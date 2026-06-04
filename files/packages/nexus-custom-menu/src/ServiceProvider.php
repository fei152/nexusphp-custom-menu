<?php

namespace NexusPlugin\CustomMenu;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', Repository::ID);
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
