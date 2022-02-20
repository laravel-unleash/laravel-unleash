<?php

namespace LaravelUnleash\Middleware;

use Closure;
use Illuminate\Http\Request;
use LaravelUnleash\Facades\Feature;

class FeatureEnabled
{
    public function handle(Request $request, Closure $next, string $featureName)
    {
        if (!Feature::enabled($featureName)) {
            abort(404);
        }

        return $next($request);
    }
}
