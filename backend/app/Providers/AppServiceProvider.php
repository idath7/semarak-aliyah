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
        $this->app->singleton(\Illuminate\Foundation\Vite::class, function () {
            return new class extends \Illuminate\Foundation\Vite {
                public function devServerUrl()
                {
                    $url = parent::devServerUrl();
                    if (! $url) {
                        return null;
                    }

                    $host = request()->getHost();
                    if ($host && !in_array($host, ['0.0.0.0', ''])) {
                        $parsed = parse_url($url);
                        $scheme = $parsed['scheme'] ?? 'http';
                        $port = isset($parsed['port']) ? ':' . $parsed['port'] : ':5173';
                        return "{$scheme}://{$host}{$port}";
                    }

                    return str_replace('0.0.0.0', 'localhost', $url);
                }
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production' || str_starts_with(config('app.url', ''), 'https://') || request()->header('X-Forwarded-Proto') === 'https') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

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
