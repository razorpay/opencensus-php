<?php

namespace RZP\Services;

use App;
use Razorpay\Trace\Logger as Trace;
use \WpOrg\Requests\Hooks as Requests_Hooks;
use RZP\Constants\Product;
use RZP\Exception;
use RZP\Gateway\Hitachi\Status;
use RZP\Models\Order;
use RZP\Models\Card;
use RZP\Models\CardMandate;
use RZP\Error\ErrorCode;
use RZP\Gateway\Base\Verify;
use RZP\Models\Emi\Migration;
use RZP\Gateway\Base\VerifyResult;
use RZP\Models\Payment\Gateway;
use RZP\Reconciliator\Base\InfoCode;
use RZP\Reconciliator\Service;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal;
use RZP\Constants\Entity;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Reminders;
use RZP\Error\ErrorClass;
use RZP\Gateway\Base\Action;
use Illuminate\Support\Arr;
use RZP\Http\Request\Requests;
use RZP\Models\Customer\Token\Repository;
use RZP\Models\Customer\Token\Core;
use RZP\Models\Feature;
use Illuminate\Support\Str;

class CardPaymentService
{
    const FETCH_PID_FROM_REF2_QUERY     = "SELECT payment_id FROM hive.realtime_pgpayments_card_live.authorization WHERE gateway='%s' and gateway_reference_id2= '%s'";
    const CONTENT_TYPE_HEADER           = 'Content-Type';
    const ACCEPT_HEADER                 = 'Accept';
    const APPLICATION_JSON              = 'application/json';
    const X_RAZORPAY_APP_HEADER         = 'X-Razorpay-App';
    const X_RAZORPAY_TASKID_HEADER      = 'X-Razorpay-TaskId';
    const X_RAZORPAY_MODE_HEADER        = 'X-Razorpay-Mode';
    const X_REQUEST_ID                  = 'X-Request-ID';
    const X_RAZORPAY_TRACKID            = 'X-Razorpay-TrackId';
    const X_RZP_TESTCASE_ID             = 'X-RZP-TESTCASE-ID';
    const RZPCTX_OPTIMIZER              = 'RZPCTX-OPTIMIZER';
    const RZPCTX_MERCHANT_ID            = 'RZPCTX-MERCHANT-ID';
    const RZPCTX_GATEWAY                = 'RZPCTX-GATEWAY';

    const REQUEST_TIMEOUT = 75; // Seconds
    const MAX_RETRY_COUNT = 1;
    const CPS_BULK_LIMIT  = 500;

    // request and response fields
    const GATEWAY   = 'gateway';
    const ACTION    = 'action';
    const INPUT     = 'input';
    const DATA      = 'data';
    const ERROR     = 'error';
    const AUTHORIZE = 'authorize';

    const ROUTE_NAME = 'route_name';


    // card meta data
    const NAME          = 'name';
    const IIN           = 'iin';
    const EXPIRY_MONTH  = 'expiry_month';
    const EXPIRY_YEAR   = 'expiry_year';

    /**
     * Default OTP attempts limit
     * @var integer
     */
    protected static $GatewayOtpAttemptLimit = [
        Payment\Gateway::KOTAK_DEBIT_EMI => 3,
        Payment\Gateway::INDUSIND_DEBIT_EMI => 5,
    ];

    // Entities fetch params
    const RRN = 'rrn';

    // admin path
    const ADMIN_PATH = 'admin/entities/';

    // entity path
    const ENTITY_PATH = 'entity/';

    // entities path
    const ENTITIES_PATH = 'entities/';
    const ENTITIES_PATH_V2 = 'v1/entitiesV2/';

    /**
     * Columns of CPS authorization table.
     * To be used while force authorizing failed payment
     */
    const AUTH_CODE     = 'auth_code';
    const RECON_ID      = 'recon_id';
    const PAYMENT_ID    = 'payment_id';
    const ENTITY_TYPE   = 'entity_type';
    const STATUS        = 'status';

    protected $baseUrl;
    protected $config;
    //protected $mozartConfig;
    protected $trace;
    protected $request;
    protected $action;
    protected $gateway;
    protected $input;
    protected $app;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.card_payment_service');
    }

    public function fetchAuthorizationData(array $input)
    {
        $request = [
            'url'     => $this->getBaseUrl() . 'entities/authorization',
            'method'  => 'POST',
            'content' => $input,
            'headers' => [
                'task_id'       => $this->app['request']->getTaskId(),
                'request_id'    => $this->app['request']->getId(),
            ],
        ];

        $response = $this->sendRawRequest($request);

        return $this->jsonToArray($response->body);
    }

    public function fetchPaymentIdFromVerificationFields($verificationFields)
    {
        $dataLakeQuery = sprintf(self::FETCH_PID_FROM_REF2_QUERY, $verificationFields['gateway'], $verificationFields['gateway_reference_id2']);

        $response = $this->app['datalake.presto']->getDataFromDataLake($dataLakeQuery);

        if(empty($response) === false && isset($response[0]['payment_id']) === true)
        {
            return $response[0]['payment_id'];
        }

        return [];
    }

    public function fetchPaymentIdFromCapsPIDs(array $input)
    {
        $responses = [];

        foreach (array_chunk($input, self::CPS_BULK_LIMIT) as $chunk)
        {
            $request = [
                'url'     => $this->getBaseUrl() . 'entities/all',
                'method'  => 'POST',
                'content' => [
                    'authorization' => [
                        'fields' => ['payment_id'],
                    ],
                    'ref_ids' => $chunk,
                ],
                'headers' => [
                    'task_id' => $this->app['request']->getTaskId(),
                    'request_id' => $this->app['request']->getId(),
                ],
            ];

            $rawResponse = $this->sendRawRequest($request);

            $response = $this->jsonToArray($rawResponse->body);

            foreach ($response as $key => $value)
            {
                $responses[$key] = $value;
            }
        }

        return $responses;
    }

    protected function getBaseUrl(): string
    {
        $mode = $this->app['rzp.mode'];

        $url = $this->config['url'][$mode];

        return $url;
    }

    protected function getRequestHooks()
    {
        $hooks = new Requests_Hooks();

        $hooks->register('curl.before_send', [$this, 'setCurlOptions']);

        return $hooks;
    }

    public function setCurlOptions($curl)
    {
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    }

    protected function getDefaultOptions(): array
    {
        $options = [
            'timeout' => self::REQUEST_TIMEOUT,
            'auth' => [
                $this->config['username'],
                $this->config['password']
            ],
            'hooks' => $this->getRequestHooks(),
        ];

        return $options;
    }

    protected function getDefaultHeaders(): array
    {
        $headers = [
            self::CONTENT_TYPE_HEADER      => self::APPLICATION_JSON,
            self::ACCEPT_HEADER            => self::APPLICATION_JSON,
            self::X_RAZORPAY_APP_HEADER    => 'api',
            self::X_RAZORPAY_TASKID_HEADER => $this->app['request']->getTaskId(),
            self::X_REQUEST_ID             => $this->app['request']->getId(),
            self::X_RAZORPAY_TRACKID       => $this->app['req.context']->getTrackId(),
        ];



        return $headers;
    }

    public function getRequestFieldsToBeUpdated(string $gateway,  array $input)
    {
        switch ($gateway)
        {
            case Payment\Gateway::HITACHI :
                return  [
                    self::RRN           =>  $input['gateway'][\RZP\Gateway\Hitachi\Entity::RRN],
                    self::AUTH_CODE     =>  $input['gateway'][\RZP\Gateway\Hitachi\Entity::AUTH_ID],
                    self::RECON_ID      =>  $input['gateway'][\RZP\Gateway\Hitachi\Entity::MERCHANT_REFERENCE]
                ];
            case Payment\Gateway::FIRST_DATA :
                return [
                    self::AUTH_CODE           => $input['gateway'][\RZP\Gateway\FirstData\Entity::AUTH_CODE]
                ];
            case Payment\Gateway::CARD_FSS :
                return [
                    self::RRN    => $input['gateway']['reference_number']
                ];
            default :
                return [];
        }
    }


    public function forceAuthorizeFailed(string $gateway, string $action, array $input)
    {
        $paymentId = $input['payment']['id'];

        $request = [
            'fields'        => [self::STATUS],
            'payment_ids'   => [$paymentId],
        ];

        $this->trace->info(
            TraceCode::PAYMENT_RECON_QUEUE_CPS_REQUEST,
            $request
        );

        $response = $this->fetchAuthorizationData($request);

        $this->trace->info(
            TraceCode::RECON_INFO,
            [
                'info_code'     => InfoCode::CPS_RESPONSE_AUTHORIZATION_DATA,
                'response'      => $response,
            ]);

        if (empty($response[$paymentId]) === false)
        {
            if ($response[$paymentId][self::STATUS] === Status::FAILED)
            {
                // Push to queue in order to update/force auth
                // Note : This push part we can do in async way and
                // just return true here, as there is no failure case ahead.

                $entity = $this->getRequestFieldsToBeUpdated($this->gateway, $input);

                if (empty($entity) === true)
                {
                    $this->trace->info(
                        TraceCode::RECON_INFO_ALERT,
                        [
                            'info_code'     => InfoCode::CPS_PAYMENT_AUTH_DATA_ABSENT,
                            'payment_id'    => $paymentId,
                            'gateway'       => $this->gateway,
                        ]);

                    return false;
                }

                $attr = [
                    self::PAYMENT_ID    =>  $paymentId,
                    self::ENTITY_TYPE   =>  self::GATEWAY,
                    self::GATEWAY       =>  $entity
                ];

                $queueName = $this->app['config']->get('queue.payment_card_api_reconciliation.' . $this->mode);

                Queue::pushRaw(json_encode($attr), $queueName);

                $this->trace->info(
                    TraceCode::RECON_INFO,
                    [
                        'info_code' => InfoCode::RECON_CPS_QUEUE_DISPATCH,
                        'message'   => 'Update gateway data in order to Force Authorize payment',
                        'payment_id'=> $paymentId,
                        'queue'     => $queueName,
                        'payload'   => json_encode($attr),
                    ]
                );
            }
        }
        else
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'     => InfoCode::CPS_PAYMENT_AUTH_DATA_ABSENT,
                    'payment_id'    => $paymentId,
                    'gateway'       => $this->gateway,
                ]);

            return false;
        }

        return true;
    }


    protected function verifyOtpAttempts(string $gateway,$payment, $limit = 3)
    {
        if (array_key_exists($gateway,self::$GatewayOtpAttemptLimit))
        {
            $limit = self::$GatewayOtpAttemptLimit[$gateway];
        }

        if ($payment['otp_attempts'] >= $limit)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_OTP_VALIDATION_ATTEMPT_LIMIT_EXCEEDED, null, null,
                ['method'=> $payment['method']]);
        }
    }

    public function action(string $gateway, string $action, array $input)
    {
        $app = App::getFacadeRoot();

        $this->action = $action;

        $this->gateway = $gateway;

        $this->input = $input;

        if ($this->action === Action::CALLBACK and ($gateway === Payment\Gateway::KOTAK_DEBIT_EMI or $gateway === Payment\Gateway::INDUSIND_DEBIT_EMI))
        {
            $this->verifyOtpAttempts($gateway,$input['payment']);
        }

        if (empty($input[Entity::TERMINAL]) === false)
        {
            $input[Entity::TERMINAL] = $input[Entity::TERMINAL]->toArrayWithPassword();

            //$input[Entity::TERMINAL] = $this->updateTerminalFromConfig($input);
        }

        if ($this->action === Action::AUTHORIZE)
        {
            $input[self::GATEWAY]['features']['tpv'] = $input[Entity::MERCHANT]->isTPVRequired();

            try
            {
                $input = $this->getAdditionalNetworkTokenDetailsForOptimizer($gateway, $input);
            }
            catch (\Throwable $e)
            {
                $this->trace->info(TraceCode::GET_OPTIMIZER_TOKEN_FAILED, [$e->getTrace()]);
            }

            try
            {
                $this->fetchPNetworkData($input);
            }
            catch (\Exception $ex)
            {
                $this->trace->info(
                    TraceCode::FAILED_TO_FETCH_PNETWORK_DATA,
                    [
                        'payment_id' => $input['payment']['id'],
                    ]);
                $input['payment']['network_transaction_id'] = '039217544591994';
            }

            try
            {
                $this->fetchTokenReferenceNumber($input);
            }
            catch (\Exception $ex)
            {
                $this->trace->info(
                    TraceCode::FAILED_TO_FETCH_TOKEN_REFERENCE_NUMBER,
                    [
                        'payment_id' => $input['payment']['id'],
                    ]);
            }
        }

        if ((in_array($gateway, Payment\Gateway::OPTIMIZER_CARD_GATEWAYS, true) and
            ($action === Action::CALLBACK) and
            ($input['gateway']['type'] !== 'otp')))
        {
            $dynamicContent = $input['gateway'];

            unset($input['gateway']);

            $input['gateway']['redirect']['dynamicContent'] = $dynamicContent;
        }

        if ((Gateway::isGatewayCallbackEmpty($gateway) === true) and
            (($action === Action::CALLBACK) or
                ($action === Action::PAY)))
        {
            unset($input['gateway']);
        }

        if ($action === Action::AUTHORIZE_FAILED)
        {
            $action = Action::VERIFY;
        }

        if ($action === Action::VERIFY)
        {
            unset($input['payment']['billing_address']);
        }

        if($action === ACTION::FORCE_AUTHORIZE_FAILED and (in_array($gateway, Payment\Gateway::FORCE_AUTHORIZE_FAILED_SYNC_GATEWAYS, true) === true))
        {
            unset($input['gateway']);
        }
        if ($this->action != Action::AUTHORIZE || empty($input['acs_afa_authentication']))
        {
            unset($input['acs_afa_authentication']);
        }

        $routeName = $app['api.route']->getCurrentRouteName();

        $input['route'] = $routeName;

        $content = [
            self::ACTION  => $action,
            self::GATEWAY => $gateway,
            self::INPUT   => $input
        ];

        // change action for force_authorize_failed to verify after content creation
        // to be take decisions further on action for fulcrum gateway
        if ($action === Action::FORCE_AUTHORIZE_FAILED and (in_array($gateway, Payment\Gateway::FORCE_AUTHORIZE_FAILED_SYNC_GATEWAYS, true) !== true))
        {
            $action = Action::VERIFY;
        }

        $this->addDummyCardMetaDataIfNotPresent($content);

        $this->addOrderDetailsIfNotPresent($content);

        $this->addMerchantFeatures($content);

        $this->addCardIin($content);

        $this->addAuthenticationDataIfApplicable($content);


        // Should migrate merchant_attribute table also as part of rearch to accomodate 3ds2 flow.
        if($action === Action::AUTHORIZE)
        {
            $this->addThreeDSDetailsIfApplicable($content);
        }

        $response = $this->sendRequest('POST', 'action/' . $action, $content);

        if ($this->action === Action::AUTHORIZE)
        {
            try
            {
                $this->updatePNetworkData($input);
            }
            catch (\Exception $ex)
            {
                $this->trace->info(
                    TraceCode::FAILED_TO_UPDATE_PNETWORK_DATA,
                    [
                        'payment_id' => $input['payment']['id'],
                    ]);
            }
        }

        return $response;
    }

    /**
     * @param array $data
     * @param array $request
     */
    public function traceOptimizerMerchant(array $data, array &$request)
    {
        try {
            if ((isset($data[self::INPUT]) === true))
            {
                if (isset($data[self::INPUT][Entity::PAYMENT]) === true)
                {
                    $paymentData = $data[self::INPUT][Entity::PAYMENT] ?? null;
                    $mid = $paymentData['merchant_id'];
                    $this->trace->info(TraceCode::CPS_MERCHANT_FEATURE_DATA_MID, [$mid]);
                    $request['headers'][self::RZPCTX_MERCHANT_ID] = $mid;
                    $request['headers'][self::RZPCTX_GATEWAY] = $request['content'][self::INPUT][Entity::PAYMENT]['gateway'];
                    if (((new Feature\Service())->checkFeatureEnabled(Feature\Constants::MERCHANT, $mid, Feature\Constants::RAAS))['status'])
                    {
                        $request['headers'][self::RZPCTX_OPTIMIZER] = "true";
                    } else
                    {
                        $request['headers'][self::RZPCTX_OPTIMIZER] = "false";
                    }
                    $this->trace->info(TraceCode::CPS_MERCHANT_FEATURE_ENABLED, [
                        $request['headers'][self::RZPCTX_OPTIMIZER],
                        $request['headers'][self::RZPCTX_MERCHANT_ID],
                        $request['headers'][self::RZPCTX_GATEWAY]
                    ]);
                }
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->info(TraceCode::CPS_MERCHANT_FEATURE_ERROR, [
                $e->getMessage()
            ]);
        }
    }

    /**
     * @throws \Exception
     */
    protected function fetchPNetworkData(array &$input)
    {
        if (!empty($input['token']) and ($input['payment']['recurring'] === true) and ($input['payment']['recurring_type'] === 'auto') and ($input['payment']['international'] === false))
        {
            if (!empty($input['token']['card']) and ($input['token']['card']['network'] === 'Visa'))
            {
                $token = (new Repository())->find($input[Entity::TOKEN]['id']);
                if (empty($token)){
                    throw new \Exception("Token entity not found");
                }

                $cardMandate = (new CardMandate\Repository())->findByCardMandateId($token->getCardMandateId());
                if (empty($cardMandate)){
                    throw new \Exception("Card Mandate entity not found");
                }

                if (!empty($cardMandate->getNetworkTransactionId()))
                {
                    $input['payment']['network_transaction_id'] = $cardMandate->getNetworkTransactionId();
                }
                else
                {
                    $initialPayment = (new Payment\Repository)->fetchInitialPaymentIdForToken($input['token']['id'], $input['merchant']['id']);
                    if (empty($initialPayment)){
                        throw new \Exception("Initial Payment for token not found");
                    }

                    $paymentId = $initialPayment->getId();

                    $request = [
                        'fields'      => ['network_transaction_id'],
                        'payment_ids' => [$paymentId],
                    ];

                    $response = $this->app['card.payments']->fetchAuthorizationData($request);

                    $this->trace->info(
                        TraceCode::HITACHI_DATA_CPS_REQUEST_RESPONSE,
                        [
                            'info_code' => InfoCode::CPS_RESPONSE_AUTHORIZATION_DATA,
                            'response' => $response,
                        ]);

                    if (!empty($response[$paymentId]['network_transaction_id']))
                    {
                        $input['payment']['network_transaction_id'] = $response[$paymentId]['network_transaction_id'];
                        $cardMandate->setHasInitialTransactionId(true);
                        $cardMandate->setNetworkTransactionId($input['payment']['network_transaction_id']);
                    }
                    else
                    {
                        $input['payment']['network_transaction_id'] = '039217544591994';
                        $cardMandate->setHasInitialTransactionId(false);
                    }
                    (new CardMandate\Repository())->saveOrFail($cardMandate);
                }
            }
        }
    }

    protected function fetchTokenReferenceNumber(array &$input) {

        if ($this->action === Action::AUTHORIZE && $input['payment']['gateway'] === gateway::AXIS_TOKENHQ) {

            $token = (new repository())->find($input['token']['id']);

            $networkToken = (new Core())->fetchToken($token, false);

            assertTrue(empty($networkToken) === false);

            $noOfTokens = count($networkToken);

            if ($noOfTokens > 1)
            {
                $trn = $networkToken[0][Entity::PROVIDER_TYPE] === 'issuer' ? $networkToken[0][Entity::PROVIDER_DATA][Entity::TOKEN_REFERENCE_NUMBER] : $networkToken[1][Entity::PROVIDER_DATA][Entity::TOKEN_REFERENCE_NUMBER];
                $input[Entity::CARD][Entity::TOKEN_REFERENCE_NUMBER] = $trn;
            }
            else
            {
                $trn = $networkToken[0][Entity::PROVIDER_DATA][Entity::TOKEN_REFERENCE_NUMBER] ?? '';
                $input[Entity::CARD][Entity::TOKEN_REFERENCE_NUMBER] = $trn;
            }
        }
    }

    /**
     * @throws \Exception
     */
    protected function updatePNetworkData(array $input)
    {
        if (!empty($input['token']) and ($input['payment']['recurring'] === true) and ($input['payment']['recurring_type'] === 'auto') and ($input['payment']['international'] === false))
        {
            if (!empty($input['token']['card']) and ($input['token']['card']['network'] === 'Visa'))
            {
                $token = (new Repository())->find($input[Entity::TOKEN]['id']);
                if (empty($token)){
                    throw new \Exception("Token entity not found");
                }

                $cardMandate = (new CardMandate\Repository())->findByCardMandateId($token->getCardMandateId());
                if (empty($cardMandate)){
                    throw new \Exception("Card Mandate entity not found");
                }

                if (!$cardMandate->getHasInitialTransactionId())
                {
                    $request = [
                        'fields'      => ['network_transaction_id'],
                        'payment_ids' => [$input['payment']['id']],
                    ];
                    $response = $this->app['card.payments']->fetchAuthorizationData($request);
                    $cardMandate->setNetworkTransactionId($response[$input['payment']['id']]['network_transaction_id']);
                    (new CardMandate\Repository())->saveOrFail($cardMandate);
                }
            }
        }
    }


    public function authorizeAcrossTerminals(Payment\Entity $payment, array $gatewayInput, array $terminals)
    {
        $app = App::getFacadeRoot();

        $input = [];

        $input = $gatewayInput;

        $input['terminals'] = [];

        foreach ($terminals as $terminal)
        {
            $terminalInput = [];

            $terminalInput = $terminal->toArrayWithPassword();


            if ((empty($input['authentication_terminals']) === false) and
                (empty($input['authentication_terminals'][$terminal->getId()]) === false))
            {
                $terminalInput['auth'] = $input['authentication_terminals'][$terminal->getId()];
            }

            $input['terminals'][] = $terminalInput;
        }

        unset($input['authentication_terminals']);

        $content = [
            self::INPUT   => $input
        ];

        $this->addOrderDetailsIfNotPresent($content);

        $this->addMerchantFeatures($content);

        $this->addCardIin($content);

        $this->addAuthenticationDataIfApplicable($content);

        $routeName = $app['api.route']->getCurrentRouteName();

        if ($routeName != null)
        {
            $content[self::INPUT][self::ROUTE_NAME] = $routeName;
        }

        $response = $this->sendRequest('POST', self::AUTHORIZE , $content);

        return $response;
    }

    public function fetchMultiple(string $entityName, array $input)
    {
        $path = self::ADMIN_PATH . $entityName;

        return $this->sendRequest('GET', $path, $input);
    }

    public function fetch(string $entityName, string $id, $input)
    {
        $path = self::ADMIN_PATH . $entityName . '/' . $id;

        return $this->sendRequest('GET', $path, $input);
    }

    public function fetchEntity(string $entityName, $id)
    {
        $path = self::ENTITY_PATH . $entityName . '/' . $id;

        return $this->sendRequest('GET', $path, []);
    }

    public function fetchEntityForEsSync($backfill)
    {
        $path = self::ENTITIES_PATH . 'payments/es_sync';

        if ($backfill === true)
        {
            $path = $path . "?backfill=true";
        }

        return $this->sendRequest('GET', $path, []);
    }

    public function create(string $entityName, $input)
    {
        $path = self::ENTITIES_PATH_V2 . $entityName;

        return $this->sendRequest('POST', $path, $input);
    }

    public function get(string $entityName, $id)
    {
        $path = self::ENTITIES_PATH_V2 . $entityName . '/' . $id;

        return $this->sendRequest('GET', $path);
    }

    public function query(string $entityName, $query)
    {
        $path = self::ENTITIES_PATH_V2 . $entityName . '/query' ;

        return $this->sendRequest('POST', $path, $query);
    }

    public function update(string $entityName, string $id, $input)
    {
        $path = self::ENTITIES_PATH_V2 . $entityName . '/' . $id;

        return $this->sendRequest('PUT', $path, $input);
    }

    public function delete(string $entityName, string $id)
    {
        $path = self::ENTITIES_PATH_V2 . $entityName . '/' . $id;

        return $this->sendRequest('DELETE', $path, []);
    }

    public function emiPlanQuery(string $entityName, $query)
    {
        $path = '/v1/emi_plans/search' ;

        return $this->sendRequest('POST', $path, $query);
    }

    public function sendRequest(string $method, string $url, array $data = [])
    {
        $request = [
            'url'     => $this->getBaseUrl() . $url,
            'method'  => $method,
            'content' => $data,
            'headers' => $this->getDefaultHeaders()
        ];

        if (isset($this->app['rzp.mode']) and $this->app['rzp.mode'] === 'test')
        {
            $testCaseId = $this->app['request']->header('X-RZP-TESTCASE-ID');

            if (empty($testCaseId) === false)
            {
                $request['headers'][self::X_RZP_TESTCASE_ID] = $testCaseId;
            }
        }

        $this->traceOptimizerMerchant($data, $request);

        $this->traceRequest($request);

        $response = $this->sendRawRequest($request);

        $response = $this->processResponse($response, $method);

        $this->traceResponse($response, $data);

        return $response;
    }

    protected function addOrderDetailsIfNotPresent(array & $data)
    {
        if (isset($data[self::INPUT]) === true)
        {
            if ((isset($data[self::INPUT][Entity::PAYMENT]) === true) and
                (isset($data[self::INPUT][Entity::PAYMENT][Payment\Entity::ORDER_ID]) === true))
            {
                $orderId = $data[self::INPUT][Entity::PAYMENT][Payment\Entity::ORDER_ID];

                if (Str::startsWith($orderId, 'order_') === false)
                {
                    $orderId = 'order_' . $orderId;
                }
                $order = (new Order\Repository())->findByPublicId($orderId);

                if (is_null($order) === false)
                {
                    $data[self::INPUT][Entity::ORDER] = $order->toArrayPublic();
                }
            }
        }
    }

    protected function addMerchantFeatures(array & $data)
    {
        if ((isset($data[self::INPUT]) === true) and (isset($data[self::INPUT][Entity::MERCHANT]) === true))
        {
            if (is_null($data[self::INPUT][Entity::MERCHANT]) === false)
            {
                $data[self::INPUT][Entity::MERCHANT][Merchant\Entity::FEATURES] = $data[self::INPUT][Entity::MERCHANT]->features;
            }
        }
    }

    protected function addCardIin(array & $data)
    {
        if ((isset($data[self::INPUT]) === true) and (isset($data[self::INPUT][Entity::PAYMENT]) === true))
        {
            $cardId = $data[self::INPUT][Entity::PAYMENT][Payment\Entity::CARD_ID];

            $card = null;

            try
            {
                $card = (new Card\Repository())->findOrFail($cardId);
            }
            catch (\Throwable $exception) {}

            if ((is_null($card) === false) and (is_null($card->iinRelation) === false))
            {
                $data[self::INPUT][Entity::IIN] = $card->iinRelation->toArrayPublic();
            }
        }
    }

    protected function addDummyCardMetaDataIfNotPresent(array & $data)
    {
        if (isset($data[self::INPUT]) === true)
        {
            if (isset($data[self::INPUT][Entity::CARD]) === true)
            {
                if(empty( $data[self::INPUT][Entity::CARD][self::NAME]) === true)
                {
                    $data[self::INPUT][Entity::CARD][self::NAME] = "dummy card";
                }

                if(empty( $data[self::INPUT][Entity::CARD][self::EXPIRY_MONTH]) === true )
                {
                    $data[self::INPUT][Entity::CARD][self::EXPIRY_MONTH] = 1;
                }

                if(empty( $data[self::INPUT][Entity::CARD][self::EXPIRY_YEAR]) === true )
                {
                    $data[self::INPUT][Entity::CARD][self::EXPIRY_YEAR] = 2099;
                }

                if(empty( $data[self::INPUT][Entity::CARD][self::IIN]) === true )
                {
                    $data[self::INPUT][Entity::CARD][self::IIN] = "999999";
                }
            }
        }
    }

    protected function addAuthenticationDataIfApplicable(array & $data)
    {
        if ((isset($data[self::INPUT]) === true) and (isset($data[self::INPUT]['authentication']) === true))
        {
            $authentication = $data[self::INPUT]['authentication'];

            if((isset($authentication['provider_data']) === true)){
                $providerData = $authentication['provider_data'];

                unset($authentication['provider_data']);

                $authentication = array_merge($authentication, $providerData);
            }

            if (isset($data[self::INPUT]['authenticate']) === true)
            {
                $data[self::INPUT]['authenticate'] = array_merge($data[self::INPUT]['authenticate'], $authentication);
            }
            else
            {
                $data[self::INPUT]['authenticate'] = $authentication;
            }

            unset($data[self::INPUT]['authentication']);
        }
    }

    //Migrate it as part of rearch to send 3ds2 details as part of AREQ
    protected function addThreeDSDetailsIfApplicable(array & $data)
    {
        if( $data['input'][Entity::MERCHANT]->Is3dsDetailsRequiredEnabled())
        {
            $network =  strtolower($data['input']['iin']['network']);

            if(in_array($network,Merchant\Constants::listOfNetworksSupportedOn3ds2))
            {
                $data['input']['card']['authentication_out_of_band'] = $this->get3ds2DetailsForNetwork($network, $data['input'][Entity::MERCHANT], Product::PRIMARY);
            }
        }

    }

    public function get3ds2DetailsForNetwork($network, $merchant, $product) {

        $requestorId = (new Merchant\Attribute\Repository())->getValueForProductGroupType($merchant->getId(), $product, $network,Merchant\Attribute\Type::REQUESTER_ID);
        $merchantName = (new Merchant\Attribute\Repository())->getValueForProductGroupType($merchant->getId(), $product, $network,Merchant\Attribute\Type::MERCHANT_NAME);

        if($requestorId && $merchantName)
        {
            return [
                "3ds_requestor_id" => $requestorId['value'],
                "3ds_requestor_name" => $merchantName['value']
            ];
        }
        else{
            list($requestorIdValue, $merchantNameValue) = (new Merchant\Attribute\Service())->getDefaultValuesForMerchantOnboarding($network, $merchant);
            return [
                "3ds_requestor_id" => $requestorIdValue,
                "3ds_requestor_name" => $merchantNameValue
            ];
        }
    }

    protected function traceRequest(array $request)
    {
        try
        {
            unset($request['content'][self::INPUT]['gateway']['otp']);
            // Redacting PCI fields for PineLabs card payments
            unset($request['content'][self::INPUT]['gateway']['redirect']['dynamicContent']['masked_card_number']);
            unset($request['content'][self::INPUT]['gateway']['redirect']['dynamicContent']['card_holder_name']);

            $request['content'][self::INPUT]['terminal_ids'] = [];

            if (empty($request['content'][self::INPUT]['terminals']) === false)
            {
                foreach ($request['content'][self::INPUT]['terminals'] as $idx => $terminal)
                {
                    $request['content'][self::INPUT]['terminal_ids'][$idx] = $terminal[Terminal\Entity::ID];
                }
            }

            $traceMap = [
                'action'                            => 'content.action',
                'payment.id'                        => 'content.input.payment.id',
                'payment.auth_type'                 => 'content.input.payment.auth_type',
                'payment.notes'                     => 'content.input.payment.notes',
                'payment.gateway'                   => 'content.input.payment.gateway',
                'payment.billing_address'           => 'content.input.payment.billing_address',
                'payment.network_transaction_id'    => 'content.input.payment.network_transaction_id',
                'merchant.id'                       => 'content.input.merchant.id',
                'merchant.name'                     => 'content.input.merchant.name',
                'merchant.features'                 => 'content.input.merchant.features',
                'terminal.id'                       => 'content.input.terminal.id',
                'terminal.merchant_id'              => 'content.input.terminal.merchant_id',
                'terminal.type'                     => 'content.input.terminal.type',
                'card.network'                      => 'content.input.card.network',
                'card.issuer'                       => 'content.input.card.issuer',
                'card.country'                      => 'content.input.card.country',
                'card.tokenised'                    => 'content.input.card.tokenised',
                'card.token_id'                     => 'content.input.token.id',
                'card.token_reference_number'       => 'content.input.card.token_reference_number',
                'card.3ds2_requestor_id'            => 'content.input.card.authentication_out_of_band.3ds_requestor_id',
                'card.3ds2_requestor_name'          => 'content.input.card.authentication_out_of_band.3ds_requestor_name',
                'iin.network'                       => 'content.input.iin.network',
                'iin.country'                       => 'content.input.iin.country',
                'iin.emi'                           => 'content.input.iin.emi',
                'iin.flows'                         => 'content.input.iin.flows',
                'iin.enabled'                       => 'content.input.iin.enabled',
                'emi_plan.id'                       => 'content.input.emi_plan.id',
                'emi_plan.duration'                 => 'content.input.emi_plan.duration',
                'emi_plan.type'                     => 'content.input.emi_plan.type',
                'emi_plan.rate'                     => 'content.input.emi_plan.rate',
                'emi_plan.subvention'               => 'content.input.emi_plan.subvention',
                'card.is_international'             => 'content.input.card.international',
                'authentication.auth'               => 'content.input.authenticate',
                'authentication.auth_type'          => 'content.input.auth_type',
                'gateway.data'                      => 'content.input.gateway',
                'gateway.callback_url'              => 'content.input.callbackUrl',
                'gateway.otpsubmit_url'             => 'content.input.otpSubmitUrl',
                'terminals'                         => 'content.input.terminal_ids',
                'token.id'                          => 'content.input.token.id',
                'analytics.risk_engine'             => 'content.input.payment_analytics.risk_engine',
                'analytics.risk_score'              => 'content.input.payment_analytics.risk_score',
                'analytics.ip'                      => 'content.input.payment_analytics.ip',
                'analytics.user_agent'              => 'content.input.payment_analytics.user_agent',
                'order.receipt'                     => 'content.input.order.receipt',
                'emi_plan_fetch.merchant_id'        => 'content.merchant_id',
                'emi_plan_fetch.merchant_ids'       => 'content.merchant_ids',
                'emi_plan_fetch.id'                 => 'content.id',
                'emi_plan_fetch.network'            => 'content.network',
                'emi_plan_fetch.bank'               => 'content.bank',
                'emi_plan_fetch.type'               => 'content.type',
                'emi_plan_fetch.duration'           => 'content.duration',
                'emi_plan_fetch.durations'          => 'content.durations',
                'emi_plan_fetch.cobranding_partner' => 'content.cobranding_partner',
            ];

            $requestTrace = [];

            foreach ($traceMap as $key => $srcPath)
            {
                $value = Arr::get($request, $srcPath);

                if (is_null($value) === false)
                {
                    Arr::set($requestTrace, $key, $value);
                }
            }

            $this->trace->info(TraceCode::CARD_PAYMENT_SERVICE_REQUEST, $requestTrace);
        }
        catch (\Throwable $e)
        {
            $this->trace->info(TraceCode::CARD_PAYMENT_SERVICE_REQUEST_ERROR, [
                $e->getMessage()
            ]);
        }
    }

    protected function sendRawRequest($request)
    {
        $retryCount = 0;

        while (true)
        {
            try
            {
                $content = $request['content'];

                if ($request['method'] === 'POST')
                {
                    $content = json_encode($request['content']);
                }

                $response = Requests::request(
                    $request['url'],
                    $request['headers'],
                    $content,
                    $request['method'],
                    $this->getDefaultOptions());

                break;
            }
            catch(\WpOrg\Requests\Exception $e)
            {
                $this->trace->traceException($e);

                if ($retryCount < self::MAX_RETRY_COUNT)
                {
                    $this->trace->info(
                        TraceCode::CARD_PAYMENT_SERVICE_RETRY,
                        [
                            'message' => $e->getMessage(),
                            'type'    => $e->getType(),
                            'data'    => $e->getData()
                        ]);

                    $retryCount++;

                    continue;
                }

                $this->throwServiceErrorException($e);
            }
        }

        return $response;
    }

    protected function processResponse($response, $method)
    {
        $code = $response->status_code;

        $responseBody = $this->jsonToArray($response->body);
        $responseBody['success'] = false;
        $responseBody['status_code'] = $code;

        if ($this->action === Action::AUTHORIZE_FAILED)
        {
            return $this->processAuthorizedFailedResponse($responseBody);
        }

        if ($this->action === Action::VERIFY)
        {
            return $this->processVerifyResponse($responseBody);
        }

        if ($this->isSuccessResponse($code, $responseBody))
        {
            $responseBody['success'] = true;
        }

        return $responseBody;
    }

    protected function traceResponse($response, $data)
    {

        $traceResponse = $response;

        // For axis_migs we don't send gateway request in redirect case,
        // We redirect customer with actual request content which has card and terminal details,
        // Unsetting these fields before logging is mandatory
        unset($traceResponse['data']['content']['vpc_CardNum']);
        unset($traceResponse['data']['content']['vpc_AccessCode']);
        unset($traceResponse['data']['content']['vpc_CardExp']);
        unset($traceResponse['data']['content']['vpc_CardSecurityCode']);
        unset($traceResponse['data']['content']['vpc_SubMerchant_Phone']);
        unset($traceResponse['data']['content']['vpc_SubMerchant_Email']);
        unset($traceResponse['data']['content']['vpc_SubMerchant_Street']);

        //Redacting fields for First_data
        unset($traceResponse['data']['content']['cvm']);
        unset($traceResponse['data']['content']['cardnumber']);
        unset($traceResponse['data']['content']['dynamicMerchantName']);
        unset($traceResponse['data']['content']['bname']);
        unset($traceResponse['data']['content']['expmonth']);
        unset($traceResponse['data']['content']['expyear']);

        // Redacting fields for Cashfree card payments
        unset($traceResponse['data']['content']['appId']);
        unset($traceResponse['data']['content']['card_number']);
        unset($traceResponse['data']['content']['card_holder']);
        unset($traceResponse['data']['content']['card_cvv']);
        unset($traceResponse['data']['content']['card_expiryMonth']);
        unset($traceResponse['data']['content']['card_expiryYear']);
        unset($traceResponse['data']['content']['customerEmail']);
        unset($traceResponse['data']['content']['customerName']);
        unset($traceResponse['data']['content']['customerPhone']);

        // Redacting fields for Payu card payments
        unset($traceResponse['data']['content']['ccnum']);
        unset($traceResponse['data']['content']['ccname']);
        unset($traceResponse['data']['content']['ccvv']);
        unset($traceResponse['data']['content']['ccexpmon']);
        unset($traceResponse['data']['content']['ccexpyr']);
        unset($traceResponse['data']['content']['email']);
        unset($traceResponse['data']['content']['firstname']);
        unset($traceResponse['data']['content']['phone']);

        if (isset($traceResponse[Migration::EMI_PLANS]) === true)
        {
            $emiTrace = [];

            foreach ($traceResponse[Migration::EMI_PLANS] as $plan)
            {
                $planTrace = [
                    'id'             => Arr::get($plan, 'id'),
                    'merchant_id'    => Arr::get($plan, 'merchant_id'),
                ];

                array_push($emiTrace, $planTrace);
            }

            $traceResponse[Migration::EMI_PLANS] = $emiTrace;
        }

        $traceData = [];

        $traceData['response']   = $traceResponse;

        if (isset($data['input']['payment']['id']) === true)
        {
            $traceData['payment_id'] = $data['input']['payment']['id'];
        }

        if (isset($data['input']['payment']['gateway']) === true)
        {
            $traceData['gateway'] = $data['input']['payment']['gateway'];
        }

        $this->trace->info(TraceCode::CARD_PAYMENT_SERVICE_RESPONSE, $traceData ?? []);
    }

    protected function jsonToArray($json)
    {
        $decodeJson = json_decode($json, true);

        switch (json_last_error())
        {
            case JSON_ERROR_NONE:
                return $decodeJson;

            case JSON_ERROR_DEPTH:
            case JSON_ERROR_STATE_MISMATCH:
            case JSON_ERROR_CTRL_CHAR:
            case JSON_ERROR_SYNTAX:
            case JSON_ERROR_UTF8:
            default:

                $this->trace->error(
                    TraceCode::CARD_PAYMENT_SERVICE_ERROR,
                    ['json' => $json]);

                throw new Exception\RuntimeException(
                    'Failed to convert json to array',
                    ['json' => $json]);
        }
    }

    protected function isSuccessResponse($code, $responseBody)
    {
        if ($code === 200 || $code === 204)
        {
            return true;
        }

        return false;
    }

    // ----------------------- Verify ---------------------------------------------

    protected function processVerifyResponse($response)
    {
        $verify = $this->verifyPayment($response);

        if (($verify->match === false) and
            ($verify->throwExceptionOnMismatch))
        {
            throw new Exception\PaymentVerificationException(
                $verify->getDataToTrace(),
                $verify);
        }

        if (($verify->amountMismatch === true) and
            ($verify->throwExceptionOnMismatch))
        {
            throw new Exception\RuntimeException(
                'Payment amount verification failed.',
                [
                    'payment_id' => $this->input['payment']['id'],
                    'gateway'    => $this->gateway
                ]
            );
        }

        return $verify->getDataToTrace();
    }

    // ------------------------ Authorized Failed --------------------------------------

    protected function processAuthorizedFailedResponse($response)
    {
        $e = null;

        // since authorized failed action we do gateway verify
        try
        {
            $verify = $this->processVerifyResponse($response);
        }
        catch (Exception\PaymentVerificationException $e) {
            $this->trace->info(
                TraceCode::PAYMENT_FAILED_TO_AUTHORIZED,
                [
                    'message'    => 'Payment verification failed. Now converting to authorized',
                    'payment_id' => $this->input['payment']['id']
                ]);

            if ($e->getAction() === Payment\Verify\Action::RETRY)
            {
                //
                // When the response returned is a null, we throw
                // a PaymentVerificationException with Action::RETRY
                // and therefore we must return from here with an exception
                // result without processing the rest of this flow
                //
                throw new Exception\GatewayErrorException(
                    ErrorCode::GATEWAY_ERROR_PAYMENT_VERIFICATION_ERROR,
                    null,
                    null,
                    [],
                    $e);
            }

        }

        if ($e === null)
        {
            throw new Exception\LogicException(
                'When converting failed payment to authorized, payment verification ' .
                'should have failed but instead it did not',
                null,
                $this->input['payment']);
        }

        $verify = $e->getVerifyObject();

        if (($verify->apiSuccess === false) and ($verify->gatewaySuccess === true))
        {
            // The update of gateway entities are handled in the cps.
            $authDetails = $verify->getDataToTrace();

            if (isset($response['payment']['reference2']) === true)
            {
                $authDetails['acquirer'] = [
                    'reference2' => $response['payment']['reference2']
                ];
            }

            return  $authDetails;
        }

        throw new Exception\LogicException(
            'Should not have reached here',
            null,
            ['payment' => $this->input['payment']]);
    }

    protected function verifyPayment($response)
    {
        $verify = new Verify($this->gateway, []);
        if (empty($response[self::DATA]) === false)
        {
            $verify->verifyResponseContent = $response[self::DATA];
        }
        else {
            $verify->verifyRequest =  $response[self::ERROR] ?? null;
        }
        $verify->status = VerifyResult::STATUS_MATCH;

        $this->checkGatewaySuccess($verify);

        $this->checkApiSuccess($verify);

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $verify->status = VerifyResult::STATUS_MISMATCH;
        }

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH);

        if (($verify->match === true) and
            ($verify->apiSuccess === false))
        {
            return $verify;
        }

        $this->checkAmountMismatch($verify);

        return $verify;
    }

    protected function checkApiSuccess(Verify &$verify)
    {
        $verify->apiSuccess = true;

        // If payment status is either failed or created, this is an api failure
        if (($this->input[Entity::PAYMENT][Payment\Entity::STATUS] === Payment\Status::FAILED) or
            ($this->input[Entity::PAYMENT][Payment\Entity::STATUS] === Payment\Status::CREATED))
        {
            $verify->apiSuccess = false;
        }
    }

    protected function checkGatewaySuccess(Verify &$verify)
    {
        $verify->gatewaySuccess = $verify->verifyResponseContent['gateway_success'] ?? false;
    }

    protected function checkAmountMismatch(Verify &$verify)
    {
        $expectedAmount = $this->input[Entity::PAYMENT][Payment\Entity::AMOUNT];
        $actualAmount   =  $verify->verifyResponseContent['amount'] ?? 0 ;

        $verify->amountMismatch = ($expectedAmount !== $actualAmount);
    }

    // ----------------------- Error ---------------------------------------------

    public function checkForErrors($response)
    {
        if ((empty($response['success']) === false) and
            ($response['success'] === true))
        {
            return;
        }

        if (empty($response[self::ERROR]) === true)
        {
            return;
        }

        $errorCode = $response[self::ERROR]['internal_error_code'];

        $class = $this->getErrorClassFromErrorCode($errorCode);

        switch ($class)
        {
            case ErrorClass::GATEWAY:
                $this->handleGatewayErrors($response[self::ERROR], $response);
                break;

            case ErrorClass::BAD_REQUEST:
                $this->handleBadRequestErrors($response[self::ERROR], $response);
                break;

            case ErrorClass::SERVER:
                $this->handleInternalServerErrors($response[self::ERROR]);
                break;

            default:
                throw new Exception\InvalidArgumentException('Not a valid error code class',
                    ['errorClass' => $class]);
        }
    }

    protected function getErrorClassFromErrorCode($code)
    {
        $pos = strpos($code, '_');

        $class = substr($code, 0, $pos);

        if ($class === 'BAD')
        {
            $class = ErrorClass::BAD_REQUEST;
        }

        return $class;
    }

    protected function handleGatewayErrors(array $error, array $response)
    {
        $errorCode = $error['internal_error_code'];

        $gatewayErrorCode = $error['gateway_error_code'] ?? null;

        $gatewayErrorDesc = $error['gateway_error_description'] ?? null;

        switch ($errorCode)
        {
            case ErrorCode::GATEWAY_ERROR_REQUEST_ERROR:
                throw new Exception\GatewayRequestException($errorCode);

            case ErrorCode::GATEWAY_ERROR_TIMED_OUT:
                throw new Exception\GatewayTimeoutException($errorCode);

            default:
                throw new Exception\GatewayErrorException($errorCode,
                    $gatewayErrorCode,
                    $gatewayErrorDesc);
        }
    }

    protected function handleBadRequestErrors(array $error, array $response)
    {
        $errorCode = $error['internal_error_code'];

        $data = $response['data'] ?? null;

        $description = $error['description'] ?? null;

        if ($errorCode == ErrorCode::BAD_REQUEST_PAYMENT_PENDING_AUTHORIZATION)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_PENDING_AUTHORIZATION);
        }

        if ($errorCode == ErrorCode::BAD_REQUEST_PAYMENT_OTP_INCORRECT)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_OTP_INCORRECT,
                null,
                $data
            );
        }

        if (empty($error['gateway_error_code']) === false)
        {
            $this->handleGatewayErrors($error, $response);
        }
        else if ($errorCode !== '')
        {
            throw new Exception\BadRequestException($errorCode);
        }
        else
        {
            throw new Exception\LogicException(
                $description,
                $errorCode,
                $data);
        }
    }

    protected function handleInternalServerErrors(array $error)
    {
        $code = $error['internal_error_code'];

        $data = $error['data'] ?? null;

        $description = $error['description'] ?? 'card payment service request failed';

        throw new Exception\LogicException(
            $description,
            $code,
            $data);
    }

    protected function throwServiceErrorException(\Throwable $e)
    {
        $errorCode = ErrorCode::SERVER_ERROR_CARD_PAYMENT_SERVICE_FAILURE;

        if ((empty($e->getData()) === false) and
            (curl_errno($e->getData()) === CURLE_OPERATION_TIMEDOUT))
        {
            $errorCode = ErrorCode::SERVER_ERROR_CARD_PAYMENT_SERVICE_TIMEOUT;
        }

        throw new Exception\ServerErrorException($e->getMessage(), $errorCode);
    }

    /**
     * Optimizer gateways required additional network token details, like
     * PAR, TRN, TRID for payment processing
     *
     * @param string $gateway
     * @param array $input
     * @return array
     */
    public function getAdditionalNetworkTokenDetailsForOptimizer(string $gateway, array $input): array
    {
        if ((in_array($gateway, Payment\Gateway::OPTIMIZER_TOKENIZATION_SUPPORTED_GATEWAYS, true) === false) or
            ((isset($input[Entity::CARD][Entity::TOKENISED]) === false) or ($input[Entity::CARD][Entity::TOKENISED] === false)) or
            ((isset($input[Entity::TOKEN]) === false) or (isset($input[Entity::TOKEN]['id']) === false))
        )
        {
            return $input;
        }

        if (empty($this->app) === true)
        {
            $this->app = App::getFacadeRoot();
        }

        $cardInput = $input[Entity::CARD];

        // fetch network token associated with payment
        $token = (new Repository())->find($input[Entity::TOKEN]['id']);
        $networkToken = (new Core())->fetchToken($token, false);

        assertTrue(empty($networkToken) === false);

        $tokenisedTerminalId = $networkToken[0][Entity::TOKENISED_TERMINAL_ID] ?? '';
        $tokenisedTerminal = $this->app['terminals_service']->fetchTerminalById($tokenisedTerminalId);

        $trid = '';

        assertTrue(empty($tokenisedTerminal) === false);

        if (empty($tokenisedTerminal) === false)
        {
            switch ($cardInput[Entity::NETWORK_CODE])
            {
                case Card\Network::MC:
                    $trid = $tokenisedTerminal[Entity::GATEWAY_MERCHANT_ID];
                    break;

                case Card\Network::RUPAY:
                    $trid = $tokenisedTerminal[Entity::GATEWAY_MERCHANT_ID2];
                    break;

                case Card\Network::AMEX:
                case Card\Network::VISA:
                    $trid = $tokenisedTerminal[Entity::GATEWAY_TERMINAL_ID];
                    break;

                default:
                    break;
            }
        }

        $par = $networkToken[0][Entity::PROVIDER_DATA][Entity::PAYMENT_ACCOUNT_REFERENCE] ?? '';
        $trn = $networkToken[0][Entity::PROVIDER_DATA][Entity::TOKEN_REFERENCE_NUMBER] ?? '';
        $nri = $networkToken[0][Entity::PROVIDER_DATA][Entity::NETWORK_REFERENCE_ID] ?? '';

        $input[Entity::CARD][Entity::PAYMENT_ACCOUNT_REFERENCE] = $par;
        $input[Entity::CARD][Entity::TOKEN_REFERENCE_NUMBER] = $trn;
        $input[Entity::CARD][Entity::TOKEN_REFERENCE_ID] = $trid;
        $input[Entity::CARD][Entity::NETWORK_REFERENCE_ID] = $nri;

        return $input;
    }
}
