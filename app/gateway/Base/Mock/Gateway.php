<?php

namespace Gateway\Mock;

use Gateway\Base;

class Gateway extends Base\Gateway
{
    public function authorize(array $input)
    {
        ;
    }

    public function refund(array $input)
    {
        ;
    }

    public function capture(array $input)
    {
        ;
    }

    public function auth(array $input)
    {
        ;
    }

    public function setTerminal($terminal)
    {
        ;
    }

    public function setMode($mode)
    {
        ;
    }
}