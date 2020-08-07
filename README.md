#  

[![Latest Version on Packagist](https://img.shields.io/packagist/v/umobi/laravel-simple-storage.svg?style=flat-square)](https://packagist.org/packages/umobi/laravel-simple-storage)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/umobi/laravel-simple-storage/run-tests?label=tests)](https://github.com/umobi/laravel-simple-storage/actions?query=workflow%3Arun-tests+branch%3Amaster)
[![Total Downloads](https://img.shields.io/packagist/dt/umobi/laravel-simple-storage.svg?style=flat-square)](https://packagist.org/packages/umobi/laravel-simple-storage)

This package can associate all sorts of files with Eloquent models. It provides a simple API to work with. To learn all about it, head over to the extensive documentation.

## Installation

You can install the package via composer:

```bash
composer require umobi/package-laravel-simple-storage-laravel
```

## Usage

Here are a few short examples of what you can do:

``` php
class User extends Model implements StorageFieldsContract {
    use StorageFieldsTrait;
    protected $files = [
        'image' => [
            'path' => 'users',
            'type' => 'image',
            'extension' => 'jpg',
            'default' => 'default.png',
            'size' => [300, 300],
            'disk' => 'public'
        ],
    ];
}
```

``` php
$user = new User();
$user->name = 'Jane Doe';
$user->image = UploadFile|File|Url;
$user->save();
```

``` html
<img src="{!! $user->image !!}">
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email freek@umobi.be instead of using the issue tracker.

## Credits

- [Umobi](https://github.com/umobi)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
