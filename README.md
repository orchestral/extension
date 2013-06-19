Orchestra Platform Extension Component
==============

Orchestra\Extension allows components or packages to be added dynamically to Orchestra Platform without the hassle of modifying the configuration.

[![Build Status](https://travis-ci.org/orchestral/extension.png?branch=master)](https://travis-ci.org/orchestral/extension) [![Coverage Status](https://coveralls.io/repos/orchestral/extension/badge.png?branch=master)](https://coveralls.io/r/orchestral/extension?branch=master)

## Quick Installation

To install through composer, simply put the following in your `composer.json` file:

```json
{
	"require": {
		"orchestra/extension": "2.0.*"
	}
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

## Resources

* [Documentation](http://orchestraplatform.com/docs/2.0/components/extension)
* [Change Logs](https://github.com/orchestral/extension/wiki/Change-Logs)
