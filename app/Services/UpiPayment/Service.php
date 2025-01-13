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
use RZP\Models\UpiMandate;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Mozart\Gateway;
use RZP\Models\Base\PublicEntity;
use RZP\Gateway\Base\VerifyResult;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Gateway\Upi\Base\Constants;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RZP\Gateway\Upi\Base\RecurringTrait;
use Http\Discovery\Psr18ClientDiscovery;
use RZP\Exception\GatewayErrorException;
use RZP\Models\Merchant\RazorxTreatment;
use Http\Discovery\Psr17FactoryDiscovery;
use GuzzleHttp\Psr7\Request as Psr7Request;
use Psr\Http\Client\NetworkExceptionInterface;
use \RZP\Models\Terminal\Entity as TerminalEntity;
use RZP\Models\Payment\UpiMetadata\InternalStatus;
use RZP\Models\Gateway\File\Constants as GatewayConstants;

/**
 * Service implements the UPI Payments service client
 */
class Service
{
    use RecurringTrait;

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

    const VALIDATE_ACCOUNT_PROXY = 'validate_account_proxy';
    const VALIDATE_VPA_PROXY     = 'validate_vpa_proxy';

    const DASHBOARD_ENTITY_FETCH = 'dashboard_entity_fetch';

    const DASHBOARD_MULTIPLE_ENTITY_FETCH = 'dashboard_multiple_entity_fetch';

    const TURBO_CALLBACK_RECEIVE_AT_ANALYSIS_REDIS_KEY = 'turbo_%s_callback_rec_at';

    public static $upiRecurringActions = [
        Payment\Action::AUTHENTICATE,
        Payment\Action::DEBIT,
        Payment\Action::RECURRING_CALLBACK,
        Payment\Action::VERIFY_RECURRING,
        Payment\Action::NOTIFY,
        self::PRE_PROCESS
    ];

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
     * @param array|string $input
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
     * @param $rrn1
     * @return array
     * @throws \Exception
     */
    public function fetchAuthorizeEntityViaRRN(string $rrn, array $requiredColumns)
    {
        $action = GatewayConstants::MULTIPLE_ENTITY_FETCH;

        $input = [
            GatewayConstants::MODEL => GatewayConstants::AUTHORIZE,
            GatewayConstants::REQUIRED_FIELDS => $requiredColumns,
            GatewayConstants::COLUMN_NAME => GatewayConstants::CUSTOMER_REFERENCE,
            GatewayConstants::VALUES => [$rrn]
        ];

        try
        {
            return $this->action($action, $input, '');
        }
        catch (\Exception $e)
        {
            if ($e->getCode() !== ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND)
            {
                throw $e;
            }
        }
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
                    'body'      => $input['gateway']['payload'],
                    'gateway'   => $this->gateway,
                    'method'    => Payment\Method::UPI
                ];
                break;
            case Payment\Action::CALLBACK:
            case Payment\Action::RECURRING_CALLBACK:
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
            case self::VALIDATE_ACCOUNT_PROXY:
            case self::VALIDATE_VPA_PROXY:
            case self::DASHBOARD_ENTITY_FETCH:
            case self::DASHBOARD_MULTIPLE_ENTITY_FETCH:
                $data = $input;
                break;
            case Payment\Action::AUTHENTICATE:
                $data = $this->getRequestBodyForAuthentication($input);
                break;
            case Payment\Action::DEBIT:
                $data = $this->getCommonRequestForRecurring($input, Payment\Action::DEBIT);
                break;
            case Payment\Action::VERIFY_RECURRING:
                $data = $this->getRequestBodyForVerifyRecurring($input);
                break;
            case Payment\Action::REVOKE:
                $data = $this->getCommonRequestForRecurring($input, Payment\Action::REVOKE);
                break;
            case Payment\Action::NOTIFY:
                $data = $this->getRequestBodyForNotify($input);
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
     * returns the common Request Body for all actions
     *
     * @param  string $action
     * @param  array  $input
     * @return array
     */
    protected function getCommonRequestForRecurring(array $input, $action): array
    {
        return [
            Entity::PAYMENT     => $input[Entity::PAYMENT] ?? null,
            self::METADATA      => $input[self::METADATA] ?? null,
            Entity::TERMINAL    => $input[Entity::TERMINAL] ?? null,
            Entity::MERCHANT    => $input[Entity::MERCHANT] ?? null,
            Entity::UPI_MANDATE => $input[Entity::UPI_MANDATE] ?? null,
            Base\Entity::ACTION => $action,
        ];
    }

    /**
     * returns the Request Body for Notify action
     *
     * @param  array  $input
     * @return array
     */
    protected function getRequestBodyForNotify(array $input): array {

        $request = $this->getCommonRequestForRecurring($input, $this->action);

        $request[Entity::NOTIFICATION] = $input[Entity::NOTIFICATION] ?? null;

        return $request;
    }

    /**
     * returns the Request Body for Verify action
     *
     * @param  array  $input
     */
    protected function getRequestBodyForVerifyRecurring(array $input) {

        $ipt = $this->getCommonRequestForRecurring($input, Payment\Action::VERIFY);

        return [
            'data'      => $ipt,
            'gateway'   => $input['payment']['gateway'],
            'action'    => $this->action,
        ];
    }

    /**
     * returns the Request Body for Authenticate action
     *
     * @param  string $action
     * @param  array  $input
     * @return array
     */
    protected function getRequestBodyForAuthentication(array $input): array
    {
        $input[self::METADATA] = $input['upi'];

        if ($input[Entity::MERCHANT]->isTPVRequired() === true)
        {
            $input[self::METADATA][self::TPV] = true;
        }

        // check vpa in vpas table of upi, if not present then fetch it from gateway
        if ($input['payment']['gateway'] === Payment\Gateway::UPI_MINDGATE ||
            $input['payment']['gateway'] === Payment\Gateway::UPI_AXIS)
        {
            if ($input['metadata']['flow'] === Constants::COLLECT)
            {
                $vpa = $this->getVpaDetails($input);

                $input['vpa'] = $vpa;
            }
        }

        $variant = $this->evaluateSplitzExperimentForUpiAutopayPaymentRemark($input['payment']['merchant_id']);

        $description = "";
        if($variant === true)
        {
            $paymentDescription = $input['payment']['description'] ?? '';
            $description = Payment\Entity::getFilteredDescription($paymentDescription);
            $description =  ($description ? substr($description, 0, 50) : 'Pay via Razorpay');
        }
        else
        {
            $description = $this->getPaymentRemark($input);
        }

        $input[self::METADATA][Base\Entity::REMARK] = $description;

        $this->convertInputToArray($input);

        $response = $this->getCommonRequestForRecurring($input, Payment\Action::AUTHENTICATE);
        $response[Entity::ORDER] = $input[Entity::ORDER] ?? null;
        return $response;
    }

    protected function evaluateSplitzExperimentForUpiAutopayPaymentRemark($merchantId)
    {
        try
        {
            $properties = [
                'id'            => UniqueIdEntity::generateUniqueId(),
                'experiment_id' => $this->app['config']->get('app.upi_autopay_payment_remark'),
                'request_data'  => json_encode(
                    [
                        'merchant_id' => $merchantId,
                    ]),
            ];

            $response = $this->app['splitzService']->evaluateRequest($properties);

            $variant = $response['response']['variant']['name'] ?? '';

            $this->trace->info(TraceCode::SPLITZ_RESPONSE, $response);

            if ($variant === 'variant_on')
            {
                return true;
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::UPI_AUTOPAY_PAYMENT_REMARK
            );
        }

        return false;
    }

    /**
     * Returns the Payment Remark, i.e. The payment description if it exists
     * else the default remark 'Pay via Razorpay`
     *
     * @param array $input
     * @return string
     */
    protected function getPaymentRemark(array $input)
    {
        $paymentDescription = $input['payment']['description'] ?? '';
        $filteredPaymentDescription = Payment\Entity::getFilteredDescription($paymentDescription);

        $description = $input['merchant']->getFilteredDba() . ' ' . $filteredPaymentDescription;

        return ($description ? substr($description, 0, 50) : 'Pay via Razorpay');
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

        if(!(in_array($this->action, self::$upiRecurringActions, true)))
        {
            $this->checkForErrors($response, $code);
        }

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
                if($this->input[Entity::PAYMENT][Payment\Entity::RECURRING] === true)
                {
                    $response = $this->transformRecurringResponse($this->input, $response);
                }
            return $this->processVerifyResponse($response);
            case Payment\Action::VERIFY_RECURRING:
                $response = $this->transformRecurringResponse($this->input, $response);
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
            case self::VALIDATE_ACCOUNT_PROXY:
            case self::VALIDATE_VPA_PROXY:
            case self::DASHBOARD_ENTITY_FETCH:
            case self::DASHBOARD_MULTIPLE_ENTITY_FETCH:
                return $response;
            case Payment\Action::DEBIT:
            case Payment\Action::AUTHENTICATE:
            case Payment\Action::RECURRING_CALLBACK:
            case Payment\Action::REVOKE:
                return $this->processRecurringResponse($this->input, $response);
            case Payment\Action::NOTIFY:
                if (isset($this->input[Entity::NOTIFICATION]) === true)
                {
                    return $this->transformRecurringResponse($this->input, $response);
                }
                return $this->processRecurringResponse($this->input, $response);
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
     * processes the debit response
     *
     * @param array $response
     * @return array
     */
    protected function processRecurringResponse($input, array $response): array
    {
        $response = $this->transformRecurringResponse($input, $response);

        if (isset($response['error']) === true)
        {
            $error = $response['error'];

            $exception = new GatewayErrorException(
                $error['internal']['metadata']['internal_error_code'] ?? 'BAD_REQUEST_PAYMENT_FAILED',
                $error['internal']['metadata']['gateway_error_code'] ?? null,
                $error['internal']['metadata']['gateway_error_description'] ?? null,
                null,
                null,
                $this->action);

            $exception->setData($this->processRecurringRearchResponse($input, $response['data'], $exception));

            throw $exception;
        }

        return $this->processRecurringRearchResponse($input, $response['data']);
    }

    protected function transformRecurringResponse($input, $response): array
    {
        $response['data']['version'] = "v2";
        $response['data']['mandate'] = $response['data']['upi_mandate'] ?? [];

        if(isset($response['data']['data']['upi_mandate']) === true)
        {
            $response['data']['data']['mandate'] = $response['data']['data']['upi_mandate'];
            $response['data']['data']['version'] = "v2";
        }

        if(isset($response['data']['data']) and isset($response['data']['data'][Response::INTENT_URL]))
        {
            $response['data'][Response::INTENT_URL] = $response['data']['data'][Response::INTENT_URL];
        }

        $upiData = [];
        if (isset($response['data']['upi']) === true)
        {
            $upiData = $response['data']['upi'];
            $upiData['type'] = $input['upi']['flow'];
        }

        $response['data']['upi'] = $upiData;

        // transformation for decoupled notify response
        if((isset($this->input['notification']) === true) and (isset($response['error']) === false))
        {
            $response['success'] = true;
        }

        return $response;
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

            $description = $error['internal']['description'] ?? null;

            // todo: check if change is required here
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
        $data     = [];

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

        if ($this->action == Payment\Action::CALLBACK)
        {
            $data = $response['data'];
        }

        throw new Exception\GatewayErrorException(
            $internalErrorCode,
            $gatewayErrorCode,
            $gatewayErrorDesc,
            $data,
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

        if ($verify->gatewaySuccess === true and $this->input[Entity::PAYMENT][Payment\Entity::RECURRING] !== true)
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
            if($this->input[Entity::PAYMENT][Payment\Entity::RECURRING] === true)
            {
                $upi = $this->createRecurringUpiEntityFromResponse($response['data']['data']);
                $gateway = New Gateway();
                $verify->input['action'] = Action::VERIFY;
                return $gateway->extractUpiRecurringMandateAndPaymentProperties($upi, $verify);
            }
            return $this->processAuthorizeFailedPayment($verify, $response);
        }

        $this->verifyPayment($verify);

        return $verify->getDataToTrace();
    }

    protected function createRecurringUpiEntityFromResponse($response) : Base\Entity
    {
        $upi = new Base\Entity($response['upi']);

        $upi->setAction(Action::DEBIT);

        if (str_contains($upi->getMerchantReference(), 'create')){
            $upi->setAction(Action::AUTHENTICATE);
        }

        return $upi;
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
            case Payment\Action::RECURRING_CALLBACK:
                $traceData += $request[Request::CONTENT];
                break;
            case Payment\Action::VERIFY:
            case Payment\Action::AUTHORIZE_FAILED:
            case Payment\Action::VERIFY_RECURRING:
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
            case self::VALIDATE_ACCOUNT_PROXY:
                $traceData += $this->getValidateAccountProxyTraceData($request[Request::CONTENT]);
                break;
            case self::VALIDATE_VPA_PROXY:
                $traceData += $this->getValidateVpaProxyTraceData($request[Request::CONTENT]);
                break;
            case self::VALIDATE_VPA:
                $traceData += $this->getValdiateVpaTraceData($request[Request::CONTENT]);
                break;
            case Payment\Action::AUTHENTICATE:
            case Payment\Action::DEBIT:
            case Payment\Action::NOTIFY:
            case Payment\Action::REVOKE:
                $traceData += $this->getRecurringTraceData($request[Request::CONTENT]);
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
     * Returns trace data for recurring debit request
     *
     * @param array $content
     * @return array
     */
    protected function getRecurringTraceData(array $content): array
    {
        $data = [
            Payment\Entity::GATEWAY    => $content[Entity::PAYMENT][Payment\Entity::GATEWAY] ?? null,
            Entity::PAYMENT     => [
                Payment\Entity::ID        => $content[Entity::PAYMENT][Payment\Entity::ID] ?? null,
                Payment\Entity::AMOUNT    => $content[Entity::PAYMENT][Payment\Entity::AMOUNT] ?? null,
                Payment\Entity::CURRENCY  => $content[Entity::PAYMENT][Payment\Entity::CURRENCY] ?? null,
                Payment\Entity::CPS_ROUTE => $content[Entity::PAYMENT][Payment\Entity::CPS_ROUTE] ?? null,
                Payment\Entity::VPA       => $content[Entity::PAYMENT][Payment\Entity::VPA] ?? null,
                Payment\Entity::RECURRING => $content[Entity::PAYMENT][Payment\Entity::RECURRING] ?? null,
                Payment\Entity::TOKEN_ID  => $content[Entity::PAYMENT][Payment\Entity::TOKEN_ID] ?? null,
            ],
            Entity::MERCHANT   => [
                Merchant\Entity::BILLING_LABEL  => $content[Entity::MERCHANT][Merchant\Entity::BILLING_LABEL] ?? null,
            ],
            Entity::UPI_MANDATE => [
                UpiMandate\Entity::ID              => $content[Entity::UPI_MANDATE][UpiMandate\Entity::ID] ?? null,
                UpiMandate\Entity::FREQUENCY       => $content[Entity::UPI_MANDATE][UpiMandate\Entity::FREQUENCY] ?? null,
                UpiMandate\Entity::START_TIME      => $content[Entity::UPI_MANDATE][UpiMandate\Entity::START_TIME] ?? null,
                UpiMandate\Entity::END_TIME        => $content[Entity::UPI_MANDATE][UpiMandate\Entity::END_TIME] ?? null,
                UpiMandate\Entity::UMN             => $content[Entity::UPI_MANDATE][UpiMandate\Entity::UMN] ?? null,
                UpiMandate\Entity::MAX_AMOUNT      => $content[Entity::UPI_MANDATE][UpiMandate\Entity::MAX_AMOUNT] ?? null,
                UpiMandate\Entity::RECURRING_TYPE  => $content[Entity::UPI_MANDATE][UpiMandate\Entity::RECURRING_TYPE] ?? null,
                UpiMandate\Entity::RECURRING_VALUE => $content[Entity::UPI_MANDATE][UpiMandate\Entity::RECURRING_VALUE] ?? null,
            ]
        ];

        $data[self::METADATA] = $content[self::METADATA] ?? [];

        return $data;
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

        if (is_string($data)) {
            $data = $content;
        }

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

        if ($action === Payment\Action::AUTHORIZE_FAILED or
            $action === Payment\Action::VERIFY_RECURRING)
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

        if ($action === self::VALIDATE_ACCOUNT_PROXY)
        {
            return sprintf('%s/payments/validate/account', $version);
        }

        if ($action === self::VALIDATE_VPA_PROXY)
        {
            return sprintf('%s/payments/validate/vpa', $version);
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

        if ($action === Payment\Action::RECURRING_CALLBACK)
        {
            return sprintf('%s/recurring/callback', $version);
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
                Payment\Entity::RECURRING => $content[Entity::PAYMENT][Payment\Entity::RECURRING] ?? null,
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
     * get trace data for validate vpa proxy action send to UPS
     *
     * @param  array $content
     * @return array
     */
    protected function getValidateAccountProxyTraceData(array $content): array
    {
        return [
            Entity::VPA => mask_by_percentage($content['value'], 0.6)
        ];
    }

    protected function getValidateVpaProxyTraceData(array $content): array
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

        if($this->gateway === Payment\Gateway::UPI_AXISOLIVE)
        {
            if(isset($input[Entity::TERMINAL][TerminalEntity::NOTES]) === true && isJson($input[Entity::TERMINAL][TerminalEntity::NOTES]) === true)
            {
                $notesContent = json_decode($input[Entity::TERMINAL][TerminalEntity::NOTES], true);

                if($notesContent != null && isset($notesContent[TerminalEntity::MERCHANT_MOBILE_CONTACT]) === true)
                {
                    $input[Entity::TERMINAL][TerminalEntity::MERCHANT_MOBILE_CONTACT] = $notesContent[TerminalEntity::MERCHANT_MOBILE_CONTACT];
                }

            }
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

    /**
     * function to calculate and record the time difference between payer and payee callback of upi_axisolive
     *
     * @param string $gatewayDriver
     * @param $gateway
     * @param string $callbackFor
     * @param array $callbackPayload
     * @param float $receivedAt
     */
    public function turboPayerPayeeCallbackTimeDiffAnalysis(string $gatewayDriver, $gateway, string $callbackFor, array $callbackPayload, float $receivedAt) {
        try
        {
            $redis = $this->app['redis']->connection();

            $gatewayTransactionId = $gateway->getGatewayTransactionIdFromCallback($gatewayDriver, $callbackPayload);

            $redisKey = sprintf(self::TURBO_CALLBACK_RECEIVE_AT_ANALYSIS_REDIS_KEY, $gatewayTransactionId);

            $isSaved = $redis->set($redisKey, $receivedAt, 'ex', 600, 'nx');
            if (!$isSaved)
            {
                $leadCallbackType = $callbackFor === 'payer' ? 'payee' : 'payer';

                $dimensions = [
                    "leadCallbackType" => $leadCallbackType,
                ];

                $leadCallbackRecAt = $redis->get($redisKey);

                $difference = abs($receivedAt - $leadCallbackRecAt) * 1000;

                $this->trace->info(TraceCode::PAYMENT_PAYER_PAYEE_CALLBACK_DELAY,
                    [
                        'transaction_id' => $gatewayTransactionId,
                        'delay' => $difference,
                        'leadCallbackType' => $leadCallbackType
                    ]);

                $this->trace->histogram(Payment\Metric::PAYMENT_UPI_PAYER_PAYEE_CALLBACK_DIFF, $difference, $dimensions);
            }
        }
        catch(\Throwable $ex)
        {
            $this->app['trace']->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::PAYMENT_PAYER_PAYEE_CALLBACK_DELAY_FAILURE);
        }
    }
}
