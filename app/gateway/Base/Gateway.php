<?php

namespace Gateway\Base;

use Constants\Mode;
use EE\Exception;
use Requests;
use Trace;

class Gateway
{
    protected $trace;

    protected $input;

    protected $action;

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
        $this->trace = Trace::getFacadeRoot();

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

        if (isset($request['header']) === false)
        {
            $request['header'] = array();
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
                    $request['header'],
                    $request['content'],
                    $request['options']);

        // echo 'Response - ' . PHP_EOL . $response->body . PHP_EOL . PHP_EOL;
        // \Log::info('Response - ' . PHP_EOL . $response->body . PHP_EOL . PHP_EOL);

        return $response;
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

    protected function getUrl($type)
    {
        $url = $this->getUrlDomain();

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
}