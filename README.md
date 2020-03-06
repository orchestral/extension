Extension Component for Orchestra Platform
==============

Extension Component allows components or packages to be added dynamically to Orchestra Platform without the hassle of modifying the configuration.

[![Build Status](https://travis-ci.org/orchestral/extension.svg?branch=5.x)](https://travis-ci.org/orchestral/extension)
[![Latest Stable Version](https://poser.pugx.org/orchestra/extension/version)](https://packagist.org/packages/orchestra/extension)
[![Total Downloads](https://poser.pugx.org/orchestra/extension/downloads)](https://packagist.org/packages/orchestra/extension)
[![Latest Unstable Version](https://poser.pugx.org/orchestra/extension/v/unstable)](//packagist.org/packages/orchestra/extension)
[![License](https://poser.pugx.org/orchestra/extension/license)](https://packagist.org/packages/orchestra/extension)
[![Coverage Status](https://coveralls.io/repos/github/orchestral/extension/badge.svg?branch=5.x)](https://coveralls.io/github/orchestral/extension?branch=5.x)

## Table of Content

* [Version Compatibility](#version-compatibility)
* [Installation](#installation)
* [Configuration](#configuration)
* [Resources](#resources)
* [Changelog](https://github.com/orchestral/extension/releases)

## Version Compatibility

Laravel    | Extension
:----------|:----------
 5.5.x     | 3.5.x
 5.6.x     | 3.6.x
 5.7.x     | 3.7.x
 5.8.x     | 3.8.x
 6.x       | 4.x
 7.x       | 5.x
 
## Installation

To install through composerby using the following command:

    composer require "orchestra/extension"

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

