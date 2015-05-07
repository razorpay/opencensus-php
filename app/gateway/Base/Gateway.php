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
     * Denotes if the gateway is a mock
     * @var boolean
     */
    protected $mock;

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
        return substr(get_called_class(), 0, strrpos(get_called_class(), "\\"));
    }

    public function generateHash($content)
    {
        $hashString = $this->config['test_hash_secret'];

        ksort($content);

        foreach($content as $key => $value)
        {
            //
            // create the md5 input and URL leaving
            // out any fields that have no value
            //
            if (strlen($value) > 0)
            {
                $hashString .= $value;
            }
        }

        return $this->getHashOfString($hashString);
    }

    protected function getHashOfString($str)
    {
        return $str;
    }

    protected function loadGatewayConfig()
    {
        $configGatewayStr = 'gateway.'.$this->gateway;

        $app = \App::getFacadeRoot();
        $this->config = $app['config']->get($configGatewayStr);
    }
}