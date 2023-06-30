<?php

namespace RZP\Gateway\Base;

use App;
use Crypt;
use Cache;
use RZP\Http\Request\Requests;
use phpseclib\Crypt\RC4;
use RZP\Gateway\Mpi\Base as Mpi;
use RZP\Models\Admin\ConfigKey;
use Symfony\Component\DomCrawler\Crawler;

use RZP\Exception;
use RZP\Http\Route;
use \WpOrg\Requests\Hooks as Requests_Hooks;
use RZP\Gateway\Upi;
use RZP\Models\Card;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Gateway\Utility;
use RZP\Gateway\Netbanking;
use RZP\Models\Payment\Status;
use RZP\Services\DowntimeMetric;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\VirtualAccount\Receiver;
use RZP\Constants\Entity as ConstantsEntity;

class Gateway
{
    /**
     * Default request read timeout duration in seconds.
     * @var  integer
     */
    const TIMEOUT = 60;

    /**
     * Default request connect timeout duration in seconds.
     * @var  integer
     */
    const CONNECT_TIMEOUT = 10;

    /**
     * Default payment timeout duration in mins.
     * @var  integer
     */
    const PAYMENT_TTL = 20;

    /**
     * Default OTP attempts limit
     * @var integer
     */
    const OTP_ATTEMPTS_LIMIT = 3;

    /**
     * Number of minutes that the card cache key will be stored
     * @var integer
     */
    const CARD_CACHE_TTL = 15;

    /**
     * In gateway responses one particular field contains
     * hash or checksum. This variable will contain that field
     * name.
     */
    const CHECKSUM_ATTRIBUTE = '';

    const CACHE_KEY = 'base_%s_card_details';

    /**
     *  Max number of retry in case first request to gateway fails
     */
    const MAX_RETRY_COUNT = 2;

    /**
     *  curl error numbers for SSL errors. Gateway requests are retried in case
     *  this curl error number is received.
     */
    const RETRIABLE_CURL_ERRORS = [
        35, // cURL error 35: LibreSSL SSL_connect: SSL_ERROR_SYSCALL
        52, // cURL error 52: Empty reply from server
        56, // cURL error 56: LibreSSL SSL_read: SSL_ERROR_SYSCALL
    ];

    /**
     * Actions for which gateway action can be retried on next terminal safely.
     */
    const RETRIABLE_ACTIONS = [
        Action::AUTHENTICATE,
        Action::OTP_GENERATE,
        Action::VALIDATE_VPA,
    ];

    /**
     * Columns of CPS authorization table.
     * To be used while force authorizing failed payment
     */
    const RRN           = 'rrn';
    const AUTH_CODE     = 'auth_code';
    const RECON_ID      = 'recon_id';
    const PAYMENT_ID    = 'payment_id';
    const GATEWAY       = 'gateway';
    const ENTITY_TYPE   = 'entity_type';

    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * Trace instance for tracing
     * @var $trace Trace
     */
    protected $trace;

    protected $repo;

    /**
     * @var array
     */
    protected $input;

    /**
     * Action being taken currently
     * @var string
     */
    protected $action;

    /**
     * Whether the gateway supports topup payments
     * @var boolean
     */
    protected $topup = false;

    /**
     * Whether the gateway supports authorizing payments.
     * @var boolean
     */
    protected $authorize = false;

    /**
     * Whether the gateway supports otp flow.
     * @var boolean
     */
    protected $canRunOtpFlow = false;

    /**
     * The state in which the api is operating
     * that is live/test
     * @var string
     */
    protected $mode;

    protected $env;

    /**
     * Denotes if the gateway is a mock
     * @var boolean
     */
    protected $mock;

    /**
     * Namespacing for URL's
     * used in case where multiple
     * domains need to be supported
     * @var string
     */
    protected $domainType;

    /**
     * Denotes if running in testing env
     * @var boolean
     */
    protected $testing;

    /**
     * Gateway's config present in app/config/gateway.php
     *
     * @var array
     */
    protected $config;

    protected $proxyEnabled;

    /**
     * Api Route instance
     *
     * @var Route
     */
    protected $route;

    /**
     * @var $terminal \RZP\Models\Terminal\Entity
     */
    protected $terminal;

    protected $gateway;

    /**
     * Laravel request class instance
     * @var Request
     */
    protected $request;

    /**
     * Some gateways whitelist our IP and requests to them can only
     * be sent from those IP.
     *
     * Proxy address specifies the proxy through which these requests
     * are routed. The proxy simply sits at the public IP machine
     * and mostly acts transparently.
     *
     * @var string
     */
    protected $proxy;

    protected $sortRequestContent = true;

    protected $externalMockDomain;

    protected $paymentId;

    protected $wasGatewayHit = false;

    protected $shouldMapLateAuthorized = false;

    /**
     * @var $downtimeMetric DowntimeMetric Singleton for storing count of gateway
     * requests data with success-failure count and error codes (if any)
     * Used by Downtime Detectors in Payment processor to decide whether to mark the
     * gateway as down or not.
     */
    protected $downtimeMetric;

    /**
     * Gateway Sanitize key is used to mask the gateway requests/responses where some
     * sensitive information is getting being traced. This key can be set as secret from
     * Gateway config and could be different for different gateways.
     * @var string
     */
    protected $gatewaySanitizeKey = null;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->env = $this->app['env'];

        $this->downtimeMetric = $this->app['gateway_downtime_metric'];

        if ($this->env === 'testing')
        {
            $this->testing = true;
        }

        $this->loadGatewayConfig();

        $this->repo = $this->getRepository();

        $this->route = $this->app['api.route'];

        $this->request = $this->app['request'];

        $this->cache = $this->app['cache'];

        $this->externalMockDomain = env('EXTERNAL_MOCK_GATEWAY_DOMAIN');
    }

    public function call($action, $input)
    {
        try
        {
            $this->wasGatewayHit = false;

            $response = $this->$action($input);

            $this->pushDimensions($action, $input, Metric::SUCCESS, null, 200);

            if ($this->wasGatewayHit === true)
            {
                $this->downtimeMetric->setMetrics($this->gateway, DowntimeMetric::Success);
            }

            return $response;
        }
        catch (\Throwable $exc)
        {
            if ($this->wasGatewayHit === true)
            {
                if ($exc instanceof Exception\BaseException)
                {
                    $this->downtimeMetric->setMetrics($this->gateway, DowntimeMetric::Failure,
                        $exc->getError()->getInternalErrorCode());
                }
                else
                {
                    $this->downtimeMetric->setMetrics($this->gateway, DowntimeMetric::Failure,
                        ErrorCode::SERVER_ERROR);
                }
            }

            $previousExc = $exc->getPrevious();

            if (($previousExc instanceof \WpOrg\Requests\Exception) and
                    ($previousExc->getType() === 'curlerror'))
            {
                $excData = curl_errno($previousExc->getData());

                $this->pushDimensions($action, $input, Metric::CURL_ERROR, $excData);
            }
            else
            {
                $excData = 'UKNOWN';

                $statusCode = null;

                if($exc instanceof Exception\BaseException)
                {
                    $excData = $exc->getError()->getClass();

                    $statusCode = $exc->getError()->getHttpStatusCode();
                }

                $this->pushDimensions($action, $input, Metric::FAILED, $excData, $statusCode);
            }

            throw $exc;
        }
    }

    public function authorize(array $input)
    {
        $this->input = $input;
        $this->action = Action::AUTHORIZE;

     // Risk validation for gateways using this function before authenticate and Authorize of the payment.
        if (isset($input['payment_analytics']['risk_score']) === true)
        {
            if (($input['payment_analytics']['risk_engine'] === Payment\Analytics\Metadata::SHIELD_V2) or
                ($input['payment_analytics']['risk_engine'] === Payment\Analytics\Metadata::MAXMIND_V2))
            {
                $this->validateRiskScore($input);
            }
        }
    }

    /**
     * Handles gateway callback
     *
     * @param array $input
     *
     * @return array|null
     * @throws Exception\GatewayErrorException
     */
    public function callback(array $input)
    {
        if (empty($input['gateway']) === true)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_CALLBACK_EMPTY_INPUT);
        }

        $this->input = $input;
        $this->action = Action::CALLBACK;
    }

    public function callbackOtpSubmit(array $input)
    {
        $this->input = $input;
    }

    public function omniPay(array $input)
    {
        if (empty($input['gateway']) === true)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_OMNIPAY_EMPTY_INPUT);
        }

        $this->input = $input;
        $this->action = Action::OMNI_PAY;
    }

    public function createTerminal(array $input)
    {
        $this->input = $input;

        $this->action = ACTION::CREATE_TERMINAL;
    }

    public function enableTerminal(array $input)
    {
        $this->input = $input;

        $this->action = Action::ENABLE_TERMINAL;
    }

    public function disableTerminal(array $input)
    {
        $this->input = $input;

        $this->action = Action::DISABLE_TERMINAL;
    }

    public function debit(array $input)
    {
        $this->input = $input;
    }

    public function checkBalance(array $input)
    {
        $this->input = $input;
    }

    public function capture(array $input)
    {
        $this->input = $input;
        $this->action = Action::CAPTURE;

        if (($input['payment'][Payment\Entity::STATUS] === Status::AUTHORIZED) or
            (($input['payment'][Payment\Entity::STATUS] === Status::CAPTURED) and
            (array_key_exists(Payment\Entity::GATEWAY_CAPTURED, $input['payment']) === true) and
            ($input['payment'][Payment\Entity::GATEWAY_CAPTURED] === null)))
        {
            return;
        }
        else
        {
            throw new Exception\RuntimeException(
                'Payment status should be authorized or if captured, gateway captured should not be set',
                ['payment_id' => $input['payment']['id']]);
        }
    }

    public function advice(array $input)
    {
        $this->input = $input;
        $this->action = Action::ADVICE;
    }

    public function refund(array $input)
    {
        $this->input = $input;
        $this->action = Action::REFUND;
    }

    public function reverse(array $input)
    {
        $this->input = $input;
        $this->action = Action::REVERSE;
    }

    public function void(array $input)
    {
        $this->input = $input;
        $this->action = Action::VOID;
    }

    public function verify(array $input)
    {
        $this->input = $input;
        $this->action = Action::VERIFY;
    }

    public function action(array $input, $action)
    {
        $this->action = $action;
        $this->input = $input;
    }

    public function verifyRefund(array $input)
    {
        throw new Exception\LogicException(
            'Verify Refund is not implemented');
    }

    public function reconcile(array $input)
    {
        throw new Exception\LogicException(
            'Reconcile is not implemented');
    }

    public function canTopup()
    {
        return $this->topup;
    }

    public function setGatewayParams($input, $mode, $terminal)
    {
        $this->setMode($mode);

        $this->setTerminal($terminal);
    }

    public function setTerminal($terminal)
    {
        $this->terminal = $terminal;
    }

    public function setMode($mode)
    {
        $this->mode = $mode;
    }

    public function setMock($mock)
    {
        assertTrue (is_bool($mock));

        $this->mock = $mock;
    }

    /**
     * if bharatQr payment is not successful $valid will be set to false in BharatQr Service,in
     * that case the reason of the failure is shared with gateway using exception thrown
     * else the value of $valid will be true and we will send the respective response to gateway.
     */
    public function getBharatQrResponse(bool $valid, $gatewayInput = null, $exception = null)
    {
        if ($valid === true)
        {
            $xml = '<RESPONSE>OK</RESPONSE>';
        }
        else
        {
            $xml = '<RESPONSE>NOK</RESPONSE>';
        }

        $response = \Response::make($xml);

        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');

        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }

    protected function checkApiSuccess(Verify $verify)
    {
        $verify->apiSuccess = true;

        $input = $verify->input;

        // If payment status is either failed or created,
        // this is an api failure
        if (($input[ConstantsEntity::PAYMENT][Payment\Entity::STATUS] === Payment\Status::FAILED) or
            ($input[ConstantsEntity::PAYMENT][Payment\Entity::STATUS] === Payment\Status::CREATED))
        {
            $verify->apiSuccess = false;
        }
    }

    public function getAction()
    {
        return $this->action;
    }

    public function getMode()
    {
        return $this->mode;
    }

    protected function getIntegerFormattedAmount(string $amount)
    {
        return (int) number_format(($amount * 100), 0, '.', '');
    }

    protected function assertPaymentId($expectedPaymentId, $actualPaymentId)
    {
        if ($actualPaymentId !== $expectedPaymentId)
        {
            throw new Exception\LogicException(
                'Data tampering found.', null, [
                    'expected' => $expectedPaymentId,
                    'actual'   => $actualPaymentId
                ]);
        }
    }

    protected function assertAmount($expectedAmount, $actualAmount)
    {
        if ($expectedAmount !== $actualAmount)
        {
            throw new Exception\LogicException(
                'Amount tampering found.',
                ErrorCode::SERVER_ERROR_AMOUNT_TAMPERED, [
                    'expected' => $expectedAmount,
                    'actual'   => $actualAmount
                ]);
        }
    }

    protected function getAcquirerData($input, $gatewayPayment)
    {
        $acquirer = [];

        switch ($input['payment']['method'])
        {
            case Payment\Method::CARD:
            case Payment\Method::EMI:
                $acquirer['acquirer'] = [
                    Payment\Entity::REFERENCE2 => $gatewayPayment->getAuthCode(),
                ];
                break;
        }

        return $acquirer;
    }

    protected function getCallbackResponseData(array $input, $response = [])
    {
        $response[Payment\Entity::TWO_FACTOR_AUTH] = Payment\TwoFactorAuth::PASSED;

        // Keeping this same for eMandate. However, this needs
        // to be updated for different authentication type
        if (($input['payment'][Payment\Entity::METHOD] === Payment\Method::NETBANKING) or
            ($input['payment'][Payment\Entity::METHOD] === Payment\Method::EMANDATE))
        {
            $response[Payment\Entity::TWO_FACTOR_AUTH] = Payment\TwoFactorAuth::UNAVAILABLE;
        }

        return $response;
    }

    public function setInput(array $input)
    {
        $this->input = $input;

        return $this;
    }

    protected function getHashValueFromContent(array $content)
    {
        return $content[static::CHECKSUM_ATTRIBUTE];
    }

    protected function verifySecureHash(array $content)
    {
        $actual = $this->getHashValueFromContent($content);

        unset($content[static::CHECKSUM_ATTRIBUTE]);

        $generated = $this->generateHash($content);

        $this->compareHashes($actual, $generated);
    }

    protected function compareHashes($actual, $generated)
    {
        if (hash_equals($actual, $generated) === false)
        {
            $this->trace->info(
                TraceCode::GATEWAY_CHECKSUM_VERIFY_FAILED,
                [
                    'actual'    => $actual,
                    'generated' => $generated
                ]);

            throw new Exception\RuntimeException('Failed checksum verification');
        }
    }

    protected function isSecondRecurringPaymentRequest($input)
    {
        if (($input['payment']['recurring'] === true) and
            ($input['payment']['recurring_type'] === 'auto'))
        {
            return true;
        }

        return false;
    }

    protected function isMotoTransactionRequest($input)
    {
        if (($input['terminal']->isMoto() === true) and
            ($input['payment']['auth_type'] === Payment\AuthType::SKIP))
        {
            return true;
        }

        return false;
    }

    public function generateRefunds($input)
    {
        $paymentIds = array_map(function($row)
        {
            return $row['payment']['id'];
        }, $input['data']);

        $payments = $this->repo->fetchByPaymentIdsAndAction(
                                $paymentIds, Action::AUTHORIZE);

        $payments = $payments->getDictionaryByAttribute(Entity::PAYMENT_ID);

        $input['data'] = array_map(function($row) use ($payments)
        {
            $paymentId = $row['payment']['id'];

            if (isset($payments[$paymentId]))
            {
                $row['gateway'] = $payments[$paymentId]->toArray();
            }

            return $row;
        }, $input['data']);

        $ns = $this->getGatewayNamespace();

        $class = $ns . '\\' . 'RefundFile';

        return (new $class)->generate($input);
    }

    protected function sendGatewayRequest($request)
    {
        return $this->retryHandler(
            [$this, 'sendExternalRequest'],
            [$request],
            [$this, 'shouldRetry'],
            [$this, 'getMaxRetryCount']);
    }

    //
    // TODO: Move this to base card gateway from here
    //
    protected function decideRiskValidationStep($input, $authResponse)
    {
        $eci = $authResponse[Mpi\Entity::ECI];

        $networkCode = Card\Network::getCode($input['card']['network']);

        $isInternational = $input['card']['international'];

        if (($isInternational !== true) or
            ((($networkCode === Card\Network::VISA) and ($eci !== '06')) or
             (($networkCode === Card\Network::MC) and ($eci !== '01'))))
        {
            return;
        }

        $this->validateRiskScore($input);
    }

    protected function validateRiskScore($input)
    {
        $riskScore = $input['payment_analytics']['risk_score'];

        if (($riskScore > $input['merchant']->getRiskThreshold()) and
            (($input['card'][Card\Entity::INTERNATIONAL] === true) and
             ($input['card'][Card\Entity::NETWORK] !== Card\Network::AMEX)))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD_GATEWAY,
                null,
                null,
                ['riskScore' => $riskScore],
                null,
                Action::AUTHENTICATE);
        }
    }

    protected function sendExternalRequest($request)
    {
        if (isset($request['options']) === false)
        {
            $request['options'] = [];
        }

        //
        // Intentionally setting verify to null, so Requests does not use its default
        // cacert (which is outdated), and curl ends up using the OS cacert by default.
        //
        // Ref:
        // [1] Requests::get_default_options
        // [2] Requests_Transport_cURL -> requesst
        //
        if (isset($request['options']['verify']) === false)
        {
            $request['options']['verify'] = null;
        }

        if (isset($request['headers']) === false)
        {
            $request['headers'] = [];
        }

        $method = 'post';

        if (isset($request['method']))
        {
            $method = $request['method'];
        }

        if (isset($request['options']['timeout']) === false)
        {
            $request['options']['timeout'] = static::TIMEOUT;
        }

        if (isset($request['options']['connect_timeout']) === false)
        {
            $request['options']['connect_timeout'] = static::CONNECT_TIMEOUT;
        }

        if ((isset($request['options']['hooks']) === false) or
            ($request['options']['hooks'] instanceof Requests_Hooks === false))
        {
            $request['options']['hooks'] = new Requests_Hooks();
        }

        $request['options']['hooks']->register('curl.after_request', [$this, 'traceCurlInfo']);

        try
        {
            $method = strtoupper($method);

            $this->wasGatewayHit = true;

            $response = Requests::request(
                $request['url'],
                $request['headers'],
                $request['content'],
                $method,
                $request['options']);
        }
        catch (\WpOrg\Requests\Exception $e)
        {
            $this->exception = $e;

            //
            // Some error occurred.
            // Check that whether the gateway response timed out.
            // Mostly it should be gateway timeout only
            //
            if (Utility::checkTimeout($e))
            {
                $ex = new Exception\GatewayTimeoutException($e->getMessage(), $e);

                if (in_array($this->action, static::RETRIABLE_ACTIONS, true) === true)
                {
                    $ex->markSafeRetryTrue();
                }
            }
            else
            {
                $ex = new Exception\GatewayRequestException($e->getMessage(), $e);

                if (in_array($this->action, static::RETRIABLE_ACTIONS, true) === true)
                {
                    $ex->markSafeRetryTrue();
                }
            }

            throw $ex;
        }

        $this->validateResponse($response);

        // echo 'Response - ' . PHP_EOL . $response->body . PHP_EOL . PHP_EOL;
        // \Log::info('Response - ' . PHP_EOL . $response->body . PHP_EOL . PHP_EOL);

        return $response;
    }

    /**
     * @param callable $callable -- this contains the class object and the function name as indexed array
     * @param array $arguments -- this contains the function params to be passed to the function name passed
     * in $objFunc
     * @param callable $checks -- closure to check if retry is needed
     * @param callable $retryCount -- closure which returns max number of retries we want, after which
     * exception is thrown
     * @return $response -- return the response of the closure $callable
     * @throws \Exception
     */
    protected function retryHandler(callable $callable,
                                    array $arguments,
                                    callable $checks,
                                    callable $retryCount)
    {
        $currentRetryCount = 1;

        while (true)
        {
            try
            {
                $response = call_user_func_array($callable, $arguments);

                return $response;
            }
            catch (\Exception $exc)
            {
                if ((call_user_func($checks, $exc) === true) and
                    ($currentRetryCount < call_user_func($retryCount)))
                {
                    $currentRetryCount++;

                    $this->trace->traceException($exc,
                        Trace::WARNING,
                        TraceCode::GATEWAY_REQUEST_RETRIED_DUE_TO_CURL_ISSUES,
                        [
                            'current_count' => $currentRetryCount,
                        ]);

                    continue;
                }

                throw $exc;
            }
        }
    }

    protected function shouldRetry($e)
    {
        // only authorize, validateVpa actions are retried for libressl errors.
        // this check will be removed if everything goes fine.
        if ((empty($this->action) === true) or
            (in_array($this->action, $this->getActionsToRetry(), true) === false))
        {
            return false;
        }

        $previousExc = $e->getPrevious();

        if (($previousExc instanceof \WpOrg\Requests\Exception) and
                ($previousExc->getType() === 'curlerror'))
            {
                $errorNumber = curl_errno($previousExc->getData());

                if (in_array($errorNumber, static::RETRIABLE_CURL_ERRORS, true) === true)
                {
                    return true;
                }
            }

        return false;
    }

    protected function getActionsToRetry()
    {
        return [Action::AUTHORIZE, Action::VALIDATE_VPA];
    }

    protected function getMaxRetryCount()
    {
        return static::MAX_RETRY_COUNT;
    }

    protected function validateResponse($response)
    {
        if (in_array($response->status_code, [503, 504], true) === true)
        {
            throw new Exception\GatewayTimeoutException('Response status: '. $response->status_code);
        }
        else if ($response->status_code >= 500)
        {
            $e = new Exception\GatewayErrorException(
                        ErrorCode::GATEWAY_ERROR_FATAL_ERROR);

            $data = ['status_code' => $response->status_code, 'body' => $response->body];
            $e->setData($data);

            if (in_array($this->action, static::RETRIABLE_ACTIONS, true) === true)
            {
                $e->markSafeRetryTrue();
            }

            throw $e;
        }
        else if ($response->status_code >= 300)
        {
            //
            // Trace non 200 status codes to figure out what else
            // needs to be handled here later.
            //

            $this->trace->info(
                TraceCode::GATEWAY_PAYMENT_RESPONSE,
                [
                    'status_code' => $response->status_code,
                    'gateway' => $this->gateway
                ]);
        }
    }

    public function traceCurlInfo($headers, $info)
    {
        $verbose = $this->isCurlInfoVerboseLogEnabled();

        if ($verbose === true)
        {
            $this->trace->info(TraceCode::GATEWAY_REQUEST_CURL_INFO,
                [
                    'total_time' => $info['total_time'],
                    'connect_time' => $info['connect_time'],
                    'redirect_time' => $info['redirect_time'],
                    'namelookup_time' => $info['namelookup_time'],
                    'pretransfer_time' => $info['pretransfer_time'],
                    'starttransfer_time' => $info['starttransfer_time'],
                    'primary_ip' => $info['primary_ip'] ?? 'nil',
                ]);
        }

        $this->pushGatewayMetrics($info['total_time']*1000);
    }

    protected function pushGatewayMetrics($time)
    {
        $dimensions = [
            'gateway' => $this->gateway ?? 'none',
            'action'  => $this->action ?? 'none',
        ];

        try
        {
            $metricsDriver = app('trace')->metricsDriver(Metric::DOGSTATSD_DRIVER);

            /**
             * @var $metricsDriver \Razorpay\Metrics\Drivers\Driver
             */
            $metricsDriver->histogram(Metric::GATEWAY_REQUEST_TIME,
                $time,
                $dimensions);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::GATEWAY_METRIC_DIMENSION_PUSH_FAILED,
                $dimensions);
        }
    }

    protected function isCurlInfoVerboseLogEnabled(): bool
    {
        $verbose = false;

        try
        {
            $verbose = (bool) Cache::get(ConfigKey::CURL_INFO_LOG_VERBOSE, false);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException($ex, Trace::ERROR, TraceCode::CURL_INFO_CONFIG_FETCH_ERROR);
        }

        return $verbose;
    }

    /**
     * verify flow - methods to implement:
     * 1. sendPaymentVerifyRequest - sends request to gateway, parse the response and set it
     *  on $verify->verifyResponseContent
     * 2. checkGatewaySuccess - return true/false after checking gateway status
     * 3. setVerifyAmountMismatch - assert amount and return true/false
     * 4. getVerifyAttributesToSave - return gatewayPayment attributes array to save
     *
     */
    protected function runPaymentVerifyFlow($verify)
    {
        // This payment is the gateway entity payment.
        // Also sets this gateway payment in the verify object's payment.
        $gatewayPayment = $this->getPaymentToVerify($verify);

        if (($gatewayPayment === null) and
            ($this->shouldReturnIfPaymentNullInVerifyFlow($verify)))
        {
            $this->trace->warning(
                TraceCode::GATEWAY_PAYMENT_VERIFY,
                [
                    'payment_id' => $verify->input['payment']['id'],
                    'message'    => 'payment id not found in the gateway database',
                    'gateway'    => $this->gateway
                ]
            );

            return null;
        }

        $this->sendPaymentVerifyRequest($verify);

        $this->verifyPayment($verify);

        if (($verify->amountMismatch === true) and
            ($verify->throwExceptionOnMismatch))
        {
            throw $this->getAmountMismatchExceptionInVerify($verify);
        }

        if (($verify->match === false) and
            ($verify->throwExceptionOnMismatch))
        {
            throw new Exception\PaymentVerificationException(
                $verify->getDataToTrace(),
                $verify);
        }

        return $verify->getDataToTrace();
    }

    protected function runPaymentVerifyFlowGateway($verify)
    {
        // This payment is the gateway entity payment.
        // Also sets this gateway payment in the verify object's payment.
        $gatewayPayment = $this->getPaymentToVerify($verify);

        if (($gatewayPayment === null) and
            ($this->shouldReturnIfPaymentNullInVerifyFlow($verify)))
        {
            $this->trace->warning(
                TraceCode::GATEWAY_PAYMENT_VERIFY,
                [
                    'payment_id' => $verify->input['payment']['id'],
                    'message'    => 'payment id not found in the gateway database',
                    'gateway'    => $this->gateway
                ]
            );

            return null;
        }

        $this->sendPaymentVerifyRequestGateway($verify);

        return $verify->getDataToTrace();
    }

    /**
     *
     * @param Verify $verify
     * @return Exception\RuntimeException
     */
    protected function getAmountMismatchExceptionInVerify(Verify $verify)
    {
        return new Exception\RuntimeException(
            'Payment amount verification failed.',
            [
                'payment_id' => $this->input['payment']['id'],
                'gateway'    => $this->gateway
            ]);
    }

    public function preProcessServerCallback($input): array
    {
        return $input;
    }

    public function verifyBharatQrNotification($input)
    {
        return $input;
    }

    protected function shouldReturnIfPaymentNullInVerifyFlow($verify)
    {
        if (($verify->input['payment']['status'] === 'failed') or
            ($verify->input['payment']['status'] === 'created'))
        {
            return true;
        }

        return false;
    }

    protected function traceGatewayPaymentRequest(
        array $request,
        $input,
        $traceCode = TraceCode::GATEWAY_PAYMENT_REQUEST)
    {
        $this->trace->info(
            $traceCode,
            [
                'request'    => $request,
                'gateway'    => $this->gateway,
                'payment_id' => $input['payment']['id'],
            ]);
    }

    protected function traceVirtualAccountCreateRequest(
        array $request,
        $traceCode = TraceCode::GATEWAY_CREATE_VIRTUAL_ACCOUNT_REQUEST)
    {
        $this->trace->info(
            $traceCode,
            [
                'request'    => $request,
                'gateway'    => $this->gateway,
            ]);
    }

    protected function traceVirtualAccountResponse(
        $response,
        $traceCode = TraceCode::GATEWAY_CREATE_VIRTUAL_ACCOUNT_RESPONSE)
    {
        $this->trace->info(
            $traceCode,
            [
                'response'   => $response,
                'gateway'    => $this->gateway
            ]);
    }

    protected function traceGatewayTerminalOnboarding(
        array $data,
        $dataKey,
        $input,
        $traceCode)
    {
        $this->trace->info(
            $traceCode,
            [
                $dataKey     => $data,
                'gateway'    => $input['gateway'],
            ]);
    }

    protected function traceGatewayPaymentResponseForMozart(
        $response,
        $input,
        $traceCode = TraceCode::GATEWAY_RESPONSE)
    {
        $this->trace->info(
            $traceCode,
            [
                'action'     => $this->action,
                'response'   => $response,
                'gateway'    => $this->gateway,
                'payment_id' => $input['entities']['payment']['id'],
            ]);
    }

    protected function traceGatewayPaymentResponse(
        $response,
        $input,
        $traceCode = TraceCode::GATEWAY_PAYMENT_RESPONSE)
    {
        $this->trace->info(
            $traceCode,
            [
                'response'   => $response,
                'gateway'    => $this->gateway,
                'payment_id' => $input['payment']['id'],
            ]);
    }


    protected function getPaymentToVerify(Verify $verify)
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndAction(
                    $verify->input['payment']['id'], Action::AUTHORIZE);

        $verify->payment = $gatewayPayment;

        return $gatewayPayment;
    }

    protected function getNamespace()
    {
        return substr(get_called_class(), 0, strrpos(get_called_class(), '\\'));
    }

    protected function getNamespaceWithoutMock()
    {
        $ns = $this->getNamespace();

        $pos = strrpos($ns, '\Mock');

        if ($pos !== false)
        {
            $ns = substr($ns, 0, $pos);
        }

        return $ns;
    }

    protected function getGatewayNamespace()
    {
        return $this->getNamespaceWithoutMock();
    }

    public function generateHash($content)
    {
        return $this->getHashOfArray($content);
    }

    protected function getHashOfArray($content)
    {
        if ($this->sortRequestContent)
        {
            ksort($content);
        }

        $hashString = $this->getStringToHash($content);

        return $this->getHashOfString($hashString);
    }

    public function getSecret()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->getTestSecret();
        }

        return $this->getLiveSecret();
    }

    protected function getTestSecret()
    {
        assert($this->mode === Mode::TEST);

        return $this->config['test_hash_secret'];
    }

    protected function getLiveSecret()
    {
        return $this->input['terminal']['gateway_secure_secret'];
    }

    protected function getLiveSecret2()
    {
        return $this->input['terminal']['gateway_secure_secret2'];
    }

    public function getTerminalPassword()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->getTestTerminalPassword();
        }

        return $this->getLiveTerminalPassword();
    }

    protected function getTestTerminalPassword()
    {
        assert($this->mode === Mode::TEST);

        return $this->config['test_terminal_password'];
    }

    protected function getLiveTerminalPassword()
    {
        return $this->input['terminal']['gateway_terminal_password'];
    }

    protected function getLiveTerminalPassword2()
    {
        return $this->input['terminal']['gateway_terminal_password2'];
    }

    protected function getLiveGatewayTerminalId()
    {
        return $this->input['terminal']['gateway_terminal_id'];
    }

    protected function isTestMode() : bool
    {
        return ($this->mode === Mode::TEST);
    }

    protected function isLiveMode() : bool
    {
        return ($this->mode === Mode::LIVE);
    }

    protected function getNewGatewayPaymentEntity()
    {
        $class = $this->getGatewayNamespace() . '\Entity';

        return new $class;
    }

    protected function getStringToHash($content, $glue = '')
    {
        return implode($glue, $content);
    }

    protected function getHashOfString($str)
    {
        return $str;
    }

    protected function getUrlDomain()
    {
        $urlClass = $this->getGatewayNamespace() . '\Url';

        $domainType = $this->domainType ?? $this->mode;

        $domainConstantName = strtoupper($domainType).'_DOMAIN';

        return constant($urlClass . '::' .$domainConstantName);
    }

    protected function getRelativeUrl($type)
    {
        $ns = $this->getGatewayNamespace();

        return constant($ns . '\Url::' . $type);
    }

    protected function getUrl($type = null)
    {
        $urlDomain = $this->getUrlDomain();

        $type = $type ?? $this->action;

        $type = strtoupper($type);

        if (($this->env === 'func' or $this->env === 'automation' or $this->env === 'bvt' or $this->env === 'availability' or $this->env === 'perf' or $this->env === 'perf2') and
            (isset($this->externalMockDomain) === true))
        {
            return $this->getExternalMockUrl($type);
        }

        if ($this->env === 'axis')
        {
            return $this->getAxisWrapperUrl($type,$urlDomain);
        }

        return $urlDomain . $this->getRelativeUrl($type);
    }

    protected function loadGatewayConfig()
    {
        $configGatewayStr = 'gateway.' . $this->gateway;

        $this->config = $this->app['config']->get($configGatewayStr);

        $this->proxy = $this->app['config']->get('gateway.proxy_address');

        $this->proxyEnabled = $this->app['config']->get('gateway.proxy_enabled');
    }

    protected function getFormValues($form, $url)
    {
        $crawler = new Crawler($form, $url);

        $formCrawler = $crawler->filter('form');

        if ($formCrawler->count() === 0)
        {
            // This happens because hdfc nb gateway is down.
            // We will need to verify the request later.
            throw new Exception\GatewayTimeoutException(
                'Payment verify request to Hdfc nb gateway timed out');
        }

        $form = $formCrawler->form();

        $content = $form->getValues();

        return $content;
    }

    protected function getTestAccessCode()
    {
        $code = null;

        if (isset($this->config['test_access_code']))
        {
            $code = $this->config['test_access_code'];
        }

        return $code;
    }

    protected function getTestMerchantId()
    {
        $code = null;

        if (isset($this->config['test_merchant_id']))
        {
            $code = $this->config['test_merchant_id'];
        }

        return $code;
    }

    protected function getLiveMerchantId()
    {
        return $this->input['terminal']['gateway_merchant_id'];
    }

    protected function getTestMerchantId2()
    {
        return $this->config['test_merchant_id2'];
    }

    protected function getLiveMerchantId2()
    {
        return $this->input['terminal']['gateway_merchant_id2'];
    }

    protected function getLiveGatewayAccessCode()
    {
        return $this->input['terminal']['gateway_access_code'];
    }

    protected function getDataWithFieldsInOrder($content, $orderedFields)
    {
        $orderedData = [];

        foreach ($orderedFields as $key)
        {
            if (isset($content[$key]))
            {
                $orderedData[$key] = $content[$key];
            }
        }

        return $orderedData;
    }

    protected function getStandardRequestArray($content = [], $method = 'post', $type = null)
    {
        $request = array(
            'url'       => $this->getUrl($type),
            'method'    => $method,
            'content'   => $content,
        );

        return $request;
    }

    protected function getOtpSubmitRequest(array $input): array
    {
        $request = [
            'url' => $input['otpSubmitUrl'],
            'method' => 'post',
            'content' => [
                'next' => [
                    'resend_otp'
                ]
            ]
        ];

        return $request;
    }

    protected function getDynamicMerchantName(Merchant\Entity $merchant, $limit = 20) : string
    {
        $label = $merchant->getBillingLabel();

        $label = preg_replace('/[^a-zA-Z0-9 ]+/', '', $label);

        if (empty($label) === true)
        {
            $label = 'Razorpay Payments';
        }

        return str_limit($label, $limit, '');
    }

    protected function verifyOtpAttempts($payment, $limit = null)
    {
        if ($limit === null)
        {
            $limit = self::OTP_ATTEMPTS_LIMIT;
        }

        if ($payment['otp_attempts'] >= $limit)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_OTP_VALIDATION_ATTEMPT_LIMIT_EXCEEDED, null, null,
            ['method'=> $payment['method']]);
        }
    }

    public function getGatewayCertDirPath()
    {
        $certificatePath = $this->app['config']->get('gateway.certificate_path');

        $gatewayCertPath = $certificatePath . '/' . $this->getGatewayCertDirName();

        if (file_exists($gatewayCertPath) === false)
        {
            //
            // We are using 077 permissions because default is 0777
            // We want recursive generation of path for this case
            // http://php.net/manual/en/function.mkdir.php
            //
            mkdir($gatewayCertPath, 0777, true);
        }

        return $gatewayCertPath;
    }

    protected function getRepository()
    {
        $gateway = $this->gateway;

        return $this->app['repo']->$gateway;
    }

    protected function getCacheKey($input)
    {
        return $this->gateway . '_' . $input['payment']['id'];
    }

    protected function getProcessedRefunds()
    {
        $refunds = $this->cache->get(ConfigKey::GATEWAY_PROCESSED_REFUNDS);

        if (empty($refunds) === true)
        {
            $refunds = [];
        }

        return $refunds;
    }

    protected function getUnprocessedRefunds()
    {
        $refunds = $this->cache->get(ConfigKey::GATEWAY_UNPROCESSED_REFUNDS);

        if (empty($refunds) === true)
        {
            $refunds = [];
        }

        return $refunds;
    }

    protected function isSecondRecurringPayment(array $input)
    {
        if (($input['payment']['recurring'] === true) and
            ($input['terminal']->isNon3DSRecurring() === true))
        {
            return true;
        }

        return false;
    }

    protected function getMappedAttributes($attributes)
    {
        $attr = [];

        $map = $this->map;

        foreach ($attributes as $key => $value)
        {
            if ((isset($value) === true) and
                ($value !== '') and
                (isset($map[$key])))
            {
                $newKey = $map[$key];
                $attr[$newKey] = $value;
            }
        }

        return $attr;
    }

    protected function xmlToArray($xml)
    {
        $e = null;
        $res = null;

        try
        {
            $res = simplexml_load_string($xml);

            return json_decode(json_encode($res), true);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e);

            throw new Exception\RuntimeException(
                'Failed to convert xml to array',
                ['xml' => $xml],
                $e);
        }
    }

    protected function failIfRequired(array $input)
    {
        if ((isset($input['test_success']) === true) and
            ($input['test_success'] === false))
        {
            throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_SUBSCRIPTION_SCHEDULED_FAILURE);
        }
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
                    TraceCode::GATEWAY_PAYMENT_ERROR,
                    ['json' => $json]);

                throw new Exception\RuntimeException(
                    'Failed to convert json to array',
                    ['json' => $json]);
        }
    }

    /*
     * Updates the gateway payment entity
     *
     * @param gatewayPayment Gateway\Base\Entity      Gateway Payment Entity
     * @param attributes     array
     * @param mapped         boolean                 If the attrs are mapped to gateway codes
     */
    protected function updateGatewayPaymentEntity(
        Entity $gatewayPayment,
        array $attributes,
        bool $mapped = true)
    {
        if ($mapped === true)
        {
            $attributes = $this->getMappedAttributes($attributes);
        }

        $gatewayPayment->fill($attributes);

        $this->getRepository()->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function isBharatQrPayment(): bool
    {
        return ((empty($this->input['payment'][Payment\Entity::RECEIVER_TYPE]) === false) and
                ($this->input['payment'][Payment\Entity::RECEIVER_TYPE] === Receiver::QR_CODE));
    }

    protected function isUpiTransferPayment(): bool
    {
        return ((empty($this->input['payment'][Payment\Entity::RECEIVER_TYPE]) === false) and
                ($this->input['payment'][Payment\Entity::RECEIVER_TYPE] === Receiver::VPA));
    }

    /**
     * Returns the external mock url
     * Used for gateway testing using mock in func
     * Appends the gateway string and relative url for the external mock domain
     *
     * @param  string $type Indicates which relative URL to use
     * @return string       Complete URL to be used
     */
    protected function getExternalMockUrl(string $type)
    {
        return $this->externalMockDomain . '/' . $this->gateway . $this->getRelativeUrl($type);
    }

    protected function getAxisWrapperUrl(string $type, string $urlDomain)
    {
        return $urlDomain . $this->getRelativeUrl($type);
    }

    protected function pushDimensions($action, $input, $status, $excData = null, $statusCode = null)
    {
        if (($this->mode === Mode::TEST) and
            ($this->app->runningUnitTests() === false))
        {
            return;
        }

        $gatewayMetric = new Metric;

        $gateway = $this->gateway;

        if ($gateway === 'mozart')
        {
            $gateway = $this->getGateway($input, $action);
        }

        $gatewayMetric->pushGatewayDimensions($action, $input, $status, $gateway, $excData, $statusCode);

        $gatewayMetric->pushOptimiserGatewayDimensions($action,$input,$status,$gateway,$excData,$statusCode);
    }

    protected function isDuplicateUnexpectedPayment($callbackData)
    {
        throw new Exception\LogicException(
            'Unexpected Payment is not supported');
    }

    protected function isValidUnexpectedPayment($callbackData)
    {
        throw new Exception\LogicException(
            'Unexpected Payment is not supported');
    }

    public function getParsedDataFromUnexptectedCallback($callbackData)
    {
        throw new Exception\LogicException(
            'Extraction of payment and merchant details from callback data is not supported');
    }

    public function getTerminalDetailsFromCallback($callbackData)
    {
        throw new Exception\LogicException('Extraction of merchant details from callback data is not supported');
    }

    protected function updateUrlInCacheAndPushMetric($input, $urlInRequest)
    {
        try
        {
            if (isset($input['payment']['bank']) === false)
            {
                return;
            }

            $bank = $input['payment']['bank'];

            $cacheKey = self::getNetbankingUrlCacheKey($bank);

            $cache = $this->app['redis']->connection('mutex_redis');

            $cacheValue = $cache->get($cacheKey);

            $result = $cacheValue === $urlInRequest;

            if ($result === false)
            {
                $cache->set($cacheKey, $urlInRequest);

                $this->pushNetbankingDynamicUrlMetric($input, $cacheValue, $urlInRequest);
            }
        }
        catch (\Throwable $exc)
        {
            return;
        }
    }

    public function pushNetbankingDynamicUrlMetric($input, $oldUrl, $newUrl)
    {
        $metricObj = new Netbanking\Base\Metric\DynamicUrlChangeMetric;

        $metricObj->pushDimensions($input, $this->gateway, $oldUrl, $newUrl);
    }

    public static function getNetbankingUrlCacheKey($bank)
    {
        $cachePrefix = 'gateway';

        return sprintf($cachePrefix.':'.'%s_netbanking_url', $bank);
    }

    protected function getMozartApiUrl($input)
    {
        $urlConfig = 'applications.mozart.' . $this->mode . '.url';

        $baseUrl = $this->app['config']->get($urlConfig);

        $version = $this->getVersionForAction($input, $this->action);

        return $baseUrl . 'payments/' . $this->gateway . '/' . $version . '/' . snake_case($this->action);
    }

    protected function getGateway($input, $action = null)
    {
        if ($action === null)
        {
            $action = $this->action;
        }

        $nonPaymentActions = [Action::CREATE_TERMINAL, Action::DISABLE_TERMINAL, Action::ENABLE_TERMINAL,
            \RZP\Gateway\Mozart\Action::MERCHANT_ONBOARD];

        if (
            (in_array($action, $nonPaymentActions)) or
            ((isset($input['gateway']) === true) and
             (($input['gateway'] === Payment\Gateway::GOOGLE_PAY) or
              ($input['gateway'] === Payment\Gateway::BILLDESK_SIHUB) or
              ($input['gateway'] === Payment\Gateway::PAYSECURE) or
                 ($input['gateway'] === Payment\Gateway::BT_RBL)))
        )
        {
            return $input['gateway'] ?? 'mozart';
        }

        return $input['payment']['gateway'] ?? 'mozart';
    }

    protected function getVersionForAction($input, $action)
    {
        //upi_sbi v1 is deprecated in mozart
        if ($this->gateway === Payment\Gateway::UPI_SBI) {

            $variant = $this->app->razorx->getTreatment($this->app['request']->getTaskId(), 'upi_sbi_v3_migration', $this->mode);

            if (strtolower($variant) === 'v3')
            {
                return 'v3';
            }

            return 'v2';
        }

        return 'v1';
    }

    protected function sendMozartRequest(array $input, $removeRaw = true)
    {
        $url = $this->getMozartApiUrl($input);

        $passwordConfig = 'applications.mozart.' . $this->mode . '.password';

        $authentication = [
            'api',
            $this->app['config']->get($passwordConfig)
        ];

        $input['terminal'] = $input['terminal']->toArrayWithPassword();

        $requestBody['entities'] = $input;

        $request = [
            'url' => $url,
            'method' => 'POST',
            'headers' => [
                'Content-Type'  => 'application/json',
                'X-Task-ID'     => $this->app['request']->getTaskId(),
            ],
            'content' => json_encode($requestBody),
            'options' => [
                'auth' => $authentication
            ]
        ];

        if (isset($this->app['rzp.mode']) and $this->app['rzp.mode'] === 'test')
        {
            $testCaseId = $this->app['request']->header('X-RZP-TESTCASE-ID');

            if (empty($testCaseId) === false)
            {
                $request['headers']['X-RZP-TESTCASE-ID'] = $testCaseId;
            }
        }

        $response = $this->sendGatewayRequest($request);

        $responseBody = json_decode($response->body, true);

        $this->traceGatewayPaymentResponseForMozart($responseBody ?? '', $requestBody);

        if ($removeRaw === true)
        {
            unset($responseBody['data']['_raw']);
        }

        if (in_array($this->action, ['pay_init', 'authenticate_init', 'authenticate_verify'], true) === true)
        {
            $this->action = 'authorize';
        }

        $attributes = $this->getMappedAttributes($responseBody['data']);

        if (in_array(snake_case($this->action), [Action::VERIFY, Action::VERIFY_REFUND, Action::VALIDATE_VPA]) === true)
        {
            return $responseBody;
        }

        if (empty($attributes) === false)
        {
            if (isset($this->gatewayPayment) === true)
            {
                $this->gatewayPayment = $this->updateGatewayPaymentEntity($this->gatewayPayment, $attributes, false);
            }
            else
            {
                $this->gatewayPayment = $this->createGatewayPaymentEntity($attributes, $input);
            }
        }

        $method = null;

        if (isset($input['payment']['method']) === true)
        {
            $method = $input['payment']['method'];
        }

        $this->checkErrorsAndThrowExceptionFromMozartResponse($responseBody, $method);

        return $responseBody['next']['redirect'] ?? null;
    }

    protected function checkErrorsAndThrowExceptionFromMozartResponse(array $response, $method = null)
    {
        if ($response['success'] !== true)
        {
            $data = [];

            if (is_null($method) === false)
            {
                $data = ['method' => $method];
            }

            if(isset($response['meta_data']) === true) {
                $data = array_merge($response['meta_data'], $data);
            }

            throw new Exception\GatewayErrorException(
                $response['error']['internal_error_code'] ?? 'BAD_REQUEST_PAYMENT_FAILED',
                $response['error']['gateway_error_code'] ?? 'gateway_error_code',
                $response['error']['gateway_error_description'] ?? 'gateway_error_desc',
                $data,
                null,
                $this->action);
        }
    }

    /**
     * @param array $data Data to mask
     * @param array $keys Keys in the data with dot notation
     * @return array
     */
    protected function maskUpiDataForTracing(array $data, array $keys): array
    {
        $output = $data;

        try
        {
            foreach ($keys as $name => $key)
            {
                $value = array_get($data, $key);

                // Default rule for masking
                $masked = '[' . gettype($value) . ']';

                // PHP can convert null, numeric and boolean to string
                if (is_scalar($value) === true)
                {
                    // Forcing to string to make sure making functions do not fail
                    $value = (string) $value;

                    switch ($name)
                    {
                        case Upi\Base\Entity::VPA:
                            $masked = mask_vpa($value);
                            break;
                        case Upi\Base\Entity::CONTACT:
                            $masked = mask_phone($value);
                            break;
                        default:
                            // Asterisk is being used in vpa and phone, thus forcing it by default
                            $masked = mask_except_last4($value, '*');
                    }
                }

                array_set($output, $key, $masked);
            }
        }
        catch (\Throwable $throwable)
        {
            $this->trace->traceException(
                $throwable,
                Trace::CRITICAL,
                TraceCode::ERROR_INVALID_ARGUMENT,
                $keys);

            // As a fallback we can unset the values
            $output = array_except($output, array_values($keys));
        }

        return $output;
    }

    /**
     * Sanitize the text which may have sensitive data
     *
     * @param $text
     * @return mixed
     */
    protected function sanitizeTextForTracing($text)
    {
        // If key is not set or unset, we will not sanitize the response
        if (empty($this->gatewaySanitizeKey) === true)
        {
            return $text;
        }

        // We will only sanitize the texts, anything else will be returned as it is.
        if (is_string($text) === false)
        {
            return $text;
        }

        try
        {
            $rc4 = new RC4();

            $rc4->setKey($this->gatewaySanitizeKey);

            $cipher = $rc4->encrypt($text);

            $encoded = base64_encode($cipher);

            return $encoded;
        }
        catch (\Throwable $throwable)
        {
            $this->trace->traceException(
                $throwable,
                Trace::CRITICAL,
                TraceCode::ERROR_INVALID_ARGUMENT);

            // For any failure we will trace the issue and return the value as it is
            return $text;
        }
    }

    public function isMandateUpdateCallback($input)
    {
        return false;
    }
}
