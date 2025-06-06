<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;

class AssetServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // Share common assets with all views
        View::share('assetVersion', config('app.asset_version', '1.0.0'));

        // Add asset versioning directive
        Blade::directive('versioned', function ($expression) {
            return "<?php echo asset($expression) . '?v=' . config('app.asset_version', '1.0.0'); ?>";
        });

        // Add preload directive for critical assets
        Blade::directive('preload', function ($expression) {
            return "<?php echo '<link rel=\"preload\" href=\"' . asset($expression) . '?v=' . config('app.asset_version', '1.0.0') . '\" as=\"script\">'; ?>";
        });
    }
} 