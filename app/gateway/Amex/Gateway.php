<?php

namespace Gateway\Amex;

use Constants\Mode;
use EE\Error;
use EE\Exception;
use Gateway\Base;
use Gateway\Base\Action;
use Gateway\Base\VerifyResult;
use Gateway\Amex;
use Gateway\AxisMigs;
use Requests;
use Trace\Trace;
use Trace\TraceCode;

class Gateway extends AxisMigs\Gateway
{
    protected $gateway = 'amex';

    protected $authorize = true;

    protected function addTestCardDetailsInTestMode(array & $content)
    {
        assert ($this->mode === Mode::TEST);

        if ($content['vpc_CardNum'] === '4111111111111111')
        {
            return;
        }

        // 341111111111111, 345678000000007 works

        $content['vpc_Card'] = 'Amex';
        $content['vpc_CardNum'] = '341111111111111';
        $content['vpc_CardExp'] = '1705';
        $content['vpc_CardSecurityCode'] = '0773';
    }
}