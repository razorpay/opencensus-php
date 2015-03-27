<?php

namespace Gateway;

use Constants\Mode;
use EE\Exception;
use Requests;
use Trace;

class BaseGateway
{
    protected $trace;

    protected $input;

    /**
     * Denotes if the gateway is a mock
     * @var boolean
     */
    protected $mock;

    public function __construct()
    {
        $this->trace = Trace::getFacadeRoot();
    }

    public function authorize(array $input)
    {
        $this->input = $input;
    }

    public function capture(array $input)
    {
        $this->input = $input;
    }

    public function refund(array $input)
    {
        $this->input = $input;
    }

    public function setTerminal($terminal)
    {
        $this->terminal = $terminal;
    }

    public function setMode($mode)
    {
        // if (($mode === Mode::LIVE) and
        //     ($this->mock === true))
        // {
        //     throw new Exception\LogicException('Cannot mock a gateway in live mode');
        // }

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
}