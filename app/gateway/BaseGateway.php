<?php

namespace Gateway;

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
        // if (($mode === 'live') and
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

        return Requests::post(
                    $request['url'],
                    $request['header'],
                    $request['content'],
                    $request['options']);
    }
}