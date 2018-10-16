<?php

namespace RZP\Reconciliator\UpiHulk;

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    protected function getTypeName($fileName)
    {
        return self::PAYMENT;
    }
}
