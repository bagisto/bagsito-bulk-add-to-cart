<?php

namespace Webkul\BulkAddToCart\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class BulkAddToCartServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(Router $router)
    {
        $this->loadRoutesFrom(__DIR__ . '/../Http/routes.php');

        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'bulkaddtocart');

        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'bulkaddtocart');

        $this->app->register(EventServiceProvider::class);

        $this->publishes([
            __DIR__ . '/../Resources/views/shop/velocity/customers/account/partials/sidemenu.blade.php'
            => resource_path('themes/velocity/views/customers/account/partials/sidemenu.blade.php'),
        ]);

        $this->publishes([
            __DIR__ . '/../Resources/views/shop/velocity/UI/header.blade.php'
            => resource_path('themes/velocity/views/UI/header.blade.php'),
        ]);
    }

     /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConfig();
    }

    /**
     * Register package config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/menu.php', 'menu.customer'
        );
    }
}
