<?php

namespace RZP\Gateway\Base;

use Crypt;
use Cache;
use RZP\Models\Card;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Payment\Status;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Gateway\Utility;

use Requests;
use Symfony\Component\DomCrawler\Crawler;
use App;

class Gateway
{
    /**
     * Default request timeout duration in seconds.
     * @var  integer
     */
    const TIMEOUT = 60;

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
     * Number of minutes that the cache key will be stored
     * @var integer
     */
    const CACHE_TTL = 15;

    /**
     * In gateway responses one particular field contains
     * hash or checksum. This variable will contain that field
     * name.
     */
    const CHECKSUM_ATTRIBUTE = '';

    const CACHE_KEY = 'base_%s_card_details';

    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * Trace instance for tracing
     * @var Trace\Trace
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
     * @var RZP\Http\Route
     */
    protected $route;

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

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->env = $this->app['env'];

        if ($this->env === 'testing')
        {
            $this->testing = true;
        }

        $this->loadGatewayConfig();

        $this->repo = $this->getRepository();

        $this->route = $this->app['api.route'];

        $this->request = $this->app['request'];
    }

    public function authorize(array $input)
    {
        $this->input = $input;
        $this->action = Action::AUTHORIZE;
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

        if ($input['payment']['status'] !== Status::AUTHORIZED)
        {
            throw new Exception\RuntimeException(
                'Payment status should be authorized',
                ['payment_id' => $input['payment']['id']]);
        }
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
        assert (is_bool($mock));

        $this->mock = $mock;
    }

    protected function checkApiSuccess(Verify $verify)
    {
        $verify->apiSuccess = true;

        $input = $verify->input;

        if (($input['payment'][Payment\Entity::STATUS] === Payment\Status::FAILED) or
            ($input['payment'][Payment\Entity::STATUS] === Payment\Status::CREATED))
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

    protected function getAcquirerData($input, $gatewayPayment)
    {
        $acquirer = [];

        switch ($input['payment']['method'])
        {
            case Payment\Method::CARD:
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

        if ($input['payment'][Payment\Entity::METHOD] === Payment\Method::NETBANKING)
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
                ]
            );

            throw new Exception\RuntimeException('Failed checksum verification');
        }
    }

    protected function isSecondRecurringPaymentRequest($input)
    {
        if (($this->app['basicauth']->isPrivateAuth() === false) and
            ($this->app['basicauth']->isPrivilegeAuth() === false))
        {
            return false;
        }

        if (($input['payment']['recurring'] === true) and
            (isset($input['token']) === true) and
            ($input['token']->isRecurring() === true) and
            ($input['terminal']->isNon3DSRecurring() === true))
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
        if (isset($request['options']) === false)
        {
            $request['options'] = [];
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
            $request['options']['timeout'] = self::TIMEOUT;
        }

        try
        {
            $method = strtoupper($method);

            $response = Requests::request(
                $request['url'],
                $request['headers'],
                $request['content'],
                $method,
                $request['options']);
        }
        catch (\Requests_Exception $e)
        {
            $this->exception = $e;

            //
            // Some error occurred.
            // Check that whether the gateway response timed out.
            // Mostly it should be gateway timeout only
            //
            if (Utility::checkTimeout($e))
            {
                throw new Exception\GatewayTimeoutException($e->getMessage(), $e);
            }
            else
            {
                throw new Exception\GatewayRequestException($e->getMessage(), $e);
            }
        }

        $this->validateResponse($response);

        // echo 'Response - ' . PHP_EOL . $response->body . PHP_EOL . PHP_EOL;
        // \Log::info('Response - ' . PHP_EOL . $response->body . PHP_EOL . PHP_EOL);

        return $response;
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

    public function preProcessServerCallback($input): array
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
        else
        {
            return $this->getLiveSecret();
        }
    }

    protected function getTestSecret()
    {
        assert ($this->mode === Mode::TEST);

        return $this->config['test_hash_secret'];
    }

    protected function getLiveSecret()
    {
        return $this->input['terminal']['gateway_secure_secret'];
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
            'content' => []
        ];

        return $request;
    }

    protected function getDynamicMerchantName($merchant)
    {
        $label = $merchant->getBillingLabel();

        $label = preg_replace('/[^a-zA-Z0-9 ]+/', '', $label);

        if (empty($label) === true)
        {
            $label = "Razorpay Payments";
        }

        return str_limit($label, 20);
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
                ErrorCode::BAD_REQUEST_PAYMENT_OTP_VALIDATION_ATTEMPT_LIMIT_EXCEEDED);
        }
    }

    public function getGatewayCertDirPath()
    {
        $certificatePath = $this->app['config']->get('gateway.certificate_path');

        $gatewayCertPath = $certificatePath . '/' . $this->getGatewayCertDirName();

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
            if (isset($map[$key]))
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
}
