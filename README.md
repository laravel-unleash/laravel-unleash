# Laravel Unleash

[![Packagist](https://img.shields.io/packagist/v/mikefrancis/laravel-unleash)](https://packagist.org/packages/mikefrancis/laravel-unleash) [![Build Status](https://travis-ci.org/mikefrancis/laravel-unleash.svg?branch=master)](https://travis-ci.org/mikefrancis/laravel-unleash) [![codecov](https://codecov.io/gh/mikefrancis/laravel-unleash/branch/master/graph/badge.svg)](https://codecov.io/gh/mikefrancis/laravel-unleash)

An [Unleash](https://unleash.github.io) client for Laravel.

## Installation

```bash
composer require mikefrancis/laravel-unleash
```

Export package config:

```bash
php artisan vendor:publish --provider="MikeFrancis\LaravelUnleash\ServiceProvider"
```

## Configuration

Documentation for configuration can be found in [config/unleash.php](https://github.com/mikefrancis/laravel-unleash/blob/master/config/unleash.php).

## Usage

```php

use \MikeFrancis\LaravelUnleash\Unleash;

$unleash = app(Unleash::class);

if ($unleash->isFeatureEnabled('myAwesomeFeature')) {
  // Congratulations, you can see this awesome feature!
}

if ($unleash->isFeatureDisabled('myAwesomeFeature')) {
  // Check back later for more features!
}

$allFeatures = $unleash->getFeatures();
```

### Blade

Blade directive for checking if a feature is **enabled**:

```php
@featureEnabled('myAwesomeFeature')
Congratulations, you can see this awesome feature!
@endfeatureEnabled
```

Or if a feature is **disabled**:

```php
@featureDisabled('myAwesomeFeature')
Check back later for more features!
@endfeatureDisabled
```
