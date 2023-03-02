<?php

namespace RZP\Models\P2p\Base;

use Crypt;
use phpDocumentor\Reflection\Types\This;
use RZP\Trace\TraceCode;
use RZP\Models\P2p\Device;
use RZP\Exception\LogicException;
use RZP\Gateway\P2p\Base\Response;
use RZP\Exception\P2p\ProcessorException;
use RZP\Models\P2p\Base\Libraries\Context;
use RZP\Models\P2p\Base\Libraries\ArrayBag;
use RZP\Exception\P2p\GatewayErrorException;
use RZP\Models\P2p\Base\Traits\ApplicationTrait;
use RZP\Models\P2p\Base\Metrics\GatewayActionMetric;

/**
 * @property Core $core
 * @property Entity $entity
 * @property Validator $validator
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

    /**
     * @var ProcessorException
     */
    private $exception = null;

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
            $this->validator->withContext($this->context()->getContextType())->validateInput($action, $input);
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

    /******************************* Exception Functions *****************************/

    /**
     * Method set the processor exception in object,
     *  1. Can not be overridden,
     *  2. Can not be accessed from any where other than Processor
     * @param ProcessorException $exception
     */
    final protected function setException(ProcessorException $exception)
    {
        $this->exception = $exception;
    }

    final protected function hasException(): bool
    {
        return ($this->exception instanceof ProcessorException);
    }

    final protected function getActualException()
    {
        return $this->exception->getActual();
    }

    protected function getTracableException()
    {
        if ($this->hasException() === true)
        {
            return [
                'class'     => get_class($this->getActualException()),
                'code'      => $this->getActualException()->getCode(),
                'message'   => $this->getActualException()->getMessage(),
            ];
        }
    }

    /******************************* COMMON ACTIONS *****************************/

    public function fetchAll(array $input): array
    {
        $this->initialize(Action::FETCH_ALL, $input);

        $entities = $this->core->fetchAll($input);

        return $entities->toArrayPublic();
    }

    public function fetch(array $input): array
    {
        $this->initialize(Action::FETCH, $input);

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
        $entity      = $this->getEntity();
        $mode        = $this->mode();

        // These two variables are passed directly to gateway and only gateway can validate these
        $this->gatewayInput->put('sdk', $this->arrayBag($this->input->get('sdk', [])));
        $this->gatewayInput->put('callback', $this->arrayBag($this->input->get('callback', [])));

        // Before passing input to gateway we will run basic check
        $this->modifyGatewayInput($this->gatewayInput);

        $traceInput = $this->input->toArray();

        // unsetting jwt token from traces in request
        unset($traceInput['request']['headers']['x-passport-jwt-v1']);

        $this->trace()->info(TraceCode::P2P_REQUEST, [
            'action'    => $this->action,
            'entity'    => $entity,
            'gateway'   => $gateway,
            'input'     => $this->redactForAction($traceInput),
        ]);

        // We are using context directly to pass to gateway. This is experimental and may change in future.
        // We might need to reverse the logic where context will be put inside gateway input.
        $this->context()->setGatewayData($gateway, $this->action, $this->gatewayInput);

        try
        {
            // In spite of passing the gateway data, we are passing complete context object
            $this->gatewayResponse = $this->app['gateway']->call($gateway, $entity, $this->context(), $mode);
        }
        catch (\Throwable $e)
        {
            $this->pushGatewayActionMetric($this->context(), true);

            throw $e;
        }

        return $this->processGatewayResponse();
    }

    protected function getGateway()
    {
        return $this->context()->getHandle()->getAcquirer();
    }

    protected function getEntity()
    {
        $action = strtr(static::class, ['RZP\Models\P2p\\' => '', '\Processor' => '']);

        return snake_case($action);
    }

    protected function processGatewayResponse()
    {
        if ($this->gatewayResponse->isSuccess() === false)
        {
            $response = $this->handleGatewayFailure();
        }
        else if ($this->gatewayResponse->hasRequest())
        {
            // If there if there is next request, we will handle that
            $response = $this->generateNextRequest();
        }
        else
        {
            // Else we will consider it to be a success response
            $response = $this->handleGatewaySuccess();
        }

        // Even if there is exception and no response we would want to trace that
        $this->trace()->info(TraceCode::P2P_RESPONSE, [
            'action'    => $this->action,
            'entity'    => $this->getEntity(),
            'gateway'   => $this->getGateway(),
            'exception' => $this->getTracableException(),
            'response'  => $this->redactForAction($response),
        ]);

        $this->pushGatewayActionMetric($this->context(),$this->gatewayResponse->isSuccess() === false);

        // The exception can only be set from handleGatewayFailure or specific failure/success handler
        if ($this->hasException() === true)
        {
            throw $this->getActualException();
        }

        return $response;
    }

    public function handleGatewaySuccess(): array
    {
        $action = $this->action . 'Success';

        return $this->processAction($action, $this->gatewayResponse->data()->toArray());
    }

    public function processAction(string $action, array $input, Device\Entity $device = null): array
    {
        if (method_exists($this, $action))
        {
            if (is_null($device) === false)
            {
                $this->context()->setDevice($device);
            }

            return $this->{$action}($input);
        }

        throw new LogicException('Gateway response processor not found.', null , [
            'action'    => $action,
            'suffix'    => 'Success',
        ]);
    }

    public function handleGatewayFailure(): array
    {
        $error = $this->gatewayResponse->error();

        $exception = new GatewayErrorException($error->get(Response::CODE),
                                               $error->get(Response::GATEWAY_CODE,
                                               $error->get(Response::DESCRIPTION)));

        $this->setException(new ProcessorException($exception));

        $action = $this->action . 'Failure';

        if (method_exists($this, $action))
        {
            return $this->{$action}($this->gatewayResponse->data()->toArray());
        }

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

    /**
     * Redact the input against a given action
     * @param array $input
     * @return array
     */
    protected function redactForAction(array $input)
    {
        $rules = $this->getNewAction()->getRedactRules($this->action);

        if (empty($rules) === true)
        {
            return $input;
        }

        $output = $this->redactNested($input, $rules);

        return $output;
    }

    protected function redactNested(array $input, $rules)
    {
        $output = $input;

        foreach ($rules as $key => $rule)
        {
            if (isset($input[$key]) === false)
            {
                continue;
            }

            $existing = $input[$key];

            // String, numeric, bool and null
            if (is_scalar($rule) === true)
            {
                $value = '[redacted]';

                // Disabling verbose for now
                //            if ($rule === 'verbose')
                //            {
                //                $size  = is_scalar($input[$key]) ? strlen($input[$key]) : sizeof($input[$key]);
                //                $value = gettype($input[$key]) . '|' . $size;
                //            }

                $output[$key] = $value;

                continue;
            }

            if (is_array($rule))
            {
                if (is_array($existing) === false)
                {
                    if ($existing instanceof ArrayBag)
                    {
                        $existing = $existing->toArray();
                    }
                    else
                    {
                        // Should never happen
                        continue;
                    }
                }

                $output[$key] = $this->redactNested($existing, $rule);
            }
        }

        return $output;
    }

    /**
     * Metrics to be sent for gateway actions
     * @param Context $context To extract fields for metrics like os, sdk_version etc
     * @param bool $isFailure  Resembles if it is a failure metric
     */
    protected function pushGatewayActionMetric(Context $context, bool $isFailure)
    {
        $gatewayActionMetric = new GatewayActionMetric($context);

        $gatewayActionMetric
            ->setGateway($this->getGateway())
            ->setEntity($this->getEntity())
            ->setAction($this->action)
            ->statusSuccess()
            ->typeProcessed();

        /**
         * We are checking for both failure, one explicit failure (when exception happens),
         * and When gateway response has the failure
         */
        if ($isFailure or ($this->gatewayResponse->isSuccess() === false))
        {
            $gatewayActionMetric
                ->statusFailed()
                ->pushCount();

            return;
        }

        if ($this->gatewayResponse->hasRequest())
        {
            $gatewayActionMetric->typeNext();
        }

        $gatewayActionMetric->pushCount();
    }
}
