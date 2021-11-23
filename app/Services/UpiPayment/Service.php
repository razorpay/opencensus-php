<?php

namespace RZP\Services\UpiPayment;

use App;
use RZP\Exception;
use RZP\Models\Payment;
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
use Http\Discovery\Psr18ClientDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;
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

    const MAX_RETRY = 2;

    const METADATA = 'metadata';

    const PRE_PROCESS = 'pre_process';

    /**
     * Initiates the app container, trace and UPS config
     */
    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.upi_payment_service');
    }

    /**
     * action handles all the action based payment requests
     *
     * @param  string $action
     * @param  array  $input
     * @return array
     */
    public function action(string $action, array $input, string $gateway) : array
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
        $this->action = self::PRE_PROCESS;

        $data = [
            'payload' => $input,
            'gateway' => $gateway,
        ];

        return $this->action(self::PRE_PROCESS, $data, $gateway);
    }

    /**
     * getRequest returns the request for UPS
     *
     * @param  array $input
     * @return RequestInterface
     */
    protected function getRequest(array $input): RequestInterface
    {
        $mode = $this->app['rzp.mode'];

        $domain = $this->config['url'][$mode];

        $content = $this->buildRequestBody($input);

        $uri = $this->action;

        if ($uri === Payment\Action::AUTHORIZE_FAILED)
        {
            $uri = Payment\Action::VERIFY;
        }

        $request = [
            Request::URL        => $domain . $uri,
            Request::METHOD     => Request::POST,
            Request::CONTENT    => $content,
        ];

        // trace the request
        $this->traceRequest($request);

        $requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $streamFactory = Psr17FactoryDiscovery::findStreamFactory();

        // Create a PSR-7 Request Object
        $req = $requestFactory->createRequest($request[Request::METHOD], $request[Request::URL]);

        // Set the headers
        $headers = $this->getRequestHeaders();
        foreach ($headers as $key => $value) {
            $req = $req->withHeader($key, $value);
        }

        // Set the body
        $body = $streamFactory->createStream($this->arrayToJsonString($request[Request::CONTENT]));
        $req = $req->withBody($body);

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
                $data = $input;
                break;
            case Payment\Action::CALLBACK:
                $data = [
                    'data'      => $input['gateway'],
                    'gateway'   => $input['payment']['gateway'],
                ];
                break;
            case Payment\Action::VERIFY:
            case Payment\Action::AUTHORIZE_FAILED:
                $data = [
                    'data'      => $input,
                    'gateway'   => $input['payment']['gateway'],
                ];
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
     * Sends the request to UPS
     *
     * @param  RequestInterface $request
     * @return array
     */
    protected function sendRequest(RequestInterface $request): array
    {
        $retryCount = 0;

        while(true)
        {
            try
            {
                $httpClient = Psr18ClientDiscovery::find();

                $response = $httpClient->sendRequest($request);

                $responseBody = json_decode($response->getBody(), true);

                $statusCode = $response->getStatusCode();

                return [$responseBody, $statusCode];
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
        $this->convertInputToArray($input);

        $content = [
            Entity::PAYMENT     => $input[Entity::PAYMENT] ?? null,
            self::METADATA      => $input[self::METADATA] ?? null,
            Entity::TERMINAL    => $input[Entity::TERMINAL] ?? null,
            Entity::MERCHANT    => $input[Entity::MERCHANT] ?? null,
            Base\Entity::ACTION => Payment\Action::AUTHORIZE,
        ];

        return $content;
    }

    /**
     * throws Server exception in case of request failures
     *
     * @param  \Requests_Exception $e
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
            Trace::WARNING,
            TraceCode::UPI_PAYMENT_SERVICE_REQUEST_ERROR);

        throw new Exception\ServerErrorException($e->getMessage(), $errorCode);
    }

    /**
     * Process the response received from UPS
     *
     * @return array
     */
    protected function processResponse($response, $code): array
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
                return $response['data'];
            case Payment\Action::CALLBACK:
                return $response;
            case self::PRE_PROCESS:
                return $response['data'];
            case Payment\Action::CALLBACK:
                return $response;
            case Payment\Action::VERIFY:
            case Payment\Action::AUTHORIZE_FAILED:
                return $this->processVerifyResponse($response);
            default:
                throw new Exception\LogicException(
                    'No supported actions found for UPS',
                    null,
                    ['action' => $this->action]);
        }
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
            // Verify error is handled seprately.
            if ($this->action == Payment\Action::VERIFY)
            {
                return;
            }

            $this->checkGatewayFailure($response);
        }
        else if ($code >= 400 and $code < 500)
        {
            $error = $response['details'][0];

            throw new Exception\BadRequestException(
                $error['internal']['code'],
                null,
                $error,
                $error['internal']['description']);
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
        if ($this->action === self::PRE_PROCESS)
        {
            // gateway error handling for pre-process will be handled else where
            return;
        }

        $error = $response['error'] ?? null;

        if ((isset($error) === false) or
            (empty($error) === true))
        {
            return;
        }

        // Add processing for Mozart Gateway failures
        $metadata = $error['internal']['metadata'];

        $internalErrorCode = $metadata['internal_error_code'];
        $gatewayErrorCode = $metadata['gateway_error_code'];
        $gatewayErrorDesc = $metadata['gateway_error_description'];

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
    protected function processVerifyResponse(array $response)
    {
        $verify = new Verify($this->gateway, []);

        $verify->setVerifyResponseBody($response);

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

            $verify->setAmountMismatch(
                $payment[Payment\Entity::AMOUNT_AUTHORIZED] !== $this->input[Entity::PAYMENT][Payment\Entity::AMOUNT]
            );

            $verify->setCurrencyAndAmountAuthorized(
                $payment[Payment\Entity::CURRENCY],
                $payment[Payment\Entity::AMOUNT_AUTHORIZED]
            );
        }

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH);

        if ($this->action === Payment\Action::AUTHORIZE_FAILED)
        {
            return $this->processAuthorizeFailedPayment($verify, $response);
        }

        $this->verifyPayment($verify);

        return $verify->getDataToTrace();
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

        return $this->getAuthorizeFailedResponse($verify, $response);
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
            throw new Exception\RuntimeException(
                'Payment amount verification failed.',
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

    /**
     * sets gateway status in verify object
     *
     * @param  Verify $verify
     * @return array
     */
    protected function setGatewaySuccess(Verify $verify)
    {
        $body = $verify->verifyResponseBody;

        $isSuccess = $body[Response::DATA]['success'];

        $verify->gatewaySuccess  = $isSuccess;
    }

    /**
     * sets api payment status in verify object
     *
     * @param  Verify $verify
     * @return array
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
    protected function getAuthorizeFailedResponse(Verify $verify, array $response)
    {
        $returnResponse = [];

        if (($verify->apiSuccess === false) and
            ($verify->gatewaySuccess === true))
        {
            $data = $response[Response::DATA][Response::DATA];

            $acquirer[Payment\Entity::VPA]  = $data[Entity::UPI][Base\Entity::NPCI_REFERENCE_ID];
            $acquirer[Payment\Entity::REFERENCE16] = $data[Entity::UPI][Base\Entity::NPCI_REFERENCE_ID];

            $returnResponse['acquirer'] = $acquirer;

            if ($verify->amountMismatch === true)
            {
                if ((is_string($verify->currency) === true) and
                    (is_integer($verify->amountAuthorized) === true))
                {
                    $returnResponse[Payment\Entity::CURRENCY]             = $verify->currency;
                    $returnResponse[Payment\Entity::AMOUNT_AUTHORIZED]    = $verify->amountAuthorized;
                }
                else
                {
                    throw new Exception\LogicException(
                        'For gateways with amountMismatch, currency and amountAuthorized are mandatory',
                        null,
                        ['payment' => $this->input['payment']]);
                }
            }

            return $returnResponse;
        }

        throw new Exception\LogicException(
            'Should not have reached here',
            null,
            ['payment' => $this->input['payment']]);
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
        $this->trace->info(TraceCode::UPI_PAYMENT_SERVICE_RESPONSE, $response);
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
                $traceData += $request[Request::CONTENT];
                break;
            case Payment\Action::CALLBACK:
                $traceData += $request[Request::CONTENT];
                break;
            case Payment\Action::VERIFY:
            case Payment\Action::AUTHORIZE_FAILED:
                $traceData += $this->getVerifyTraceData($request[Request::CONTENT]);
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

        return $headers;
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
    protected function getVerifyTraceData(array $content): array
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

        return $data;
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
}
