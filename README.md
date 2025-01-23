# Spatie\Translatable field for [MoonShine Laravel admin panel](https://moonshine-laravel.com)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/visual-ideas/moonshine-spatie-translatable.svg?style=flat-square)](https://packagist.org/packages/visual-ideas/moonshine-spatie-translatable)
[![Total Downloads](https://img.shields.io/packagist/dt/visual-ideas/moonshine-spatie-translatable.svg?style=flat-square)](https://packagist.org/packages/visual-ideas/moonshine-spatie-translatable)

Extend [JSON](https://moonshine-laravel.com/ru/docs/3.x/fields/json) field.

## Compatibility

| MoonShine | Moonshine Spatie Translatable | Currently supported |
|:---------:|:-----------------------------:|---------------------|
| \>= v1.0  |            ^1.0               |         no          |
| \>= v2.0  |            ^2.0               |         yes         |
| \>= v3.0  |            ^3.0               |         yes         |

## Installation
**This field belongs to a separate package, you have to complete the installation before using it**

For MoonShine 2.*:
```shell
composer require "visual-ideas/moonshine-spatie-translatable:^2.0"
```

For MoonShine 3.* (current version):
```shell
composer require "visual-ideas/moonshine-spatie-translatable:^3.0"
```
The field is purposed for work with the [Laravel-translatable](https://github.com/spatie/laravel-translatable) package made by [Spatie](https://spatie.be/open-source).
Before using the Spatie\Translatable field, make sure that:
- The [spatie/laravel-translatable](https://github.com/spatie/laravel-translatable) package is installed and configured.
- The field passed to the Spatie\Translatable is added to the $translatable array of the model.

```php
use VI\MoonShineSpatieTranslatable\Fields\Translatable;
//...
Translatable::make('Title', 'name')
//...
```

## Mandatory translations
The `->requiredLanguages(array $languages)` method is used to specify the languages required by the validator for creating/saving a record.
It is recommended to pass the config('app.fallback_locale') value to this method
```php
use VI\MoonShineSpatieTranslatable\Fields\Translatable;
//...
Translatable::make('Title', 'name')
    ->requiredLanguages([config('app.fallback_locale'), 'ru'])
//...
```

## Recommended translations
If you specify this array, the language codes in the forms for adding/modifying a specific translation will be placed at the beginning of the list of all possible languages.
```php
use VI\MoonShineSpatieTranslatable\Fields\Translatable;
//...
Translatable::make('Title', 'name')
    ->priorityLanguages([config('app.fallback_locale'), config('app.locale'), 'de', 'fr', 'uk'])
//...
```

## Deleting
You can delete specific translations from out of the entered ones
```php
Translatable::make('Field', 'field')
    ->removable()
```
If you leave the translation text blank, it will be deleted!
If there are two translations into the same language, the translation that comes first will be deleted (replaced)!
