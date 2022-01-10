<?php

Route::group(['middleware' => ['web', 'locale', 'theme', 'currency']], function () {

    Route::prefix('customer')->group(function () {

        Route::group(['middleware' => ['customer']], function () {
            Route::namespace('Webkul\BulkAddToCart\Http\Controllers')->group(function () {
                Route::get('bulk-add-to-cart', 'BulkAddToCartController@create')->defaults('_config', [
                    'view' => 'bulkaddtocart::shop.products.bulk-add-to-cart'
                ])->name('cart.bulk-add-to-cart.create');

                Route::post('bulk-add-to-cart', 'BulkAddToCartController@store')->defaults('_config', [
                    'redirect' => 'shop.checkout.cart.index'
                ])->name('cart.bulk-add-to-cart.store');

                Route::get('download-sample', 'BulkAddToCartController@downLoadSample')->defaults('_config', [
                    'view' => 'bulkaddtocart::shop.products.bulk-add-to-cart'
                ])->name('cart.bulk-add-to-cart.sample.download');
            });
        });
    });
});