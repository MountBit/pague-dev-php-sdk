# SDK do pague.dev para PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mountbit/pague-dev-php-sdk.svg?style=flat-square)](https://packagist.org/packages/mountbit/pague-dev-php-sdk)
[![Tests](https://img.shields.io/github/actions/workflow/status/mountbit/pague-dev-php-sdk/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/mountbit/pague-dev-php-sdk/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/mountbit/pague-dev-php-sdk.svg?style=flat-square)](https://packagist.org/packages/mountbit/pague-dev-php-sdk)

SDK do pague.dev para PHP.

## Support us

We invest a lot of resources into creating [best in class open source packages](https://mountbit.com.br). You can support us by [donnation](https://donate.mapos.com.br/).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Installation

You can install the package via composer:

```bash
composer require mountbit/pague-dev-php-sdk
```

## Usage

```php
$skeleton = new MountBit\PagueDev();
echo $skeleton->echoPhrase('Hello, MountBit!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Gianluca Bine](https://github.com/Pr3d4dor)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
