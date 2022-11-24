<?php

namespace RZP\Gateway;

use Config;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Constants\Entity;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Gateway\Base\Mock;

class GatewayManager extends \Illuminate\Support\Manager
{
    protected $gateways = [];

    protected $mocks = [];

    protected $servers = [];

    protected $recons = [];

    public function __construct($container)
    {
        parent::__construct($container);

        $gatewayConfig = $container['config']->get('gateway');

        $this->gateways = $gatewayConfig['available'];

        $this->registerMocks($gatewayConfig);

        $this->container = $container;
    }

    public function call($gateway, $action, $input, $mode, $terminal = null)
    {
        $gatewayName = $gateway;

        $gateway = $this->gateway($gatewayName);

        $gateway->setGatewayParams($input, $mode, $terminal);

        $this->registerTraceProcessor($input, $action);

        // Laravel helper function converts snake case to camel case
        $action = camel_case($action);

        try
        {
            return $gateway->call($action, $input);
        }
        finally
        {
            $this->revertTraceProcessor();
        }
    }

    protected function registerMocks($gatewayConfig)
    {
        foreach ($this->gateways as $gateway)
        {
            if ((isset($gatewayConfig['mock_'.$gateway])) and
                ($gatewayConfig['mock_'.$gateway] === true))
            {
                $this->mocks[] = $gateway;
            }
        }
    }

    protected function registerTraceProcessor($input, $action)
    {
        $gatewayProcessor = $this->container['trace']->processor('gateway');

        list($this->input, $this->action) = $gatewayProcessor->getInputAction();

        $gatewayProcessor->setInputAction($input, $action);
    }

    protected function revertTraceProcessor()
    {
        $this->registerTraceProcessor($this->input, $this->action);
    }

    protected function createDriver($driver)
    {
        if (in_array($driver, $this->getGateways(), true) === false)
        {
            throw new Exception\LogicException($driver . ' is not an available gateway');
        }

        $mock = $this->isMock($driver);

        return $this->createGatewayDriver($driver, $mock);
    }

    public function getCpsServiceSyncDriver($driver)
    {
        $namespace = $this->getGatewayNamespace($driver);
        return $namespace;
    }

    protected function createGatewayDriver($driver, $mock)
    {
        $namespace = $this->getGatewayNamespace($driver, $mock);

        // Constructs gateway class name in the format
        $class = $namespace . '\\' . 'Gateway';

        if (class_exists($class) === false)
        {
            throw new Exception\LogicException($class . ' is not a valid class');
        }

        $gateway = new $class;

        $gateway->setMock($mock);

        return $gateway;
    }

    protected function isMock($driver)
    {
        $mode = $this->getMode();

        $runningTests = $this->checkRunningTests();

        //
        // In case of direct auth, the mode is not set at this point,
        // it is derived later from the payment. This causes it to
        // default to live mode which calls actual gateway instead
        // of mock which is not desirable in tests. Hence we are checking
        // if tests are running. This used to work before since the tests
        // didn't clear headers before setting any auth and the ones
        // with direct auth that would land here had some setup flows
        // which were setting mode as part of setting other auth (like proxy)
        // before reaching the direct auth part. These tests broke with
        // some recent changes in auth wherein we added the authCreds class PR #8320.
        //
        if ((($mode === Mode::TEST) or ($runningTests === true)) and
            (in_array($driver, $this->getMockDrivers())))
        {
            return true;
        }

        return false;
    }

    protected function checkRunningTests(): bool
    {
        if (empty($this->container) === true)
        {
            return false;
        }

        return ($this->container->runningUnitTests() === true);
    }

    public function getDefaultDriver()
    {
        throw new Exception\LogicException('No default gateway is specified');
    }

    public function gateway($gateway)
    {
        return $this->driver($gateway);
    }

    public function driver($driver = null)
    {
        $driver = $driver ?: $this->getDefaultDriver();

        $driver = $this->createDriver($driver);

        return $driver;
    }

    public function server($driver)
    {
        $servers = & $this->servers;

        if (isset($servers[$driver]))
        {
            return $servers[$driver];
        }

        $server = $this->getServerClass($driver);

        $server = new $server;

        $servers[$driver] = $server;

        return $servers[$driver];
    }

    public function recon($driver, array $input = [])
    {
        $recons = & $this->recons;

        if (isset($recons[$driver]))
        {
            return $recons[$driver];
        }

        $recon = $this->getReconClass($driver, $input);

        $recon = new $recon;

        $recons[$driver] = $recon;

        return $recons[$driver];
    }

    public function setRecon($driver, $recon = null)
    {
        $this->recons[$driver] = $recon;

        return $recon;
    }

    public function getServerClass($driver)
    {
        $server = $this->getGatewayNamespace($driver, true) . '\\Server';

        if ($driver === 'sharp')
        {
            $server = 'RZP\Gateway\Sharp\Server';
        }

        return $server;
    }

    /**
     * During tests, if we want to set a mock server to manipulate gateway
     * server function results, then use this function to set the mock
     * object as the corresponding server instead of the default one.
     *
     * @param $driver
     * @param Mock\Server|null $server
     * @return Mock\Server
     */
    public function setServer($driver, Mock\Server $server = null)
    {
        $this->servers[$driver] = $server;

        $server->setNamespace($this->getGatewayNamespace($driver, true));

        return $server;
    }

    /**
     * Resets the mocked server for this driver to the default
     * mock server available.
     * @param  string $driver [description]
     */
    public function resetServer($driver)
    {
        $class = $this->getServerClass($driver);

        $this->servers[$driver] = new $class;
    }

    public function getReconClass($driver, array $input = [])
    {
        $reconClassName = (empty($input['type']) === false)
                          ? (studly_case($input['type']) . 'Reconciliator')
                          : 'Reconciliator';

        $reconFQCN = $this->getGatewayNamespace($driver, true) . '\\' . $reconClassName;

        return $reconFQCN;
    }

    public function resetDriver($driver)
    {
        $mock = $this->isMock($driver);

        $this->drivers[$driver] = $this->createGatewayDriver($driver, $mock);
    }

    protected function getMockDrivers()
    {
        return $this->mocks;
    }

    protected function getMode()
    {
        return $this->container['basicauth']->getMode();
    }

    protected function getGateways()
    {
        return $this->gateways;
    }

    protected function getGatewayNamespace($driver, $mock = false)
    {
        $namespace = Entity::getEntityNamespace($driver);

        if ($mock === true)
        {
            $namespace .= '\\' . 'Mock';
        }

        return $namespace;
    }
}
