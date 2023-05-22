<?php

namespace RZP\Services\Mock;

use RZP\Foundation\Application;
use Mockery;
use Razorpay\Dcs\Config\Config;
use Razorpay\Dcs\Config\UserCredentials;
use Razorpay\Dcs\Kv\V1\ApiException;
use RZP\Services\Dcs\Cache;
use RZP\Services\Dcs\Features\Service as DcsService;
use RZP\Constants\Mode;
use RZP\Models\Feature\Entity;

class DcsServiceClient extends DcsService
{
    protected $app;

    public function __construct($app)
    {
        $this->app = $app;

        parent::__construct($app);
    }

    public function initializeClientWithMode($mode): void
    {
        $creds  = new UserCredentials();
        $cache = new Cache();
        $config = new Config($cache);

        $creds->setUsername($this->config[$mode]['username'])
            ->setPassword($this->config[$mode]['password']);

        $config->setServerURL($this->config[$mode]['url'])
            ->setMock($this->config['mock'])
            ->setUserCreds($creds);
        $config->setMode($mode);

        if ($mode === Mode::LIVE)
        {
            $this->liveClient = Mockery::mock('Razorpay\Dcs\Client', [$config])->makePartial();
        }
        elseif($mode === Mode::TEST)
        {
            $this->testClient = Mockery::mock('Razorpay\Dcs\Client', [$config])->makePartial();
        }
    }
}
