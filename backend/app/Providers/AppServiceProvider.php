<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        try {
            if (in_array(config('database.default'), ['mysql', 'mariadb'])) {
                \Illuminate\Support\Facades\DB::connection()->getPdo();
            }
        } catch (\Throwable $e) {
            config(['database.default' => 'sqlite']);
        }
    }
}
