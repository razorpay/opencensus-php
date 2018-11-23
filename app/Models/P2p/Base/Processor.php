<?php

namespace RZP\Models\P2p\Base;

use RZP\Exception\LogicException;
use RZP\Gateway\P2p\Base\Response;
use RZP\Models\P2p\Base\Libraries\ArrayBag;
use RZP\Models\P2p\Base\Libraries\Context;
use RZP\Models\P2p\Base\Traits\ApplicationTrait;

/**
 *
 * Class Processor
 * @package RZP\Models\P2p\Base
 */
class Processor
{
    use ApplicationTrait;

    protected $entity;

    protected $validator;

    protected $core;

    /**
     * @var ArrayBag
     */
    protected $gatewayInput = null;

    /**
     * @var Response
     */
    protected $gatewayResponse = null;

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

        // Initializing the gateway data
        $this->gatewayInput     = new ArrayBag();
        $this->gatewayResponse  = new ArrayBag();
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

    /***************************** GATEWAY FUNCTIONS ****************************/

    protected function setGatewayInput(array $input)
    {
        $this->gatewayInput = $this->arrayBag($input);
    }

    /**
     * Currently we are returning same response as we got from gateway,
     * If we switched to mozart, we will have to create a new response class with same methods
     *
     */
    protected function callGateway()
    {
        $gateway     = $this->getGateway();
        $action      = $this->getGatewayAction();
        $mode        = $this->mode();

        // We are using context directly to pass to gateway. This is experimental and may change in future.
        // We might need to reverse the logic where context will be put inside gateway input.
        $this->context()->setGatewayData($gateway, $this->action, $this->gatewayInput);

        // In spite of passing the gateway data, we are passing complete context object
        $this->gatewayResponse = $this->app['gateway']->call($gateway, $action, $this->context(), $mode);

        return $this->processGatewayResponse();
    }

    protected function getGateway()
    {
        return $this->context()->getHandle()->getAcquirer();
    }

    protected function getGatewayAction()
    {
        return str_replace('p2p_', '', $this->entity);
    }

    protected function processGatewayResponse()
    {
        $suffix = $this->gatewayResponse->isSuccess() ? 'Success' : 'Failure';

        $method = $this->action . $suffix;

        if (method_exists($this, $method))
        {
            return $this->{$method}($this->gatewayResponse->data()->toArray());
        }

        throw new LogicException('Gateway response processor not found.', null , [
            'entity'    => $this->entity,
            'action'    => $this->action,
            'suffix'    => $suffix,
        ]);
    }
}
