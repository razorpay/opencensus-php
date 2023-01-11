<?php


namespace RZP\Services;

use App;
use Rzp\Wda_php\WDAClient;
use GuzzleHttp\Client as GuzzleClient;

class WDAService
{
    private $config;

    protected $clientConfig;

    private $httpClient;

    public $wdaClient;

    public const WDA_QUERY_BUILDER = "wda-query-builder";

    public const ADMIN_CLUSTER = "admin";

    public const MERCHANT_CLUSTER = "merchant";

    public const WDA_USERNAME = 'username';

    public const WDA_PASSWORD = 'password';

    public const WDA_BASE_URI = 'base_uri';

    const TIMEOUT = 300;

    public function __construct($app)
    {
        $this->config = $app['config']->get('applications.wda');

        $this->config['timeout'] = self::TIMEOUT;

        $this->setConfig(self::WDA_BASE_URI, $this->config[Self::WDA_BASE_URI]);

        $this->setConfig('auth', [
            $this->config[self::WDA_USERNAME],
            $this->config[self::WDA_PASSWORD]
        ]);

        $this->httpClient = new GuzzleClient($this->clientConfig);

        $this->wdaClient = new WDAClient($this->config, $this->httpClient);
    }

    protected function getConfig(string $key)
    {
        return array_get($this->clientConfig, $key);
    }

    protected function setConfig(string $key, $data)
    {
        data_set($this->clientConfig, $key, $data);
    }

}
