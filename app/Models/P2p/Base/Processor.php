<?php

namespace RZP\Models\P2p\Base;

use RZP\Models\P2p\Base\Libraries\ArrayBag;
use RZP\Models\P2p\Base\Traits\ApplicationTrait;

/**
 *
 * Class Processor
 * @package RZP\Models\P2p\Base
 */
class Processor
{
    use ApplicationTrait;

    protected $entity = null;

    public function __construct()
    {
        $this->bootApplicationTrait();
    }

    protected function initialize(string $action, array $input = [], $validate = false)
    {
        $this->initializeApplicationTrait($action, $input);

        $this->validator = $this->getNewValidator();

        $this->core = $this->getNewCore();

        if ($validate === true)
        {
            $this->validator->validateInput($action, $input);
        }
    }

    protected function getNewValidator(): Validator
    {
        $className = str_replace('\Processor', '\Validator', static::class);

        return new $className;
    }

    protected function getNewCore()
    {
        $className = str_replace('\Processor', '\Core', static::class);

        return new $className;
    }

    protected function callGateway($data)
    {
        $gateway     = $this->getGateway();
        $action      = $this->getGatewayAction();
        $gatewayData = $data;
        $mode        = $this->mode();

        $response = $this->app['gateway']->call($gateway, $action, $gatewayData, $mode);
    }

    protected function getGateway()
    {
        return $this->context()->getHandle()->getAcquirer();
    }

    protected function getGatewayAction()
    {
        return $this->entity . '::' . $this->action;
    }
}
