# Laravel Unleash

[![Codacy Badge](https://api.codacy.com/project/badge/Grade/56b0c6402eca49169cbeb3f404c2bff9)](https://app.codacy.com/manual/mikefrancis/laravel-unleash?utm_source=github.com&utm_medium=referral&utm_content=mikefrancis/laravel-unleash&utm_campaign=Badge_Grade_Dashboard)
[![Packagist](https://img.shields.io/packagist/v/mikefrancis/laravel-unleash)](https://packagist.org/packages/mikefrancis/laravel-unleash) [![Build Status](https://github.com/mikefrancis/laravel-unleash/workflows/CI/badge.svg)](https://github.com/mikefrancis/laravel-unleash/actions?query=workflow%3ACI) [![codecov](https://codecov.io/gh/mikefrancis/laravel-unleash/branch/master/graph/badge.svg)](https://codecov.io/gh/mikefrancis/laravel-unleash)

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

This package provides a `Unleash` facade for quick and easy usage. Alternatively, you can use the aliased `Feature` facade instead.

```php
if (\Unleash::enabled('myAwesomeFeature')) {
  // Congratulations, you can see this awesome feature!
}

if (\Unleash::disabled('myAwesomeFeature')) {
  // Check back later for more features!
}

$feature = \Unleash::get('myAwesomeFeature');

$allFeatures = \Unleash::all();
```

### Unleash Client Consistency

Both the `Unleash` and `Feature` facade support methods consistent with standard Unleash clients:

```php
if (\Unleash::isFeatureEnabled('myAwesomeFeature')) {
  // Congratulations, you can see this awesome feature!
}

if (\Unleash::isFeatureDisabled('myAwesomeFeature')) {
  // Check back later for more features!
}

$feature = \Unleash::getFeature('myAwesomeFeature');

$allFeatures = \Unleash::getFeatures();
```

## Strategies

To enable or disable strategies, add or remove from `unleash.strategies` config in your `unleash.php` config file.

### Custom Strategies

Custom strategies must implement `\MikeFrancis\LaravelUnleash\Strategies\Contracts\Strategy` or if your strategy relies on dynamic data at runtime it should implement `\MikeFrancis\LaravelUnleash\Strategies\Contracts\DynamicStrategy`.

```php
use \MikeFrancis\LaravelUnleash\Strategies\Contracts\Strategy;
use \Illuminate\Http\Request;

class CustomStrategy implements Strategy {
    public function isEnabled(array $params, Request $request) : bool {
        // logic here
        return true || false;
    }
}
```

### Dynamic Strategies

When implementing `DynamicStrategy` you can pass additional arguments to the feature check functions which will be passed as extra arguments to the `isEnabled()` method:

```php
$allowList = config('app.allow_list');

if (Unleash::enabled('myAwesomeFeature', $allowList)) {
  // Congratulations, you can see this awesome feature!
}

if (Unleash::disabled('myAwesomeFeature', $allowList)) {
  // Check back later for more features!
}
```

## Variants

To use variant support, define your variants on the feature and use:

```php
$color = \Unleash::variant('title-color', '#000')->payload->value;
```

This will return the correct variant for the user, or the default if the feature flag is disabled or no valid variant is found. 

The variant payload will be one of the following, depending on the variant type:

- `\MikeFrancis\LaravelUnleash\Values\Variant\PayloadCSV`
- `\MikeFrancis\LaravelUnleash\Values\Variant\PayloadJSON`
- `\MikeFrancis\LaravelUnleash\Values\Variant\PayloadString`
- `\MikeFrancis\LaravelUnleash\Values\Variant\PayloadDefault` — when no variant is found and the default is used instead

> **Note:** You _can_ combine variants with strategies.

## Blade Templates

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

> **Note:** You cannot currently use dynamic strategy arguments with Blade template directives.

## Middleware

This package includes middleware that will deny routes depending on whether a feature is enabled or not.

To use the middle, add the following to your `app/Http/Kernel.php`:

```php
protected $routeMiddleware = [
    // other middleware
    'feature.enabled' => \MikeFrancis\LaravelUnleash\Middleware\FeatureEnabled::class,
    'feature.disabled' => \MikeFrancis\LaravelUnleash\Middleware\FeatureDisabled::class,
];
```

You can then use the middleware in your routes:

```php
Route::get('/new-feature-path', function () {
    //
})->middleware('feature.enabled:myAwesomeFeature');

Route::get('/terrible-legacy-path', function () {
    //
})->middleware('feature.disabled:myAwesomeFeature');
```

or in your controllers like so:

```php
class ExampleController extends Controller
{
    public function __construct()
    {
        $this->middleware('feature.enabled:myAwesomeFeature');
        // or
        $this->middleware('feature.disabled:myAwesomeFeature');
    }
}
```

> **Note:** You cannot currently use dynamic strategy arguments with Middleware.

## Mocking

If you are writing tests against code utilizing feature flags you can mock feature flags using the `Unleash::fake()` method.

As with Unleash itself, the default behavior is for all flags to be considered disabled:

```php
\Unleash::fake();

$this->assertFalse(\Unleash::enabled('any-flag'));
```

To consider all flags enabled, you can set the default to `true` using `withDefaultStatus()`:

```php
\Unleash::fake()->withDefaultStatus(true);

$this->assertTrue(\Unleash::enabled('any-flag'));
```

You may also dynamically return the default status using `withDefaultUsing()`:

```php
\Unleash::fake()->withDefaultStatusUsing(function($flagName, $flagStatus, ... $args) {
    return !$args[0];
});

$this->assertTrue(\Unleash::enabled('any-flag', false));

$this->assertFalse(\Unleash::enabled('any-flag', true));
```

Additionaly, there are several ways to set specific Feature Flags to enabled.

To always enable one or more feature flags, you can pass in an array of flag names:

```php
\Unleash::fake(['flag-name-to-enable', 'another-enabled-flag']);

$this->assertTrue(\Unleash::enabled('flag-name-to-enable'));
$this->assertTrue(\Unleash::enabled('another-enabled-flag'));

$this->assertFalse(\Unleash::enabled('an-unknown-flag'));
```

> **Note:** You can call `Unleash::fake()` multiple times in a single test to set additional flags

Alternatively, for more advanced scenarios, you can pass in a variable number of `\MikeFrancis\LaravelUnleash\Values\FeatureFlag` instances.

If you wish to only enable the feature with the correct `DynamicStrategy` arguments without executing the strategy, you can use `withTestArgs()`:

```php
use \MikeFrancis\LaravelUnleash\Values\FeatureFlag;

\Unleash::fake(
    (new FeatureFlag('flag-name', true))->withTestArgs(1, 2, 3);
)

$this->assertTrue(\Unleash::enabled('flag-name', 1, 2, 3));

$this->assertFalse(\Unleash::enabled('flag-name'));
$this->assertFalse(\Unleash::enabled('flag-name', 2, 4, 6));
```

If you need to validate the arguments dynamically, you can instead use `withTestArgsUsing()` which takes a callback that returns a boolean on whether the arguments are accepted or not:

```php
use \MikeFrancis\LaravelUnleash\Values\FeatureFlag;

\Unleash::fake(
    (new FeatureFlag('flag-name', true))->withTestArgsUsing(function(int $int) {
        return $int % 2 === 0;
    });
)

$this->assertTrue(\Unleash::enabled('flag-name', 2));
$this->assertTrue(\Unleash::enabled('flag-name', 4));

$this->assertFalse(\Unleash::enabled('flag-name'));
$this->assertFalse(\Unleash::enabled('flag-name', 1));
$this->assertFalse(\Unleash::enabled('flag-name', 3));
```

One final option is the `withTestArgsAny()` which will allow any arguments. This is an alias for the following:

```php
use \MikeFrancis\LaravelUnleash\Values\FeatureFlag;

\Unleash::fake(
    (new FeatureFlag('flag-name', true))->withTestArgsUsing(function() {
        return true;
    });
)
```

We recommend using the `Unleash::fake(['flag-name'])` option instead.

Lastly, you may pass in both Strategies and Variants to the `FeatureFlag` and both will execute as normal:

```php
use \MikeFrancis\LaravelUnleash\Values\FeatureFlag;

\Unleash::fake(
    (new FeatureFlag('flag-name', true, '', 'default', false, 'release', [
        'myStrategy' => MyStrategyClass::class,    
    ]))->withTestArgsUsing(function() {
        return true;
    });
)
```