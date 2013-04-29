Orchestra Platform Extension Component
==============

Orchestra\Extension allows components or packages to be added dynamically to Orchestra Platform without the hassle of modifying the configuration.

[![Build Status](https://travis-ci.org/orchestral/extension.png?branch=master)](https://travis-ci.org/orchestral/extension)

## Quick Installation

To install through composer, simply put the following in your `composer.json` file:

```json
{
	"require": {
		"orchestra/extension": "2.0.*"
	},
	"minimum-stability": "dev"
}
```

Next add the following service provider in `app/config/app.php`.

```php
'providers' => array(
	
	// ...

	'Orchestra\Extension\ExtensionServiceProvider',
	'Orchestra\Memory\MemoryServiceProvider',
	'Orchestra\Extension\PublisherServiceProvider',
),
```

You might want to add following facades to class aliases in `app/config/app.php`:

```php
'aliases' => array(

	// ...

	'Orchestra\Extension' => 'Orchestra\Support\Facades\Extension',
	'Orchestra\Memory'    => 'Orchestra\Support\Facades\Memory',
),
```

## Resources

* [Documentation](http://docs.orchestraplatform.com/pages/components/extension)
* [Change Logs](https://github.com/orchestral/extension/wiki/Change-Logs)
