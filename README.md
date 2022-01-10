# Introduction

Bagisto Bulk Add to Cart add-on allow customers to upload bulk data to cart.

## Requirements:

- **Bagisto**: v1.3.2.

## Installation with composer:
- Run the following command
```
composer require bagisto/bagisto-bulk-add-to-cart
```
-Goto vendor/bagisto/bagisto-bulkaddtocart and copy the storage folder and merge it into project root directory.

- Run these commands below to complete the setup
```
composer dump-autoload
```

```
php artisan route:cache
php artisan optimize
```
```
php artisan vendor:publish --force

-> Press the number before BulkAddToCartServiceProvider and then press enter to publish all assets and configurations.
```

## Installation without composer:

- Unzip the respective extension zip and then merge "packages" and "storage" folders into project root directory.
- Goto config/app.php file and add following line under 'providers'

```
Webkul\BulkAddToCart\Providers\BulkAddToCartServiceProvider::class
```

- Goto composer.json file and add following line under 'psr-4'

```
"Webkul\\BulkAddToCart\\": "packages/Webkul/BulkAddToCart/src"
```

- Run these commands below to complete the setup

```
composer dump-autoload
```

```
php artisan route:cache
```

```
php artisan optimize
```

```
php artisan vendor:publish --force

-> Press the number before BulkAddToCartServiceProvider and then press enter to publish all assets and configurations.
```

> That's it, now just execute the project on your specified domain.