<?php

namespace RZP\Gateway\Amex;

use RZP\Constants\Mode;
use RZP\Error;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Amex;
use RZP\Gateway\AxisMigs;
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

        $content['vpc_Card'] = 'Amex';

        // 341111111111111, 345678000000007 are valid Amex card numbers

        // The following credentials are for Amex's real test gateway
        // Note: only these creds work on the Amex test gateway
        $content['vpc_CardNum'] = '341111111111111';
        $content['vpc_CardExp'] = '1705';
        $content['vpc_CardSecurityCode'] = '0773';
    }

    protected function getVpcCardValue($network)
    {
        return 'Amex';
    }
}