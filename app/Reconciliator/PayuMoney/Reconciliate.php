<?php

namespace RZP\Reconciliator\PayuMoney;

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    protected function getTypeName($fileName)
    {
        // We only support payment recon for now
        return self::PAYMENT;
    }
}
