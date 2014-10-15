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

    protected function mapKeys($array, $map, &$data)
    {
        foreach ($map as $keyOld => $keyNew)
        {
            if (isset($array[$keyOld]))
            {
                $data[$keyNew] = $array[$keyOld];
            }
        }
    }

    protected function sendGatewayRequest($request)
    {
        if (isset($request['options']) === false)
        {
            $request['options']  = array();
        }

        return Requests::post(
                    $request['url'],
                    $request['header'],
                    $request['content'],
                    $request['options']);
    }
}