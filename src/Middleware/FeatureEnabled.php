<?php
namespace MikeFrancis\LaravelUnleash\Middleware;

use MikeFrancis\LaravelUnleash\Facades\Feature;

class FeatureEnabled
{
    public function handle($request, \Closure $next, $featureName)
    {
        if (!Feature::enabled($featureName)) {
            abort(404);
        }

        return $next($request);
    }
}
