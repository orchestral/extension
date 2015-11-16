Extension Component for Orchestra Platform
==============

[![Join the chat at https://gitter.im/orchestral/platform](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/orchestral/platform?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

Extension Component allows components or packages to be added dynamically to Orchestra Platform without the hassle of modifying the configuration.

[![Latest Stable Version](https://img.shields.io/github/release/orchestral/extension.svg?style=flat-square)](https://packagist.org/packages/orchestra/extension)
[![Total Downloads](https://img.shields.io/packagist/dt/orchestra/extension.svg?style=flat-square)](https://packagist.org/packages/orchestra/extension)
[![MIT License](https://img.shields.io/packagist/l/orchestra/extension.svg?style=flat-square)](https://packagist.org/packages/orchestra/extension)
[![Build Status](https://img.shields.io/travis/orchestral/extension/3.2.svg?style=flat-square)](https://travis-ci.org/orchestral/extension)
[![Coverage Status](https://img.shields.io/coveralls/orchestral/extension/3.2.svg?style=flat-square)](https://coveralls.io/r/orchestral/extension?branch=3.2)
[![Scrutinizer Quality Score](https://img.shields.io/scrutinizer/g/orchestral/extension/3.2.svg?style=flat-square)](https://scrutinizer-ci.com/g/orchestral/extension/)

## Table of Content

* [Version Compatibility](#version-compatibility)
* [Installation](#installation)
* [Configuration](#configuration)
* [Resources](#resources)

## Version Compatibility

Laravel    | Extension
:----------|:----------
 4.0.x     | 2.0.x
 4.1.x     | 2.1.x
 4.2.x     | 2.2.x
 5.0.x     | 3.0.x
 5.1.x     | 3.1.x
 5.2.x     | 3.2.x@dev

## Installation

To install through composer, simply put the following in your `composer.json` file:

```json
{
    "require": {
        "orchestra/extension": "~3.0"
    }
}
```

And then run `composer install` from the terminal.

### Quick Installation

Above installation can also be simplify by using the following command:

    composer require "orchestra/extension=~3.0"

## Configuration

Next add the following service provider in `config/app.php`.

```php
'providers' => [

    // ...

    Orchestra\Extension\ExtensionServiceProvider::class,
    Orchestra\Memory\MemoryServiceProvider::class,
    Orchestra\Publisher\PublisherServiceProvider::class,

    Orchestra\Extension\CommandServiceProvider::class,
],
```

### Migrations

Before we can start using Extension Component, please run the following:

    php artisan extension:migrate

> The command utility is enabled via `Orchestra\Extension\CommandServiceProvider`.

## Resources

* [Documentation](http://orchestraplatform.com/docs/latest/components/extension)
* [Change Log](http://orchestraplatform.com/docs/latest/components/extension/changes#v3-2)
