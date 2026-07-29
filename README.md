# Spatie\Translatable field for [MoonShine Laravel admin panel](https://moonshine-laravel.com)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/visual-ideas/moonshine-spatie-translatable.svg?style=flat-square)](https://packagist.org/packages/visual-ideas/moonshine-spatie-translatable)
[![Total Downloads](https://img.shields.io/packagist/dt/visual-ideas/moonshine-spatie-translatable.svg?style=flat-square)](https://packagist.org/packages/visual-ideas/moonshine-spatie-translatable)

Extends the MoonShine [JSON](https://moonshine-laravel.com/ru/docs/4.x/fields/json) field to work with [spatie/laravel-translatable](https://github.com/spatie/laravel-translatable). Provides a full interface for editing multi-language model attributes inside MoonShine resources.

## Compatibility

| MoonShine | Package | Supported |
|:---------:|:-------:|:---------:|
| >= v1.0   | ^1.0    | no        |
| >= v2.0   | ^2.0    | yes       |
| >= v3.0   | ^3.0    | yes       |
| >= v4.0   | ^3.0    | yes       |

## Installation

**This field belongs to a separate package — complete installation before using it.**

```shell
composer require "visual-ideas/moonshine-spatie-translatable:^3.0"
```

> For MoonShine 2.* use `^2.0` instead.

The package registers automatically via Laravel's package auto-discovery. If you have disabled auto-discovery, register the service provider manually:

```php
// config/app.php
'providers' => [
    VI\MoonShineSpatieTranslatable\ServiceProvider::class,
],
```

> For Laravel 11+, add `VI\MoonShineSpatieTranslatable\ServiceProvider::class` to `bootstrap/providers.php` instead.

### Requirements

- PHP 8.0–8.5
- MoonShine >= 3.0
- [spatie/laravel-translatable](https://github.com/spatie/laravel-translatable) installed and configured
- The model attribute must be in the `$translatable` array

### Assets

Publish assets for the compact mode styles:

```shell
php artisan vendor:publish --tag=moonshine-spatie-translatable-assets
```

## Basic usage

```php
use VI\MoonShineSpatieTranslatable\Fields\Translatable;

// In your MoonShine resource fields() method:
Translatable::make('Title', 'name')
```

The field renders as a key-value JSON list where each row has a language code (Select) and a value (Text input by default).

> ⚠️ The `onlyValue()` method (inherited from the parent `Json` field) is **not supported** — translatable fields always require both a language key and a value.

## How it works

The field integrates with [spatie/laravel-translatable](https://github.com/spatie/laravel-translatable) at the model level:

1. **Storage** — Spatie stores all translations as a JSON object in a single database column (e.g., `{"lv": "Nosaukums", "en": "Title", "ru": "Название"}`)
2. **Reading** — The field calls `$model->getTranslations('column')` to retrieve existing translations and converts them into key-value pairs for the form (`[{key: 'lv', value: 'Nosaukums'}, ...]`)
3. **Saving** — On form submit, the field converts the key-value pairs back into a translations array and calls `$model->replaceTranslations('column', [...])`

The underlying UI is MoonShine's [Json field](https://moonshine-laravel.com/docs/4.x/fields/json) with a language code `Select` and a value input.

## Language configuration

### `languages(array $codes)`

Restrict the language list to specific codes. By default ~120 common locale codes are available.

```php
Translatable::make('Title', 'name')
    ->languages(['lv', 'en', 'ru'])
```

### `requiredLanguages(array $codes)`

Languages to place at the very top of the list, before `priorityLanguages`. Useful for highlighting important locales.

```php
Translatable::make('Title', 'name')
    ->requiredLanguages([config('app.fallback_locale')])
```

### `priorityLanguages(array $codes)`

Bring certain languages to the top of the list in the form.

```php
Translatable::make('Title', 'name')
    ->priorityLanguages([
        config('app.fallback_locale'),
        config('app.locale'),
        'de', 'fr',
    ])
```

> Merge order: `requiredLanguages` first, then `priorityLanguages`, then all `languages`.

## Input field type

By default the value input is a single-line `Text` field. You can switch to other field types.

### `textarea()`

```php
Translatable::make('Description', 'description')
    ->textarea()
```

### `tinyMce()`

Requires `moonshine/tinymce` package.

```php
Translatable::make('Body', 'body')
    ->tinyMce()
```

### `json()`

```php
Translatable::make('Metadata', 'metadata')
    ->json()
```

### `customInputField(string $fieldClass)`

Any MoonShine field class that extends `MoonShine\UI\Fields\Field`.

```php
Translatable::make('Code', 'code')
    ->customInputField(CodeField::class)
```

## Compact mode

The field can render as a **compact tabbed interface** — language badges at the top, one visible editor at a time.

```php
Translatable::make('Title', 'name')
    ->compact()
```

Features:
- Language badges with active state highlighting
- Add new languages from a dropdown (shows only unselected languages)
- Supports any input type (Text, Textarea, TinyMCE, etc.)
- Syncs values on language switch — no data loss when switching between tabs
- Hidden inputs for all languages render for form submission

## Removing translations

The `removable()` method works in **both modes** (default key-value and compact). It adds a remove button to each translation row.

```php
Translatable::make('Field', 'field')
    ->removable()
```

> ⚠️ If you leave the translation text blank, it will be deleted on save.
> If there are two translations for the same language, the first one is removed (replaced).

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md) for more information.

