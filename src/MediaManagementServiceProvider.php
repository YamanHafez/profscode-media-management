<?php

namespace Profscode\MediaManagement;

use Illuminate\Support\ServiceProvider;

class MediaManagementServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }

    public function register(): void
    {
        //
    }
}
