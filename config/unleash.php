<?php

return [
  // URL of the Unleash server. 
  // This should be the base URL, do not include /api or anything else.
  'url' => env('UNLEASH_URL'),

  // Globally control whether Unleash is enabled or disabled.
  // If not enabled, no API requests will be made and all "enabled" checks will return `false` and 
  // "disabled" checks will return `true`.
  'isEnabled' => env('UNLEASH_ENABLED', true),

  // Allow the Unleash API response to be cached.
  'cache' => [
    'isEnabled' => false,
    'ttl' => 3600,
  ],

  // Mapping of strategies used to guard features on Unleash. The default strategies are already 
  // mapped below, and more strategies can be added - they just need to implement the 
  // `\MikeFrancis\LaravelUnleash\Strategies\Strategy` interface. If you would like to disable 
  // a built-in strategy, please comment it out or remove it below.
  'strategies' => [
    'applicationHostname' => \MikeFrancis\LaravelUnleash\Strategies\ApplicationHostnameStrategy::class,
    'default' => \MikeFrancis\LaravelUnleash\Strategies\DefaultStrategy::class,
    'remoteAddress' => \MikeFrancis\LaravelUnleash\Strategies\RemoteAddressStrategy::class,
    'userWithIds' => \MikeFrancis\LaravelUnleash\Strategies\UserWithIdStrategy::class,
  ],
];
