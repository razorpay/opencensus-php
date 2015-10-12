<?php

namespace Gateway\Base;

use Constants\Mode;
use EE\Exception;
use Requests;
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

    protected $sortRequestContent = true;

    public function __construct()
    {
        $this->trace = \Trace::getFacadeRoot();

        $this->env = \App::getFacadeRoot()['env'];

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

    public function setTerminal($terminal)
    {
        $this->terminal = $terminal;
    }

    public function setMode($mode)
    {
        $this->mode = $mode;
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

        $response = Requests::$method(
                    $request['url'],
                    $request['headers'],
                    $request['content'],
                    $request['options']);

        // echo 'Response - ' . PHP_EOL . $response->body . PHP_EOL . PHP_EOL;
        // \Log::info('Response - ' . PHP_EOL . $response->body . PHP_EOL . PHP_EOL);

        return $response;
    }

    protected function runPaymentVerifyFlow($verify)
    {
        $payment = $this->getPaymentToVerify($verify->input, $verify);

        if (($payment === null) and
            ($verify->input['payment']['status'] === 'failed'))
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

    public function authorizeFailed(array $input)
    {
        $e = null;

        try
        {
            $this->verify($input);
        }
        catch (Exception\PaymentVerificationException $e)
        {
            $this->trace->info(
                TraceCode::PAYMENT_FAILED_TO_AUTHORIZED,
                ['message' => 'Payment verification failed. Now converting to authorized']);
        }

        $verify = $e->getVerifyObject();

        if ($e === null)
        {
            throw new Exception\LogicException(
                'When converting failed payment to authorized, payment verification ' .
                'should have failed but instead it did not',
                $verify->getDataToTrace());
        }

        if (($verify->apiSuccess === false) and
            ($verify->gatewaySuccess === true))
        {
            $payment = $verify->payment;
            $payment->fill($verify->verifyResponseContent);
            $payment->saveOrFail();
        }
        else
        {
            throw new Exception\LogicException(
                'Should not have reached here');
        }

        return true;
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

    protected function getSecret()
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

        $live = constant($urlClass . '::LIVE_DOMAIN');

        $test = constant($urlClass . '::TEST_DOMAIN');

        return ($this->mode === Mode::LIVE) ? $live : $test;
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
}