<?php

namespace RZP\Services\UpiPayment;

use App;
use RZP\Exception;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Constants\Entity;
use RZP\Gateway\Upi\Base;
use RZP\Gateway\Base\Verify;
use RZP\Models\Base\PublicEntity;
use RZP\Gateway\Base\VerifyResult;
use Razorpay\Trace\Logger as Trace;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;
use GuzzleHttp\Psr7\Request as Psr7Request;
use Psr\Http\Client\NetworkExceptionInterface;

/**
 * Service implements the UPI Payments service client
 */
class Service
{
    /**
     * App container
     *
     * @var mixed
     */
    protected $app;

    /**
     * App mode
     * Live / Test
     *
     * @var string
     */
    protected $mode;

    /**
     * Used for tracing
     *
     * @var mixed
     */
    protected $trace;

    /**
     * Application Config
     *
     * @var array
     */
    protected $config;

    /**
     * Stores the current Action
     *
     * @var string
     */
    protected $action;

    /**
     * Stores the current gateway
     *
     * @var string
     */
    protected $gateway;

    /**
     * Stores the received input
     *
     * @var array
     */
    protected $input;

    const MAX_RETRY = 1;

    const METADATA = 'metadata';

    const TPV = 'tpv';

    const PRE_PROCESS = 'pre_process';

    const ENTITY_FETCH = 'entity_fetch';

    const RECON_ENTITY_UPDATE = 'recon_entity_update';

    const MULTIPLE_ENTITY_FETCH = 'multiple_entity_fetch';

    const TRANSACTION_UPSERT = 'transaction_upsert';

    const VALIDATE_VPA = 'validate_vpa';

    const DASHBOARD_ENTITY_FETCH = 'dashboard_entity_fetch';

    const DASHBOARD_MULTIPLE_ENTITY_FETCH = 'dashboard_multiple_entity_fetch';

    /**
     * Initiates the app container, trace and UPS config
     */
    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        if (isset($this->app['rzp.mode']))
        {
            $this->mode = $this->app['rzp.mode'];
        }

        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.upi_payment_service');
    }

    /**
     * action handles all the action based payment requests
     *
     * @param  string $action
     * @param  array  $input
     */
    public function action(string $action, array $input, string $gateway)
    {
        $this->action = $action;

        $this->input = $input;

        $this->gateway = $gateway;

        $request = $this->getRequest($input);

        list($response, $code) = $this->sendRequest($request);

        $serviceResponse = $this->processResponse($response, $code);

        return $serviceResponse;
    }
    /**
     * preProcessServerCallback handles the pre processing of callback through UPS
     *
     * @param  array|string $input
     * @param  string $gateway
     * @return array
     */
    public function preProcessServerCallback($input, string $gateway)
    {
        $gatewayData = [
            'payload' => $input,
            'gateway' => $gateway,
        ];

        $input = [
            'gateway' => $gatewayData,
        ];

        $this->addTerminalToServerCallback($input, $gateway);

        return $this->action(self::PRE_PROCESS, $input, $gateway);
    }

    /** Updates the gateway entity attributes during recon flow
     * @param $gatewayData
     * @return array|mixed|null
     */
    public function updateReconGatewayData(array $gatewayData)
    {
        $gateway = $gatewayData['gateway'];

        return $this->action(self::RECON_ENTITY_UPDATE, $gatewayData, $gateway);
    }

    /**
     * add terminal details to server callback request
     *
     * @param array $input
     * @param string $gateway
     * @return void
     */
    protected function addTerminalToServerCallback(array &$input, string $gateway)
    {
        if ($this->isTerminalRequiredForPreProcess($gateway) === false)
        {
            return;
        }

        $terminalData = $this->getTerminalDataFromServerCallback($gateway, $input);

        $terminal = $this->app['repo']->terminal->findByGatewayAndTerminalData($gateway,
        $terminalData, false, Mode::LIVE);

        if (empty($terminal) === true)
        {
            throw new Exception\RuntimeException(
                'No terminal found',
                [
                    'input'     => $input,
                    'action'    => self::PRE_PROCESS,
                    'gateway'   => $gateway,
                ],
                null,
                ErrorCode::SERVER_ERROR_NO_TERMINAL_FOUND);
        }

        $input['terminal'] = $terminal->toArrayWithPassword();
    }

    /**
     * returns terminal data to of a gateway from server callback
     *
     * @param string $gateway
     * @param array $input
     * @return array
     */
    protected function getTerminalDataFromServerCallback(string $gateway, array $input): array
    {
        switch ($gateway)
        {
            case Payment\Gateway::UPI_AIRTEL:
                return $this->getTerminalDataFromAirtelServerCallback($input);
            default:
                throw new Exception\LogicException(
                    'terminal data extraction not defined for gateway',
                    null,
                    [
                        Base\Entity::ACTION => $this->action,
                        'gateway'           => $gateway
                    ]);
        }
    }

    /**
     * returns terminal data from airtel server callback
     *
     * @param array $input
     * @return array
     */
    protected function getTerminalDataFromAirtelServerCallback(array $input): array
    {
        $payload = $input['gateway']['payload'];

        $data = json_decode($payload, true);

        $terminalData = [
            'gateway' => Payment\Gateway::UPI_AIRTEL,
        ];

        if ((empty($data['gateway_merchant_id']) === true) and
            (empty($data['payeeVPA']) === true))
        {
            $exception = new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_REQUEST_BODY,
                null,
                $data,
                'payload does not contain required keys - gateway_merchant_id and payeeVPA.');

            $this->trace->traceException($exception);

            throw $exception;
        }

        if (isset($data['payeeVPA']) === true)
        {
            $terminalData['gateway_merchant_id2'] = $data['payeeVPA'];
        }

        if (isset($data['gateway_merchant_id']) === true)
        {
            $terminalData['gateway_merchant_id'] = $data['gateway_merchant_id'];
        }

        return $terminalData;
    }

    /**
     * checks if terminal data is required for callback pre-processing for certain gateway
     *
     * @param string $gateway
     * @return boolean
     */
    protected function isTerminalRequiredForPreProcess(string $gateway)
    {
        // gateways which require terminal details for pre-processing of callback
        $gateways = [
            Payment\Gateway::UPI_AIRTEL,
        ];

        return (in_array($gateway, $gateways, true) === true);
    }

    /**
     * getRequest returns the request for UPS
     *
     * @param  array $input
     * @return RequestInterface
     */
    protected function getRequest(array $input): RequestInterface
    {
        $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

        $domain = $this->config['url'][$mode];

        $content = $this->buildRequestBody($input);

        $request = [
            Request::URL        => $domain . $this->getUri(),
            Request::METHOD     => Request::POST,
            Request::CONTENT    => $content,
        ];

        // trace the request
        $this->traceRequest($request);

        // Set the headers
        $headers = $this->getRequestHeaders();

        // Get body
        $body = $this->arrayToJsonString($request[Request::CONTENT]);

        $req = new Psr7Request($request[Request::METHOD], $request[Request::URL], $headers, $body);

        return $req;
    }

    /**
    * buildRequestBody builds the request body for UPS
    *
    * @param  array $input
    * @return array
    */
    protected function buildRequestBody(array $input): array
    {
        $data = [];

        switch ($this->action)
        {
            case Payment\Action::AUTHORIZE:
                $data = $this->getRequestBodyForAuthorize($input);
                break;
            case self::PRE_PROCESS:
                $data = [
                    'data'      => $input,
                    'gateway'   => $this->gateway,
                ];
                break;
            case Payment\Action::CALLBACK:
                $data = [
                    'data'      => $input['gateway'],
                    'gateway'   => $input['payment']['gateway'],
                ];
                break;
            case Payment\Action::VERIFY:
            case Payment\Action::AUTHORIZE_FAILED:
                $this->convertInputToArray($input);
                $data = [
                    'data'      => $input,
                    'gateway'   => $input['payment']['gateway'],
                    'action'    => $this->action,
                ];
                break;
            case self::ENTITY_FETCH:
            case self::MULTIPLE_ENTITY_FETCH:
                $data = $input;
                break;
            case self::RECON_ENTITY_UPDATE:
                $data = $input;
                break;
            case Payment\Action::FORCE_AUTHORIZE_FAILED:
                $this->convertInputToArray($input);
                $data = [
                    'data'      => $input,
                    'gateway'   => $input['payment']['gateway'],
                    'action'    => $this->action,
                ];
                break;
            case self::TRANSACTION_UPSERT:
                $data = $input;
                break;
            case self::VALIDATE_VPA:
                $data = [
                    "vpa" => $input[Payment\Entity::VPA],
                ];
                break;
            case self::DASHBOARD_ENTITY_FETCH:
            case self::DASHBOARD_MULTIPLE_ENTITY_FETCH:
                $data = $input;
                break;
            default:
                throw new Exception\LogicException(
                    'No supported actions found for UPS',
                    null,
                    [Base\Entity::ACTION => $this->action]);
        }

        return $data;
    }

    /**
     * Sends request to UPS and parse response
     *
     * @param  RequestInterface $request
     * @return array
     */
    protected function sendRequest(RequestInterface $request): array
    {
        $response = $this->sendRawRequest($request);

        return $this->parseResponse($response);
    }

    /**
     * sends a request to UPS
     *
     * @param  array $data
     * @return ResponseInterface
     */
    protected function sendRawRequest(RequestInterface $request): ResponseInterface
    {
        $retryCount = 0;

        while(true)
        {
            try
            {
                $httpClient = Psr18ClientDiscovery::find();

                return $httpClient->sendRequest($request);
            }
            catch (\Exception $e)
            {
                if ($retryCount < self::MAX_RETRY)
                {
                    $this->trace->info(
                        TraceCode::UPI_PAYMENT_SERVICE_REQUEST_RETRY,
                        [
                            'message' => $e->getMessage(),
                        ]
                    );

                    $retryCount++;

                    continue;
                }

                $this->throwServerRequestException($e);
            }
        }
    }

    /**
     * returns the Request Body for Authorize action
     *
     * @param  string $action
     * @param  array  $input
     * @return array
     */
    protected function getRequestBodyForAuthorize(array $input): array
    {
        if ($input[Entity::MERCHANT]->isTPVRequired() === true)
        {
            if (isset($input[self::METADATA]) === true)
            {
                $input[self::METADATA][self::TPV] = true;
            }
            else
            {
                $input[self::METADATA] = [
                    self::TPV => true
                ];
            }
        }

        $this->convertInputToArray($input);

        $content = [
            Entity::PAYMENT     => $input[Entity::PAYMENT] ?? null,
            self::METADATA      => $input[self::METADATA] ?? null,
            Entity::TERMINAL    => $input[Entity::TERMINAL] ?? null,
            Entity::MERCHANT    => $input[Entity::MERCHANT] ?? null,
            Base\Entity::ACTION => Payment\Action::AUTHORIZE,
        ];

        if (isset($input[Entity::ORDER]) === true)
        {
            $content[Entity::ORDER] = $input[Entity::ORDER];
        }

        return $content;
    }

    /**
     * throws Server exception in case of request failures
     *
     * @param  \WpOrg\Requests\Exception $e
     * @return void
     */
    protected function throwServerRequestException(\Exception $e)
    {
        $errorCode = ErrorCode::SERVER_ERROR_UPI_PAYMENT_SERVICE_REQUEST_ERROR;

        if ($e instanceof NetworkExceptionInterface)
        {
            $errorCode = ErrorCode::SERVER_ERROR_UPI_PAYMENT_SERVICE_REQUEST_TIMEOUT;
        }

        $this->trace->traceException(
            $e,
            Trace::CRITICAL,
            TraceCode::UPI_PAYMENT_SERVICE_REQUEST_ERROR);

        throw new Exception\ServerErrorException($e->getMessage(), $errorCode);
    }

    /**
     * Process the response received from UPS
     *
     */
    protected function processResponse($response, $code)
    {
        $this->traceResponse($response);

        $this->checkForErrors($response, $code);

        switch ($this->action)
        {
            case Payment\Action::AUTHORIZE:
                if (isset($response[Response::DATA][Response::DATA]) === false)
                {
                    throw new Exception\LogicException(
                        'data should be present in successful authorize response.',
                        null,
                        ['response' => $response]);
                }

                return $response[Response::DATA];
            case self::PRE_PROCESS:
                return $response[Response::DATA];
            case Payment\Action::CALLBACK:
                return $this->processCallbackResponse($response);
            case Payment\Action::VERIFY:
            case Payment\Action::AUTHORIZE_FAILED:
                return $this->processVerifyResponse($response);
            case self::ENTITY_FETCH:
                return $this->processEntityFetchResponse($response);
            case self::MULTIPLE_ENTITY_FETCH:
                return $this->processMultipleEntityFetchResponse($response);
            case self::RECON_ENTITY_UPDATE:
                return $response[Response::DATA];
            case Payment\Action::FORCE_AUTHORIZE_FAILED:
                return $response[Response::DATA];
            case self::TRANSACTION_UPSERT:
                return $response[Response::DATA];
            case self::VALIDATE_VPA:
                return $this->processValidateVpaResponse($response);
            case self::DASHBOARD_ENTITY_FETCH:
            case self::DASHBOARD_MULTIPLE_ENTITY_FETCH:
                return $response;
            default:
                throw new Exception\LogicException(
                    'No supported actions found for UPS',
                    null,
                    ['action' => $this->action]);
        }
    }

    protected function processEntityFetchResponse(array $response): array
    {
        $entity = $response[Response::ENTITY] ?? null;

        if (empty($entity) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND,
                null,
                ['response' => $response],
                'no record found for entity fetch response');
        }

        return $entity;
    }

    protected function processMultipleEntityFetchResponse(array $response): array
    {
        $entities = $response[Response::ENTITIES] ?? null;

        if (empty($entities) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND,
                null,
                ['response' => $response],
                'no record found for entity fetch response');
        }

        return $entities;
    }

    /**
     * processes the callback response
     *
     * @param array $response
     * @return array
     */
    protected function processCallbackResponse(array $response): array
    {
        $data = $response[Response::DATA] ?? null;

        if ((isset($response[Response::DATA]) === false) or
            (isset($data[Payment\Entity::AMOUNT_AUTHORIZED]) === false))
        {
            throw new Exception\LogicException(
                'received invalid callback response',
                null,
                ['response' => $response]);
        }

        $data[Payment\Entity::AMOUNT_AUTHORIZED] = (int) $data[Payment\Entity::AMOUNT_AUTHORIZED];

        return $data;
    }

    /**
     * processes the validate vpa response
     *
     * @param array $response
     * @return array
     */
    protected function processValidateVpaResponse(array $response): array
    {
        if($response['success'] === true)
        {
            return [
                'vpa'               => $response['vpa'],
                'success'           => $response['success'],
                // mask customer name
                'customer_name'     => mask_by_percentage($response['customer_name'], 0.9),
            ];
        }
        return [];
    }

    /**
     * Check for response errors
     *
     * @param  array   $response
     * @param  integer $code
     * @return void
     */
    protected function checkForErrors(array $response, int $code)
    {
        if ($code === 200)
        {
            // Verify error is handled separately.
            if ($this->action == Payment\Action::VERIFY)
            {
                return;
            }

            $this->checkGatewayFailure($response);
        }
        else if ($code >= 400 and $code < 500)
        {
            $error = $response['details'][0];

            $description = $error['internal']['description'];

            if ($this->action === Payment\Action::VALIDATE_VPA)
            {
                $description = null;
            }

            throw new Exception\BadRequestException(
                $error['internal']['code'],
                null,
                $error,
                $description);
        }
        else if ($code >= 500)
        {
            throw new Exception\ServerErrorException(
                $response['error'],
                ErrorCode::SERVER_ERROR_UPI_PAYMENT_SERVICE_FAILURE);
        }
    }

    protected function checkGatewayFailure($response)
    {
        $error = $response['error'] ?? null;

        if ((isset($error) === false) or
            (empty($error) === true))
        {
            return;
        }

        // Add processing for Mozart Gateway failures
        $metadata = $error['internal']['metadata'];

        $internalErrorCode = $metadata['internal_error_code'];
        $description      = $metadata['description'];
        $gatewayErrorCode = $metadata['gateway_error_code'];
        $gatewayErrorDesc = $metadata['gateway_error_description'];
        $httpCode = $metadata['http_code'] ?? null;

        if ($httpCode !== null)
        {
            $httpCode = (int) ($httpCode);
            if ($httpCode === 400)
            {
                throw new Exception\BadRequestException(
                    $internalErrorCode,
                    null,
                    $error,
                    $description);
            }
            elseif ($httpCode === 500)
            {
                throw new Exception\ServerErrorException(
                    'received 500 from client',
                    $internalErrorCode);
            }
        }

        // We do not process gateway failures for pre-process
        if ($this->action === self::PRE_PROCESS)
        {
            return;
        }

        throw new Exception\GatewayErrorException(
            $internalErrorCode,
            $gatewayErrorCode,
            $gatewayErrorDesc,
            [],
            null,
            $this->action);
    }

    /**
     * Process the response received from UPS for verify action
     *
     * @param  array $response
     * @return array
     */
    protected function processVerifyResponse(array $response): array
    {
        $verify = new Verify($this->gateway, []);

        $verify->setVerifyResponseBody($response);

        $verify->setVerifyResponseContent($response[Response::DATA]);

        $verify->setStatus(VerifyResult::STATUS_MATCH);

        $this->setGatewaySuccess($verify);

        $this->setApiSuccess($verify);

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $verify->setStatus(VerifyResult::STATUS_MISMATCH);
        }

        if ($verify->gatewaySuccess === true)
        {
            $payment = $response[Response::DATA][Response::DATA][Entity::PAYMENT];

            $amountAuthorized = (int) $payment[Payment\Entity::AMOUNT_AUTHORIZED];
            $currency = $payment[Payment\Entity::CURRENCY];

            $verify->setAmountMismatch(
                $amountAuthorized !== $this->input[Entity::PAYMENT][Payment\Entity::AMOUNT]
            );

            $verify->setCurrencyAndAmountAuthorized(
                $currency,
                $amountAuthorized
            );
        }

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH);

        $this->setVerifyError($response, $verify);

        if ($this->action === Payment\Action::AUTHORIZE_FAILED)
        {
            return $this->processAuthorizeFailedPayment($verify, $response);
        }

        $this->verifyPayment($verify);

        return $verify->getDataToTrace();
    }

    /**
     * set verify error
     *
     * @param array $response
     * @param Verify $verify
     * @return void
     */
    protected function setVerifyError($response, Verify $verify)
    {
        // return if gateway success is true
        if ($verify->gatewaySuccess === true)
        {
            return;
        }

        try
        {
            $this->checkGatewayFailure($response);
        }
        catch (\Exception $e)
        {
            // we set error only if it is error received from gateway
            if ($e instanceof Exception\GatewayErrorException)
            {
                $verify->error = $e->getError()->getAttributes();
            }
        }
    }

    /**
     * processes Authorize failed payments
     *
     * @param  Verify $verify
     * @param  array  $response
     * @return array
     */
    protected function processAuthorizeFailedPayment(Verify $verify, array $response)
    {
        $e = null;

        try
        {
            $this->verifyPayment($verify);
        }
        catch (Exception\PaymentVerificationException $e)
        {
            $this->trace->info(
                TraceCode::PAYMENT_FAILED_TO_AUTHORIZED,
                [
                    'message'    => 'Payment verification failed. Now converting to authorized',
                    'payment_id' => $this->input[Entity::PAYMENT][Payment\Entity::ID]
                ]);
        }

        if ($e === null)
        {
            throw new Exception\LogicException(
                'When converting failed payment to authorized, payment verification ' .
                'should have failed but instead it did not',
                null,
                $this->input[Entity::PAYMENT]);
        }

        if (($verify->apiSuccess === false) and
            ($verify->gatewaySuccess === true))
        {
            return $this->getAuthorizeFailedResponse($verify, $response);
        }

        throw new Exception\LogicException(
            'Should not have reached here',
            null,
            ['payment' => $this->input['payment']]);
    }

    /**
     * Verifies a payment
     *
     * @param  Verify $verify
     */
    protected function verifyPayment($verify)
    {
        if (($verify->amountMismatch === true) and
            ($verify->throwExceptionOnMismatch))
        {
            if (($this->gateway === Payment\Gateway::UPI_ICICI) and
                ($verify->gatewaySuccess === true))
            {
                throw new Exception\PaymentVerificationException(
                    $verify->getDataToTrace(),
                    $verify);
            }

            throw new Exception\RuntimeException(
                'Payment verification failed due to amount mismatch.',
                [
                    'payment_id' => $this->input[Entity::PAYMENT][Payment\Entity::ID],
                    'gateway'    => $this->gateway
                ]);
        }

        if (($verify->match === false) and
            ($verify->throwExceptionOnMismatch))
        {
            throw new Exception\PaymentVerificationException(
                $verify->getDataToTrace(),
                $verify);
        }
    }

    public function findByPaymentIdAndGatewayOrFail(string $paymentId, string $gateway, array $requiredFields)
    {
        $input = [
            Request::MODEL              => Payment\Action::AUTHORIZE,
            Request::COLUMN_NAME        => Request::PAYMENT_ID,
            Request::REQUIRED_FIELDS    => $requiredFields,
            Request::VALUE              => $paymentId,
            Request::GATEWAY            => $gateway
        ];

        return $this->action(self::ENTITY_FETCH, $input, $gateway);
    }

    /**
     * sets gateway status in verify object
     *
     * @param  Verify $verify
     */
    protected function setGatewaySuccess(Verify $verify)
    {
        $body = $verify->verifyResponseBody;

        $isSuccess = $body[Response::DATA]['success'] ?? false;

        $verify->gatewaySuccess  = $isSuccess;
    }

    /**
     * sets api payment status in verify object
     *
     * @param  Verify $verify
     */
    protected function setApiSuccess(Verify $verify)
    {
        $verify->apiSuccess = true;

        $apiStatus = $this->input[Entity::PAYMENT][Payment\Entity::STATUS];

        if (($apiStatus === Payment\Status::FAILED) or
            ($apiStatus === Payment\Status::CREATED))
        {
            $verify->apiSuccess = false;
        }
    }

    /**
     * Returns authorize failed response
     *
     * @param $verify
     * @return array
     * @throws Exception\LogicException
     */
    protected function getAuthorizeFailedResponse(Verify $verify, array $response): array
    {
        $returnResponse = [];

        $data = $response[Response::DATA][Response::DATA];

        $acquirer[Payment\Entity::VPA]  = $data[Entity::UPI][Base\Entity::VPA] ?? '';
        $acquirer[Payment\Entity::REFERENCE16] = $data[Entity::UPI][Base\Entity::NPCI_REFERENCE_ID] ?? '';

        $returnResponse['acquirer'] = $acquirer;

        if ($verify->amountMismatch === true)
        {
            $returnResponse[Payment\Entity::CURRENCY]             = $verify->currency;
            $returnResponse[Payment\Entity::AMOUNT_AUTHORIZED]    = $verify->amountAuthorized;
        }

        return $returnResponse;
    }

    /**
     * Traces the response received from UPS
     *
     * @param  mixed $response
     * @return void
     */
    protected function traceResponse($response)
    {
        // TODO: Add Action based tracing and response redaction
        $this->trace->info(TraceCode::UPI_PAYMENT_SERVICE_RESPONSE, [
            'response' => $response,
            'action'   => $this->action
        ]);
    }

    /**
     * Traces the request sent to UPS
     *
     * @param  mixed $request
     * @return void
     */
    protected function traceRequest(array $request)
    {
        // Default request trace data
        $traceData = [
            Request::URL        => $request[Request::URL],
            Request::METHOD     => $request[Request::METHOD],
        ];

        switch ($this->action)
        {
            case Payment\Action::AUTHORIZE:
                $traceData += $this->getAuthorizeTraceData($request[Request::CONTENT]);
                break;
            case self::PRE_PROCESS:
                $traceData += $this->getPreProcessTraceData($request[Request::CONTENT]);
                break;
            case Payment\Action::CALLBACK:
                $traceData += $request[Request::CONTENT];
                break;
            case Payment\Action::VERIFY:
            case Payment\Action::AUTHORIZE_FAILED:
                $traceData += $this->getVerifyTraceData($request[Request::CONTENT]);
                break;
            case self::ENTITY_FETCH:
            case self::MULTIPLE_ENTITY_FETCH:
            case self::DASHBOARD_ENTITY_FETCH:
            case self::DASHBOARD_MULTIPLE_ENTITY_FETCH:
                $traceData += $request[Request::CONTENT];
                break;
            case self::RECON_ENTITY_UPDATE:
                $traceData += $request[Request::CONTENT];
                break;
            case Payment\Action::FORCE_AUTHORIZE_FAILED:
                $traceData += $this->getForceAuthorizedTraceData($request[Request::CONTENT]);
                break;
            case self::TRANSACTION_UPSERT:
                $traceData += $request[Request::CONTENT];
                break;
            case self::VALIDATE_VPA:
                $traceData += $this->getValdiateVpaTraceData($request[Request::CONTENT]);
                break;
            default:
                throw new Exception\LogicException(
                    'No supported actions found for UPS',
                    null,
                    [Base\Entity::ACTION => $this->action]);
        }

        $this->trace->info(TraceCode::UPI_PAYMENT_SERVICE_REQUEST, $traceData);
    }

    /**
     * Returns trace data for pre-process request
     *
     * @param array $content
     * @return array
     */
    protected function getPreProcessTraceData(array $content): array
    {
        $data = $content[Response::DATA];

        $traceData['gateway'] = $data['gateway'];

        if (isset($data[Entity::TERMINAL]) === true)
        {
            $terminal = $data[Entity::TERMINAL];

            $traceData[Entity::TERMINAL] = [
                Payment\Entity::ID      => $terminal[Payment\Entity::ID],
                payment\Entity::GATEWAY => $terminal[Payment\Entity::GATEWAY]
            ];
        }

        return $traceData;
    }

    /**
     * Returns all the required header for sending request to UPS
     *
     * @return array
     */
    protected function getRequestHeaders(): array
    {
        $authString = 'Basic '. base64_encode($this->config['username'] . ':' .  $this->config['password']);

        $headers = [
            Request::CONTENT_TYPE_HEADER      => Request::APPLICATION_JSON,
            Request::ACCEPT_HEADER            => Request::APPLICATION_JSON,
            Request::X_RAZORPAY_APP_HEADER    => 'api',
            Request::X_RAZORPAY_TASKID_HEADER => $this->app['request']->getTaskId(),
            Request::X_REQUEST_ID             => $this->app['request']->getId(),
            Request::X_RAZORPAY_TRACKID       => $this->app['req.context']->getTrackId(),
            Request::AUTH_HEADER              => $authString,
        ];

        $this->setTestingHeaders($headers);

        return $headers;
    }

    /**
     * Returns uri for request
     *
     * @return string
     */
    protected function getUri(): string
    {
        $version = 'v1';

        $action = $this->action;

        if ($action === Payment\Action::AUTHORIZE_FAILED)
        {
            $action = Payment\Action::VERIFY;
        }

        if ($action === self::RECON_ENTITY_UPDATE)
        {
            return sprintf('%s/recon/entity/update', $version);
        }
        if ($action === self::VALIDATE_VPA)
        {
            return sprintf('%s/vpa/validate', $version);
        }

        if ($action === self::TRANSACTION_UPSERT)
        {
            return sprintf('%s/transaction/upsert', $version);
        }

        if ($action === self::DASHBOARD_MULTIPLE_ENTITY_FETCH)
        {
            return sprintf('%s/dashboard/entity_fetch/multiple', $version);
        }

        if ($action === self::DASHBOARD_ENTITY_FETCH)
        {
            return sprintf('%s/dashboard/entity_fetch', $version);
        }

        return sprintf('%s/%s', $version, $action);
    }

    /**
     * Parses the response from UPS
     *
     * @param ResponseInterface $response
     * @return array
     */
    protected function parseResponse(ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();

        if ($statusCode === 404)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_URL_NOT_FOUND,
                null,
                [
                    'http_code' => $statusCode,
                ]
            );
        }
        else if ($statusCode === 401)
        {
            throw new Exception\AuthenticationException(
                ErrorCode::BAD_REQUEST_AUTHENTICATION_FAILED
            );
        }
        else if (($statusCode === 503) or ($statusCode === 502))
        {
            throw new Exception\ServerErrorException(
                'Upi Payments Service is not available',
                ErrorCode::SERVER_ERROR_SERVICE_UNAVAILABLE,
                [
                    'http_code' => $statusCode,
                ]
            );
        }

        $responseBody = json_decode($response->getBody(), true);

        if (empty($responseBody) === true)
        {
            throw new Exception\ServerErrorException(
                'received empty response from UPS',
                ErrorCode::SERVER_ERROR_INVALID_RESPONSE,
                [
                    'http_code' => $statusCode,
                    'response'  => $responseBody,
                ]
            );
        }

        return [$responseBody, $statusCode];
    }

    /**
     * get trace data for authorize action send to UPS
     *
     * @param  array $content
     * @return array
     */
    protected function getAuthorizeTraceData(array $content): array
    {
        $data = [
            Payment\Entity::GATEWAY    => $content[Entity::PAYMENT][Payment\Entity::GATEWAY] ?? null,
            Entity::PAYMENT     => [
                Payment\Entity::ID        => $content[Entity::PAYMENT][Payment\Entity::ID] ?? null,
                Payment\Entity::AMOUNT    => $content[Entity::PAYMENT][Payment\Entity::AMOUNT] ?? null,
                Payment\Entity::CURRENCY  => $content[Entity::PAYMENT][Payment\Entity::CURRENCY] ?? null,
                Payment\Entity::CPS_ROUTE => $content[Entity::PAYMENT][Payment\Entity::CPS_ROUTE] ?? null,
                Payment\Entity::VPA       => $content[Entity::PAYMENT][Payment\Entity::VPA] ?? null,
            ],
            Entity::MERCHANT   => [
                Merchant\Entity::BILLING_LABEL  => $content[Entity::MERCHANT][Merchant\Entity::BILLING_LABEL] ?? null,
            ],
        ];

        $data[self::METADATA] = $content[self::METADATA] ?? [];

        return $data;
    }

    /**
     * get trace data for verify action send to UPS
     *
     * @param  array $content
     * @return array
     */
    protected function getForceAuthorizedTraceData(array $content): array
    {
        $content = $content['data'];

        $data = [
            Payment\Entity::GATEWAY    => $content[Entity::PAYMENT][Payment\Entity::GATEWAY] ?? null,
            Entity::PAYMENT     => [
                Payment\Entity::ID        => $content[Entity::PAYMENT][Payment\Entity::ID] ?? null,
                Payment\Entity::AMOUNT    => $content[Entity::PAYMENT][Payment\Entity::AMOUNT] ?? null,
                Payment\Entity::CURRENCY  => $content[Entity::PAYMENT][Payment\Entity::CURRENCY] ?? null,
                Payment\Entity::CPS_ROUTE => $content[Entity::PAYMENT][Payment\Entity::CPS_ROUTE] ?? null,
                Payment\Entity::VPA       => mask_vpa($content[Entity::PAYMENT][Payment\Entity::VPA] ?? null),
            ],
            Entity::MERCHANT   => [
                Merchant\Entity::BILLING_LABEL  => $content[Entity::MERCHANT][Merchant\Entity::BILLING_LABEL] ?? null,
            ],
            Base\Entity::GATEWAY_DATA   => $content['gateway']
        ];

        return $data;
    }

    /**
     * get trace data for verify action send to UPS
     *
     * @param  array $content
     * @return array
     */
    protected function getVerifyTraceData(array $content): array
    {
        $content = $content['data'];

        $data = [
            Payment\Entity::GATEWAY    => $content[Entity::PAYMENT][Payment\Entity::GATEWAY] ?? null,
            Entity::PAYMENT     => [
                Payment\Entity::ID        => $content[Entity::PAYMENT][Payment\Entity::ID] ?? null,
                Payment\Entity::AMOUNT    => $content[Entity::PAYMENT][Payment\Entity::AMOUNT] ?? null,
                Payment\Entity::CURRENCY  => $content[Entity::PAYMENT][Payment\Entity::CURRENCY] ?? null,
                Payment\Entity::CPS_ROUTE => $content[Entity::PAYMENT][Payment\Entity::CPS_ROUTE] ?? null,
                Payment\Entity::VPA       => $content[Entity::PAYMENT][Payment\Entity::VPA] ?? null,
            ],
            Entity::MERCHANT   => [
                Merchant\Entity::BILLING_LABEL  => $content[Entity::MERCHANT][Merchant\Entity::BILLING_LABEL] ?? null,
            ],
        ];

        return $data;
    }

    /**
     * get trace data for validate vpa action send to UPS
     *
     * @param  array $content
     * @return array
     */
    protected function getValdiateVpaTraceData(array $content): array
    {
        return [
            Entity::VPA => mask_by_percentage($content['vpa'], 0.6)
        ];
    }

    /**
     * headers used for E2E testing
     *
     * @param  array $input
     */
    private function setTestingHeaders(array &$headers)
    {
        $testCaseID = $this->app['request']->header('X-RZP-TESTCASE-ID');

        if (empty($testCaseID) === false)
        {
            $headers[Request::X_RZP_TESTCASE_ID] = $testCaseID;
        }
    }

    /*********************************
     * Helpers
     **************************************/

    /**
     * converts the input object array to array
     *
     * @param  array $input
     * @return void
     */
    protected function convertInputToArray(array &$input)
    {
        if (empty($input[Entity::TERMINAL]) === false)
        {
            $input[Entity::TERMINAL] = $input[Entity::TERMINAL]->toArrayWithPassword();
        }

        foreach ($input as $key => $data)
        {
            if ((is_object($data) === true) and ($data instanceof PublicEntity))
            {
                $input[$key] = $data->toArray();
            }
        }
    }

    /**
     * Converts Array to Json string
     *
     * @param  array $data
     * @return string
     */
    protected function arrayToJsonString(array $data): string
    {
        $jsonEncodedData = json_encode($data);

        if (json_last_error() === JSON_ERROR_NONE)
        {
            return $jsonEncodedData;
        }

        throw new Exception\RuntimeException(
            json_last_error_msg(),
            ['array' => $data],
            null,
            ErrorCode::SERVER_ERROR_FAILED_TO_CONVERT_ARRAY_TO_JSON
        );
    }

    /**
     * fetchMultiple is used by dashboard to fetch multiple entities from UPS
     *
     * @param  array $input,
     * @param  string $entityName
     * @return array
     */
    public function fetchMultiple(string $entityName, array $input)
    {
        $this->trace->info(TraceCode::UPI_PAYMENT_SERVICE_DASHBOARD_MULTIPLE_ENTITY_FETCH_REQUEST, [$input, 'entity' => $entityName]);

        $actionInput = [
            Request::ENTITY_NAME    => $entityName,
            Request::COUNT          => $input[Request::COUNT],
            Request::SKIP           => $input[Request::SKIP],
            Request::FROM           => $input[Request::FROM],
            Request::TO             => $input[Request::TO],
        ];

        unset($input[Request::COUNT]);
        unset($input[Request::SKIP]);
        unset($input[Request::FROM]);
        unset($input[Request::TO]);
        unset($input[Request::INCLUDE_DELETED]);

        if(empty($input) === false)
        {
            $actionInput['filter'] = $input;
        }

        $response =  $this->action(self::DASHBOARD_MULTIPLE_ENTITY_FETCH, $actionInput,  'upi');

        $response['items'] = $response['entities'][$entityName];

        unset($response['entities']);

        return $response;
    }

    /**
     * fetch is called by dashboard to fetch single entity from UPS
     *
     * @param  array $input
     * @param  string $id
     * @param  string $entityName
     * @return array
     */
    public function fetch(string $entityName, string $id, array $input)
    {
        $this->trace->info(TraceCode::UPI_PAYMENT_SERVICE_DASHBOARD_ENTITY_FETCH_REQUEST, [$input, 'entity' => $entityName, 'id' => $id]);

        $input = [
            Request::ENTITY_NAME    =>$entityName,
            Request::ID             => $id
        ];

        $response = $this->action(self::DASHBOARD_ENTITY_FETCH, $input, 'upi');

        return $response['entity'];
    }
}
