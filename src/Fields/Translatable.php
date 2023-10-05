<?php

namespace VI\MoonShineSpatieTranslatable\Fields;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use MoonShine\Fields\Fields;
use MoonShine\Fields\Json;
use MoonShine\Fields\Select;
use MoonShine\Fields\Text;
use MoonShine\Fields\Textarea;
use MoonShine\Fields\TinyMce;

class Translatable extends Json
{

    protected $inputField = Text::class;

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

    protected bool $keyValue = true;

    public function textarea(): static {

        $this->inputField = Textarea::class;

        return $this;
    }

    public function tinyMce(): static {
    
        $this->inputField = TinyMce::class;
    
        return $this;
    }

    public function languages(array $languages): static
    {
        sort($languages);
        $this->languagesCodes = $languages;

        return $this;
    }

    public function requiredLanguages(array $languages): static
    {
        sort($languages);
        $this->requiredLanguagesCodes = $languages;

        return $this;
    }

    public function priorityLanguages(array $languages): static
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

    public function keyValue(string $key = 'Language', string $value = 'Value'): static
    {
        $this->fields([
            Select::make($key, 'key')
                ->options(array_combine($this->getLanguagesCodes(),
                    array_map(static fn($code) => Str::upper($code), $this->getLanguagesCodes())))
                ->nullable(),
            $this->inputField::make($value, 'value'),
        ]);

        return $this;
    }

    public function getFields(): Fields
    {

        if (empty($this->fields)) {
            $this->fields([
                Select::make(__('Code'), 'key')
                    ->options(array_combine($this->getLanguagesCodes(),
                        array_map(static fn($code) => Str::upper($code), $this->getLanguagesCodes())))
                    ->nullable(),
                $this->inputField::make(__('Value'), 'value'),
            ]);
        }

        return parent::getFields();
    }

    public function hasFields(): bool
    {
        return true;
    }

    public function indexViewValue(Model $item, bool $container = false): string
    {

        return (string)$item->{$this->field()};
    }

    public function exportViewValue(Model $item): string
    {
        return (string)$item->{$this->field()};
    }

    public function formViewValue(Model $item): mixed
    {

        $translations = collect($item->getTranslations($this->field()));

        if ($translations->isEmpty() && $this->requiredLanguagesCodes) {
            $translations = [];
            foreach ($this->requiredLanguagesCodes as $code) {
                $translations[$code] = '';
            }
            $translations = collect($translations);
        }

        return $translations->mapWithKeys(fn(string $value, string $key) => [
            $key => ['key' => $key, 'value' => $value]
        ])
            ->values()
            ->toArray();
    }

    /**
     * @throws ValidationException
     */
    public function save(Model $item): Model
    {
        if ($this->isCanSave() && $this->requestValue() !== false) {

            $array = collect($this->requestValue())
                ->filter(fn($data) => !empty($data['key']) && !empty($data['value']))
                ->mapWithKeys(fn($data) => [$data['key'] => $data['value']])
                ->toArray();

            $notSetLanguages = array_diff($this->requiredLanguagesCodes, array_keys($array));

            if (!empty($notSetLanguages)) {
                throw ValidationException::withMessages(
                    [
                        $this->field() =>
                            sprintf('The field %s does not have translation values set for the following languages: %s',
                                $this->label(), implode(', ', $notSetLanguages)),
                    ]
                );
            }

            if ($this->isRemovable()) {
                $item->replaceTranslations($this->field(), $array);
                return $item;
            }

            $item->setTranslations($this->field(), $array);
            return $item;

        }

        return $item;
    }
}



