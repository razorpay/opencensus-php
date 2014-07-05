<?php

namespace Gateway;

use Trace\Trace;

class BaseGateway
{
    protected $trace;

    protected $input;

    public function __construct()
    {
        $this->trace = Trace::getInstance();
    }

    public function auth(array $input)
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
}