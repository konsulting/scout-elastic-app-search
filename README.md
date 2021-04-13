# Elastic App Search Driver for Laravel Scout

Integrate [Elastic App Search](https://www.elastic.co/enterprise-search) with [Laravel Scout](https://laravel.com/docs/8.x/scout).

## Installation

You can install the package via composer:

```bash
composer require konsulting/scout-elastic-app-search
```

## Usage

In order to use the package, you must set Laravel Scout to use the driver
``` dotenv
SCOUT_DRIVER=elastic-app-search
```

Then set up the connection details for Elastic App Search

``` dotenv
SCOUT_ELASTIC_APP_SEARCH_ENDPOINT=
SCOUT_ELASTIC_APP_SEARCH_API_KEY=
```

You will also need to adjust `config/scout.php` so that the chunk sizes are 100 records:

``` php
'chunk' => [
    'searchable' => 100,
    'unsearchable' => 100,
],
```

### Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email keoghan@klever.co.uk instead of using the issue tracker.

## Credits

- [Keoghan Litchfield](https://github.com/konsulting)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).
