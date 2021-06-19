<?php

namespace MikeFrancis\LaravelUnleash;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Contracts\Config\Repository as Config;

class Client extends GuzzleClient
{
    public function __construct(Config $config)
    {
        parent::__construct(
            array_merge(
                ['base_uri' => $config->get('unleash.url')],
                $config->get('unleash.otherRequestDefaults')
            )
        );
    }
}
