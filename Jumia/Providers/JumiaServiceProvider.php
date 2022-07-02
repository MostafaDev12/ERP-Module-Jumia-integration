<?php

namespace Modules\Jumia\Providers;

use App\Business;
use App\Utils\ModuleUtil;
use Illuminate\Console\Scheduling\Schedule;

use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\Facades\View;

use Illuminate\Support\ServiceProvider;

class JumiaServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        //TODO: Need to be removed.
        view::composer('jumia::layouts.partials.sidebar', function ($view) {
            $module_util = new ModuleUtil();

            if (auth()->user()->can('superadmin')) {
                $__is_cs_enabled = $module_util->isModuleInstalled('Jumia');
            } else {
                $business_id = session()->get('user.business_id');
                $__is_cs_enabled = (boolean)$module_util->hasThePermissionInSubscription($business_id, 'jumia_module', 'superadmin_package');
            }

            $view->with(compact('__is_cs_enabled'));
        });

        $this->registerScheduleCommands();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCommands();
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('jumia.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php',
            'jumia'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/jumia');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ], 'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/jumia';
        }, \Config::get('view.paths')), [$sourcePath]), 'jumia');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/jumia');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'jumia');
        } else {
            $this->loadTranslationsFrom(__DIR__ .'/../Resources/lang', 'jumia');
        }
    }

    /**
     * Register an additional directory of factories.
     *
     * @return void
     */
    public function registerFactories()
    {
        if (! app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

    /**
     * Register commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        $this->commands([
            \Modules\Jumia\Console\JumiaSyncOrder::class,
            \Modules\Jumia\Console\JumiaSyncProducts::class
        ]);
    }

    public function registerScheduleCommands()
    {
        $env = config('app.env');
        $module_util = new ModuleUtil();
        $is_installed = $module_util->isModuleInstalled(config('jumia.name'));
        
        if ($env === 'live' && $is_installed) {
            $businesses = Business::whereNotNull('jumia_api_settings')->get();

            foreach ($businesses as $business) {
                $api_settings = json_decode($business->jumia_api_settings);
                if (!empty($api_settings->enable_auto_sync)) {
                    //schedule command to auto sync orders
                    $this->app->booted(function () use ($business) {
                        $schedule = $this->app->make(Schedule::class);
                  
                    });
                }
            }
        }
    }
}
