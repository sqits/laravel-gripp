# Laravel wrapper for the Gripp api v3

## Installation and usage
This package requires PHP 7.2 and Laravel 5.6 or higher. Install the package by running the following command in your console;

``` bash
composer require sqits/laravel-gripp --dev
```

You can publish the config file with:

``` bash
php artisan vendor:publish --provider="Sqits\Gripp\GrippServiceProvider" --tag="config"
```

This is the contents of the published config file:

``` php
return [

    /*
     * Define the default Gripp api uri.
     */

    'domain' => env('GRIPP_DOMAIN', 'https://api.gripp.com/'),
    
    /*
     * Define the Gripp api key.
     */

    'api_key' => env('GRIPP_API_KEY', null),
];
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Security

If you discover any security-related issues, please [email](mailto:info@sqits.nl) to info@sqits.nl instead of using the issue tracker.

## Credits

- [Sqits](https://github.com/sqits)
- [Milan Jansen](https://github.com/MilanJn)
- [Ruud Schaaphuizen](https://github.com/rschaaphuizen)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
