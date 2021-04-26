<?php

namespace MikeFrancis\LaravelUnleash\Middleware;

use Closure;
use Illuminate\Http\Request;
use MikeFrancis\LaravelUnleash\Facades\Feature;

class FeatureDisabled
{
    public function handle(Request $request, Closure $next, string $featureName)
    {
        if (Feature::enabled($featureName)) {
            abort(404);
        }

        return $next($request);
    }
}
