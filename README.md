Extension Component for Orchestra Platform
==============

Extension Component allows components or packages to be added dynamically to Orchestra Platform without the hassle of modifying the configuration.

[![Latest Stable Version](https://img.shields.io/github/release/orchestral/extension.svg?style=flat)](https://packagist.org/packages/orchestra/extension)
[![Total Downloads](https://img.shields.io/packagist/dt/orchestra/extension.svg?style=flat)](https://packagist.org/packages/orchestra/extension)
[![MIT License](https://img.shields.io/packagist/l/orchestra/extension.svg?style=flat)](https://packagist.org/packages/orchestra/extension)
[![Build Status](https://img.shields.io/travis/orchestral/extension/2.0.svg?style=flat)](https://travis-ci.org/orchestral/extension)
[![Coverage Status](https://img.shields.io/coveralls/orchestral/extension/2.0.svg?style=flat)](https://coveralls.io/r/orchestral/extension?branch=2.0)
[![Scrutinizer Quality Score](https://img.shields.io/scrutinizer/g/orchestral/extension/2.0.svg?style=flat)](https://scrutinizer-ci.com/g/orchestral/extension/)

## Table of Content

* [Version Compatibility](#version-compatibility)
* [Installation](#installation)
* [Configuration](#configuration)
* [Resources](#resources)

## Version Compatibility

Laravel    | Extension
:----------|:----------
 4.0.x     | 2.0.x

## Installation

To install through composer, simply put the following in your `composer.json` file:

```json
{
    "require": {
        "orchestra/extension": "2.0.*"
    }
}
```

And then run `composer install` from the terminal.

### Quick Installation

Above installation can also be simplify by using the following command:

    composer require "orchestra/extension=2.0.*"

## Configuration

Next add the following service provider in `app/config/app.php`.

```php
'providers' => array(

	// ...

	'Orchestra\Extension\ExtensionServiceProvider',
	'Orchestra\Memory\MemoryServiceProvider',
	'Orchestra\Extension\PublisherServiceProvider',

	'Orchestra\Extension\CommandServiceProvider',
),
```

### Migrations

Before we can start using Extension Component, please run the following:

```bash
php artisan orchestra:extension install
```

> The command utility is enabled via `Orchestra\Extension\CommandServiceProvider`.

## Resources

* [Documentation](http://orchestraplatform.com/docs/2.0/components/extension)
* [Change Log](http://orchestraplatform.com/docs/2.0/components/extension/changes#v2.0)
