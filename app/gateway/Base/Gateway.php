<?php

namespace Gateway\Base;

use Constants\Mode;
use EE\Exception;
use Requests;
use Symfony\Component\DomCrawler\Crawler;
use Trace\Trace;
use Trace\TraceCode;

class Gateway
{
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
     * Whether the gateway supports authorizing payments.
     * @var boolean
     */
    protected $authorize = false;

    /**
     * Whether the gateway supports otp flow.
     * @var boolean
     */
    protected $otpFlow = false;

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
        $this->trace = \Trace::getFacadeRoot();

        $this->env = \App::getFacadeRoot()['env'];

        if ($this->env === 'testing')
        {
            $this->testing = true;
        }

        $this->loadGatewayConfig();
    }

    public function authorize(array $input)
    {
        $this->input = $input;
        $this->action = Action::AUTHORIZE;
    }

    public function callback(array $input)
    {//s($input['gateway']);
        $this->input = $input;
        $this->action = Action::CALLBACK;
    }

    public function capture(array $input)
    {
        $this->input = $input;
        $this->action = Action::CAPTURE;
    }

    public function refund(array $input)
    {
        $this->input = $input;
        $this->action = Action::REFUND;
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

    public function canRunOtpFlow()
    {
        return $this->otpFlow;
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

        // echo 'Url: ' . $request['url'] . PHP_EOL;
        // echo $request['content'] . PHP_EOL . PHP_EOL;
        // \Log::info( 'Url: ' . $request['url'] . PHP_EOL);
        // \Log::info( json_encode($request['content'], JSON_PRETTY_PRINT) . PHP_EOL . PHP_EOL);
        if (isset($request['options']['timeout']) === false)
        {
            $request['options']['timeout'] = 30;
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
        catch(\Requests_Exception $e)
        {
            $this->exception = $e;

            //
            // Some error occurred.
            // Check that whether the gateway response timed out.
            // Mostly it should be gateway timeout only
            //
            if (\Gateway\Utility::checkTimeout($e))
            {
                throw new Exception\GatewayTimeoutException($e->getMessage(), $e);
            }
            else
            {
                throw $e;
            }
        }

        // echo 'Response - ' . PHP_EOL . $response->body . PHP_EOL . PHP_EOL;
        // \Log::info('Response - ' . PHP_EOL . $response->body . PHP_EOL . PHP_EOL);

        return $response;
    }

    protected function runPaymentVerifyFlow($verify)
    {
        $payment = $this->getPaymentToVerify($verify->input, $verify);

        if (($payment === null) and
            (($verify->input['payment']['status'] === 'failed') or
             ($verify->input['payment']['status'] === 'created')))
        {
            $this->trace->warning(
                TraceCode::GATEWAY_PAYMENT_VERIFY,
                ['payment_id' => $verify->input['payment']['id'],
                 'message' => 'payment id not found in the gateway database',
                 'gateway' => $this->gateway]);

            return;
        }

        $content = $this->sendPaymentVerifyRequest($verify);

        $status = $this->verifyPayment($verify);

        if (($verify->match === false) and
            ($verify->throwExceptionOnMismatch))
        {
            throw new Exception\PaymentVerificationException(
                $verify->getDataToTrace(),
                $verify);
        }

        return $verify->getDataToTrace();
    }

    protected function traceGatewayPaymentRequest($request, $input)
    {
        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'request' => $request,
                'gateway' => $this->gateway,
                'payment_id' => $input['payment']['id'],
            ]);
    }

    protected function getPaymentToVerify($input, $verify)
    {
        $payment = $this->getRepo()->findByPaymentIdAndAction(
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

    protected function getRepo()
    {
        $class = $this->getGatewayNamespace() . '\Repository';

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
        $configGatewayStr = 'gateway.'.$this->gateway;

        $app = \App::getFacadeRoot();
        $this->config = $app['config']->get($configGatewayStr);

        $this->proxy = $app['config']->get('gateway.proxy_address');
    }

    protected function getFormValues($form, $url)
    {
        $crawler = new Crawler($form, $url);

        $form = $crawler->filter('form')->form();

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
            'url' => $this->getUrl(),
            'method' => $method,
            'content' => $content,
        );

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
                    ['json' => $json],
                    $e);
        }
    }
}
