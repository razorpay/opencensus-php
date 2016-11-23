<?php

namespace RZP\Gateway\Base;

use RZP\Constants\Mode;
use RZP\Exception;
use RZP\Error\ErrorCode;
use Requests;
use RZP\Models\Payment\Status;
use Symfony\Component\DomCrawler\Crawler;
use RZP\Trace\TraceCode;
use App;

class Gateway
{
    /**
     * Default request timeout duration in seconds.
     * @var  integer
     */
    const TIMEOUT = 30;

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
     * In gateway responses one particular field contains
     * hash or checksum. This variable will contain that field
     * name.
     */
    const CHECKSUM_ATTRIBUTE = '';

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
     * @return array|null
     */
    public function callback(array $input)
    {
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

    public function reverseAuth(array $input)
    {
        $this->input = $input;
        $this->action = Action::REVERSE_AUTH;
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

    public function canTopup()
    {
        return $this->topup;
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
            $request['options']  = array();
        }

        if (isset($request['headers']) === false)
        {
            $request['headers'] = array();
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
            if (\RZP\Gateway\Utility::checkTimeout($e))
            {
                throw new Exception\GatewayTimeoutException($e->getMessage(), $e);
            }
            else
            {
                throw new Exception\GatewayRequestException($e->getMessage(), $e);
            }
        }

        // echo 'Response - ' . PHP_EOL . $response->body . PHP_EOL . PHP_EOL;
        // \Log::info('Response - ' . PHP_EOL . $response->body . PHP_EOL . PHP_EOL);

        return $response;
    }

    protected function runPaymentVerifyFlow($verify)
    {
        // This payment is the gateway entity payment.
        // Also sets this gateway payment in the verify object's payment.
        $gatewayPayment = $this->getPaymentToVerify($verify->input, $verify);

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

        return $verify->getDataToTrace();
    }

    public function preProcessS2SResponse($input)
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

    protected function traceGatewayPaymentRequest($request, $input)
    {
        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'request'    => $request,
                'gateway'    => $this->gateway,
                'payment_id' => $input['payment']['id'],
            ]);
    }

    protected function traceGatewayPaymentResponse($response, $input)
    {
        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_RESPONSE,
            [
                'response'   => $response,
                'gateway'    => $this->gateway,
                'payment_id' => $input['payment']['id'],
            ]);
    }

    protected function getPaymentToVerify($input, $verify)
    {
        $payment = $this->repo->findByPaymentIdAndAction(
                    $input['payment']['id'], Action::AUTHORIZE);

        $verify->payment = $payment;

        return $payment;
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

        $domainConstantName = strtoupper($this->mode).'_DOMAIN';

        if ($this->domainType !== null)
        {
            $domainType = strtoupper($this->domainType);

            $domainConstantName = $domainType.'_DOMAIN';
        }

        return constant($urlClass . '::' .$domainConstantName);
    }

    protected function getRelativeUrl($type)
    {
        $ns = $this->getGatewayNamespace();

        return constant($ns.'\Url::'.$type);
    }

    protected function getUrl($type = null)
    {
        $url = $this->getUrlDomain();

        if ($type === null)
        {
            $type = $this->action;
        }

        $type = strtoupper($type);

        $url .= $this->getRelativeUrl($type);

        return $url;
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

    protected function getStandardRequestArray($content = [], $method = 'post')
    {
        $request = array(
            'url'       => $this->getUrl(),
            'method'    => $method,
            'content'   => $content,
        );

        return $request;
    }

    protected function getOtpSubmitRequest(array $input): array
    {
        $request = [
            'url' => $input['otpSubmitUrl'],
            'method' => 'post'
        ];

        return $request;
    }

    protected function jsonToArray($json)
    {
        $decodeJson = json_decode($json, true);

        switch(json_last_error())
        {
            case JSON_ERROR_NONE:
                return $decodeJson;
            case JSON_ERROR_DEPTH:
            case JSON_ERROR_STATE_MISMATCH:
            case JSON_ERROR_CTRL_CHAR:
            case JSON_ERROR_SYNTAX:
            case JSON_ERROR_UTF8:
                $this->trace->error(
                    TraceCode::GATEWAY_PAYMENT_ERROR,
                    ['json' => $json]);

                throw new Exception\RuntimeException(
                    'Failed to convert json to array',
                    ['json' => $json]);
        }
    }

    protected function verifyOtpAttempts($payment, $limit = null)
    {
        if ($limit === null)
        {
            $limit = self::OTP_ATTEMPTS_LIMIT;
        }

        if ($payment['otp_attempts'] >= $limit)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_OTP_VALIDATION_ATTEMPT_LIMIT_EXCEEDED);
        }
    }

    protected function getGatewayCertDirPath()
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

            return (array) $res;
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
}
