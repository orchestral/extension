# Changelog

This changelog references the relevant changes (bug and security fixes) done to `orchestra/extension`.

## 3.7.1

Released: 2019-02-21

### Changes

* Improve performance by prefixing all global functions calls with `\` to skip the look up and resolve process and go straight to the global function.

## 3.7.0

Released: 2018-09-14

### Changes

* Update support for Laravel Framework v5.7.

### Removed

* Remove deprecated `Orchestra\Extension\Traits\DomainAware`, use `Orchestra\Extension\Concerns\DomainAware` instead.

## 3.6.0

Released: 2018-05-06

### Added

* Added `Orchestra\Extension\Concerns\DomainAware`.

### Changes

* Update support for Laravel Framework v5.6.

### Deprecated

* Deprecate `Orchestra\Extension\Traits\DomainAware`, use `Orchestra\Extension\Concerns\DomainAware` instead.

## 3.5.1

Released: 2018-01-15

### Added

* Added `orchestra.extension.url` service location which bind to `Orchestra\Extension\UrlGenerator`.

### Deprecated

* Deprecate `Orchestra\Extension\RouteGenerator`.

## 3.5.0

Released: 2017-09-03

### Changes

* Update support for Laravel Framework v5.5.

### Removed

* Remove deprecated `Orchestra\Extension\Traits\DomainAwareTrait`, use `Orchestra\Extension\Traits\DomainAware` instead.
