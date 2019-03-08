<?php

namespace RZP\Models\P2p\Base;

use Crypt;
use Illuminate\Support\Arr;
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
     * @var ArrayBag
     */
    protected $callbackInput = null;

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

        $this->entity = $this->getNewEntity();

        $this->validator = $this->getNewValidator();

        $this->core = $this->getNewCore();

        if ($validate === true)
        {
            $this->validator->validateInput($action, $input);
        }

        // Initializing the gateway data
        $this->gatewayInput     = new ArrayBag();
        $this->callbackInput    = new ArrayBag();
    }

    protected function getNewEntity(): Entity
    {
        $className = str_replace('\Processor', '\Entity', static::class);

        return new $className;
    }

    protected function getNewValidator(): Validator
    {
        $className = str_replace('\Processor', '\Validator', static::class);

        return new $className($this->entity);
    }

    protected function getNewCore()
    {
        $className = str_replace('\Processor', '\Core', static::class);

        return new $className;
    }

    protected function getNewAction()
    {
        $className = str_replace('\Processor', '\Action', static::class);

        return new $className;
    }

    /******************************* COMMON ACTIONS *****************************/

    public function fetchAll(array $input): array
    {
        $this->initialize(Action::FETCH_ALL, $input, true);

        $entities = $this->core->fetchAll($input);

        return $entities->toArrayPublic();
    }

    public function fetch(array $input): array
    {
        $this->initialize(Action::FETCH, $input, true);

        $entity = $this->core->fetch($this->input->get(Entity::ID));

        return $entity->toArrayPublic();
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

        // These two variables are passed directly to gateway and only gateway can validate these
        $this->gatewayInput->put('sdk', $this->arrayBag($this->input->get('sdk', [])));
        $this->gatewayInput->put('callback', $this->arrayBag($this->input->get('callback', [])));

        // Before passing input to gateway we will run basic check
        $this->modifyGatewayInput($this->gatewayInput);

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
        $action = strtr(static::class, ['RZP\Models\P2p\\' => '', '\Processor' => '']);

        return snake_case($action);
    }

    protected function processGatewayResponse()
    {
        if ($this->gatewayResponse->isSuccess() === false)
        {
            return $this->handleGatewayFailure();
        }

        // If there if there is next request, we will handle that
        if ($this->gatewayResponse->hasRequest())
        {
            return $this->generateNextRequest();
        }

        // Else we will consider it to be a success response
        return $this->handleGatewaySuccess();
    }

    public function handleGatewaySuccess()
    {
        $method = $this->action . 'Success';

        if (method_exists($this, $method))
        {
            return $this->{$method}($this->gatewayResponse->data()->toArray());
        }

        throw new LogicException('Gateway response processor not found.', null , [
            'entity'    => $this->entity,
            'action'    => $this->action,
            'suffix'    => 'Success',
        ]);
    }

    public function handleGatewayFailure()
    {
        return [];
    }

    public function generateNextRequest()
    {
        $response = [
            'version'   => 'v1',
            'type'      => $this->gatewayResponse->requestType(),
            'request'   => $this->gatewayResponse->request(),
            'callback'  => $this->generateNextRequestCallback(),
        ];

        return $response;
    }

    protected function generateNextRequestCallback()
    {
        return [
            'action'    => $this->action,
            'input'     => $this->callbackInput->toArray(),
            'gateway'   => $this->gatewayResponse->requestCallback(),
        ];
    }

    /**
     * Modifies the gateway input to gateway compatible objects
     *
     * @param ArrayBag $input
     */
    protected function modifyGatewayInput(ArrayBag $input)
    {
        $input->transform(function($item){

            if (is_object($item) === true)
            {
                if ($item instanceof Entity)
                {
                    return $item->toArrayBag();
                }
                else if (($item instanceof ArrayBag) === false)
                {
                    throw new LogicException('Could not handle class in gateway input', null, [
                        'class' => get_class($item)
                    ]);
                }
            }

            return $item;
        });
    }
}
