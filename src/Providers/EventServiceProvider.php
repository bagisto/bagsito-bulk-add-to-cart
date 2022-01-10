<?php

namespace Webkul\BulkAddToCart\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Event::listen('bagisto.shop.layout.head', function($viewRenderEventManager) {
            if (core()->getCurrentChannel()->theme == "velocity") {
                $viewRenderEventManager->addTemplate('bulkaddtocart::shop.style');
            }
        });

        Event::listen('bagisto.shop.sidemenu.after', function($viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('bulkaddtocart::shop.velocity.UI.icons');
        });
    }
}
