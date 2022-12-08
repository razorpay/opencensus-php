<?php


namespace RZP\Services;

use App;
use Rzp\Wda_php\WDAClient;

class WDAService
{
    private $config;

    public $wdaClient;

    public const WDA_QUERY_BUILDER = "wda-query-builder";

    public const ADMIN_CLUSTER = "admin";

    public const MERCHANT_CLUSTER = "merchant";

    public function __construct($app)
    {
        $this->config = $app['config']->get('applications.wda');

        $this->wdaClient = new WDAClient($this->config);
    }

}
