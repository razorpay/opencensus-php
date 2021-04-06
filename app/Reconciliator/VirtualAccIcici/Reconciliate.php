<?php

namespace RZP\Reconciliator\VirtualAccIcici;

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    protected function getTypeName($filename)
    {
        return self::PAYMENT;
    }
}
