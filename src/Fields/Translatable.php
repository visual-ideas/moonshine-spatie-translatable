<?php

namespace VI\MoonShineSpatieTranslatable\Fields;

use Closure;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Str;
use MoonShine\Contracts\Core\DependencyInjection\FieldsContract;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\UI\Exceptions\FieldException;
use MoonShine\AssetManager\Css;
use MoonShine\UI\Fields\Field;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;
use Illuminate\Contracts\View\View;
use Throwable;

final class Translatable extends Json
{
    protected bool $keyValue = true;
    protected bool $onlyValue = false;

    /**
     * @var class-string<FieldContract>
    */
    protected string $inputField = Text::class;

    protected array $languagesCodes = [
        "af", "sq", "am", "ar", "an", "hy", "ast", "az", "eu", "be", "bn", "bs", "br", "bg", "ca", "ckb", "zh", "zh-hk",
        "zh-cn", "zh-tw", "co", "hr", "cs", "da", "nl", "en", "en-au", "en-ca", "en-in", "en-nz", "en-za", "en-gb",
        "en-us", "eo", "et", "fo", "fil", "fi", "fr", "fr-ca", "fr-fr", "fr-ch", "gl", "ka", "de", "de-at", "de-de",
        "de-li", "de-ch", "el", "gn", "gu", "ha", "haw", "he", "hi", "hu", "is", "id", "ia", "ga", "it", "it-it",
        "it-ch", "ja", "kn", "kk", "km", "ko", "ku", "ky", "lo", "la", "lv", "ln", "lt", "mk", "ms", "ml", "mt", "mr",
        "mn", "ne", "no", "nb", "nn", "oc", "or", "om", "ps", "fa", "pl", "pt", "pt-br", "pt-pt", "pa", "qu", "ro",
        "mo", "rm", "ru", "gd", "sr", "sh", "sn", "sd", "si", "sk", "sl", "so", "st", "es", "es-ar", "es-419", "es-mx",
        "es-es", "es-us", "su", "sw", "sv", "tg", "ta", "tt", "te", "th", "ti", "to", "tr", "tk", "tw", "uk", "ur",
        "ug", "uz", "vi", "wa", "cy", "fy", "xh", "yi", "yo", "zu",
    ];

    protected array $requiredLanguagesCodes = [];

    protected array $priorityLanguagesCodes = [];

    protected bool $isCompact = false;

    protected function assets(): array
    {
        return [
            Css::make('vendor/moonshine-spatie-translatable/css/translatable-compact.css'),
            ...$this->getInputFieldAssets(),
        ];
    }

    protected function getInputFieldAssets(): array
    {
        if ($this->inputField === Text::class || $this->inputField === Textarea::class) {
            return [];
        }

        $instance = $this->inputField::make(__('Value'), 'value');

        return $instance->getAssets();
    }

    /** @return array<string, string> Rendered HTML per language code */
    protected function renderLanguageFields(array $values, array $languages): array
    {
        $rendered = [];

        foreach ($languages as $code => $name) {
            $value = $values[$code] ?? '';

            $field = $this->getInputFieldInstance($value);
            $field->setValue($value);

            $rendered[$code] = $field->render();
        }

        return $rendered;
    }

    public function getInputFieldInstance(?string $value = null): FieldContract
    {
        $field = $this->inputField::make('', 'value');
        $field->withoutWrapper();

        if ($value !== null) {
            $field->setValue($value);
        }

        return $field;
    }

    public function getInputField(): string
    {
        return $this->inputField;
    }

    public function compact(): static
    {
        $this->isCompact = true;

        return $this;
    }

    public function isCompact(): bool
    {
        return $this->isCompact;
    }

    protected function resolveRender(): Renderable|Closure|string
    {
        if ($this->isCompact()) {
            if (! $this->isDefaultMode() && $this->isPreviewMode()) {
                return $this->preview();
            }

            return $this->renderCompact();
        }

        return parent::resolveRender();
    }

    protected function renderCompact(): Renderable|Closure|string
    {
        $values = $this->getValue() ?? [];

        // Normalise from key-value array [[key,value],...] to associative [lang=>value,...]
        if (\is_array($values) && \count($values) > 0) {
            $first = reset($values);
            if (\is_array($first) && array_key_exists('key', $first) && array_key_exists('value', $first)) {
                $values = collect($values)
                    ->mapWithKeys(fn (array $item): array => [$item['key'] => $item['value']])
                    ->toArray();
            }
        }

        $allLanguages = $this->getLanguagesCodes();

        // Rebuild $values in the declared language order, keeping any extra
        // (undeclared) languages at the end.
        $orderedValues = [];
        foreach (array_keys($allLanguages) as $code) {
            if (array_key_exists($code, $values)) {
                $orderedValues[$code] = $values[$code];
            }
        }
        foreach ($values as $code => $v) {
            if (!isset($allLanguages[$code])) {
                $orderedValues[$code] = $v;
            }
        }
        $values = $orderedValues;

        // Determine the active language
        $existing = array_keys($values);
        $activeLanguage = $existing[0]
            ?? $this->requiredLanguagesCodes[0]
            ?? $this->priorityLanguagesCodes[0]
            ?? array_key_first($allLanguages)
            ?? 'en';

        // Sort languages alphabetically for the add-dropdown
        $sortedLanguages = $allLanguages;
        sort($sortedLanguages);

        // Pre-render the input field for each language.
        // Each rendered field includes its own Alpine component (if any),
        // so TinyMCE, Code, or any custom field initializes itself.
        $renderedFields = $this->renderLanguageFields($values, $allLanguages);

        return view('moonshine-spatie-translatable::fields.translatable-compact', [
            'field' => $this,
            'values' => $values,
            'allLanguages' => $allLanguages,
            'sortedLanguages' => $sortedLanguages,
            'activeLanguage' => $activeLanguage,
            'isRemovable' => $this->isRemovable(),
            'renderedFields' => $renderedFields,
        ]);
    }

    protected function prepareFill(array $raw = [], mixed $casted = null): array
    {
        if ($casted === null) {
            return [];
        }

        // DataWrapperContract (e.g. ModelDataWrapper) proxies method calls via __call,
        // which method_exists() cannot detect. Extract the underlying model first.
        if ($casted instanceof DataWrapperContract) {
            $casted = $casted->getOriginal();
        }

        if (! method_exists($casted, 'getTranslations')) {
            return [];
        }

        try {
            $translations = $casted->getTranslations($this->column);
        } catch (Throwable) {
            return [];
        }

        if (empty($translations)) {
            return [];
        }

        return collect($translations)
            ->map(fn ($v, $k): array => ['key' => $k, 'value' => $v])
            ->values()
            ->toArray();
    }

    /**
     * prepareFill already returns key-value format,
     * so skip the parent Json's reformatFilledValue which
     * would double-encode it as {key: index, value: {...}}.
     */
    protected function reformatFilledValue(mixed $data): mixed
    {
        return $data;
    }

    /**
     * @throws Throwable
     */
    public function onlyValue(
        string $value = 'Value',
        ?FieldContract $valueField = null,
    ): static {
        throw new FieldException('Can`t set onlyValue for this field!');
    }

    public function getFields(mixed $data = null): FieldsContract
    {
        $inputField = $this->inputField::make(__('Value'), 'value');

        if (empty($this->fields)) {
            $this->fields([
                Select::make(__('Code'), 'key')
                    ->options(array_combine(
                        $this->getLanguagesCodes(),
                        array_map(static fn ($code) => Str::upper($code), $this->getLanguagesCodes())
                    )),
                $inputField,
            ]);
        }

        return parent::getFields();
    }

    public function languages(array $languages): self
    {
        $this->languagesCodes = $languages;

        return $this;
    }

    public function requiredLanguages(array $languages): self
    {
        $this->requiredLanguagesCodes = $languages;

        return $this;
    }

    public function priorityLanguages(array $languages): self
    {
        $this->priorityLanguagesCodes = $languages;

        return $this;
    }

    protected function getLanguagesCodes(): array
    {

        return collect(array_combine($this->requiredLanguagesCodes, $this->requiredLanguagesCodes))
            ->merge(array_combine($this->priorityLanguagesCodes, $this->priorityLanguagesCodes))
            ->merge(array_combine($this->languagesCodes, $this->languagesCodes))
            ->toArray();
    }

    public function textarea(): self
    {
        $this->inputField = Textarea::class;

        return $this;
    }

    public function tinyMce(): self
    {
        $tinyMceClass = 'MoonShine\\TinyMce\\Fields\\TinyMce';

        if (!class_exists($tinyMceClass)) {
            throw new \RuntimeException(
                'Install moonshine/tinymce to use TinyMce input.'
            );
        }

        $this->inputField = $tinyMceClass;

        return $this;
    }

    public function json(): self
    {
        $this->inputField = Json::class;

        return $this;
    }

    public function customInputField(string $class): self
    {
        if (!is_subclass_of($class, Field::class)) {
            throw new FieldException('The passed class must be a subclass of MoonShine\UI\Fields\Field');
        }

        $this->inputField = $class;

        return $this;
    }

    public function keyValue(
        string $key = 'Language',
        string $value = 'Value',
        ?FieldContract $keyField = null,
        ?FieldContract $valueField = null,
    ): static {
        $this->fields([
            Select::make($key, 'key')
                ->options(array_combine(
                    $this->getLanguagesCodes(),
                    array_map(static fn ($code) => Str::upper($code), $this->getLanguagesCodes())
                ))
                ->nullable(),
            $this->inputField::make($value, 'value'),
        ]);

        return $this;
    }

    public function hasFields(): bool
    {
        return true;
    }

    protected function resolveOnApply(): null|Closure
    {
        return function ($item) {
            $translations = $this->getRequestValue() !== false
                ? collect($this->getRequestValue())->mapWithKeys(function ($item) {
                    return [$item['key'] => $item['value']];
                })->toArray()
                : $item->getTranslations($this->column);

            $item->replaceTranslations($this->column, $translations);

            return $item;
        };
    }

    protected function resolvePreview(): View|string
    {
        return $this?->data?->getOriginal()->getTranslation($this->column, app()->getLocale()) ?? '';
    }
}
