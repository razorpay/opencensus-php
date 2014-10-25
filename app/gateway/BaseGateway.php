<?php

namespace Gateway;

use Requests;
use Trace\Trace;

class BaseGateway
{
    protected $trace;

    protected $input;

    public function __construct()
    {
        $this->trace = Trace::getInstance();
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