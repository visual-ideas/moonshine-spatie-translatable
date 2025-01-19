<?php

namespace VI\MoonShineSpatieTranslatable\Fields;

use Illuminate\Support\Str;
use MoonShine\Contracts\Core\DependencyInjection\FieldsContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\UI\Exceptions\FieldException;
use MoonShine\UI\Fields\Field;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;
use MoonShine\TinyMce\Fields\TinyMce;

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

    protected function prepareFill(array $raw = [], mixed $casted = null): mixed
    {
        return $casted->getOriginal()->getTranslations($this->column);
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
                    ->options(array_combine($this->getLanguagesCodes(),
                        array_map(static fn($code) => Str::upper($code), $this->getLanguagesCodes()))),
                $inputField,
            ]);
        }

        return parent::getFields();
    }

    public function languages(array $languages): self
    {
        sort($languages);
        $this->languagesCodes = $languages;

        return $this;
    }

    public function requiredLanguages(array $languages): self
    {
        sort($languages);
        $this->requiredLanguagesCodes = $languages;

        return $this;
    }

    public function priorityLanguages(array $languages): self
    {
        sort($languages);
        $this->priorityLanguagesCodes = $languages;

        return $this;
    }

    protected function getLanguagesCodes(): array
    {
        sort($this->languagesCodes);

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
        $this->inputField = TinyMce::class;

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
                ->options(array_combine($this->getLanguagesCodes(),
                    array_map(static fn($code) => Str::upper($code), $this->getLanguagesCodes())))
                ->nullable(),
            $this->inputField::make($value, 'value'),
        ]);

        return $this;
    }

    public function hasFields(): bool
    {
        return true;
    }

    protected function resolvePreview(): View|string
    {
        return $this?->data?->{$this->column} ?? '';
    }
}
