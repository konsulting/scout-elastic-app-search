# Elastic App Search Driver for Laravel Scout

Integrate [Elastic App Search](https://www.elastic.co/enterprise-search) with [Laravel Scout](https://laravel.com/docs/8.x/scout).

This is an early but functional version. Tests to be added.

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

Once you have adde the Searchable Trait to your model. You will be able to search with:
``` php 
 $result = Model::search($searchTerm)->get();
```

If you wish to have more control over the search, you can extend it in the familiar way with Scout.

``` php
 $result = Model::search($searchTerm, function (ElasticAppProxy $elastic, $query, $options) {
    // Adjust the options here
    // E.g. set the search fields in options, and add weightings
    $options['search_fields']['field_name']['weight'] = 1;
   
   // Use filters, and so on
    $options['filters'] = [
        'all' => [
            'name' => 'Konsulting',
            'keyword' => 'Scout',
        ],
    ];

    // Manipulate the position in results
    $options['page']['size'] = $this->limit;
    $options['page']['current'] = $this->currentPage();

    return $elastic->search($query, $options);
})->get();
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
