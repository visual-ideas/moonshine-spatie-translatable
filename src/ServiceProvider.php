<?php

namespace VI\MoonShineSpatieTranslatable;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(
            __DIR__ . '/../resources/views',
            'moonshine-spatie-translatable',
        );

        $this->publishes([
            __DIR__ . '/../public' => public_path('vendor/moonshine-spatie-translatable'),
        ], ['moonshine-spatie-translatable-assets', 'laravel-assets']);
    }
}
