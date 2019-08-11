<?php

namespace MikeFrancis\LaravelUnleash\Strategies\Contracts;

use Illuminate\Http\Request;

interface Strategy
{
  public function isEnabled(array $params, Request $request): bool;
}
