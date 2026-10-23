<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Keeps composite unique indexes within limits on older MySQL versions.
        Schema::defaultStringLength(191);

        // Liquid glass pagination links.
        Paginator::defaultView('pagination.glass');
        Paginator::defaultSimpleView('pagination.glass');
    }
}
