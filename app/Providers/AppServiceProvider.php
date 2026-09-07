<?php

namespace App\Providers;

use App\Helpers\Helper;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('fun', function () {
            return new Helper();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        try {
            $rawVer = file_exists(base_path('version.txt')) ? trim(file_get_contents(base_path('version.txt'))) : '1.0.0';
            $systemVersion = 'v' . ltrim($rawVer, 'v');
            $systemTitle = 'JSBolsas Pro ' . $systemVersion;
            config(['app.name' => $systemTitle]);
            \Illuminate\Support\Facades\View::share('systemVersion', $systemVersion);
            \Illuminate\Support\Facades\View::share('systemTitle', $systemTitle);
        } catch (\Throwable $e) {}

        try {
            if (!app()->runningInConsole() && isset($_SERVER['HTTP_HOST'])) {
                $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
                $currentUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
                config(['app.url' => $currentUrl]);
                \Illuminate\Support\Facades\URL::forceRootUrl($currentUrl);
            }
        } catch (\Throwable $e) {}

        try {
            @ini_set('upload_max_filesize', '64M');
            @ini_set('post_max_size', '64M');
            @ini_set('memory_limit', '512M');

            $publicStoragePath = storage_path('app/public');
            $symlink = public_path('storage');
            if (!file_exists($symlink) && !is_link($symlink)) {
                @app('files')->link($publicStoragePath, $symlink);
            }
        } catch (\Throwable $e) {}
    }
}
