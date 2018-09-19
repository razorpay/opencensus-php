<?php

namespace RZP\Reconciliator\NetbankingIdfc;

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    public function getDelimiter()
    {
        return '|';
    }

    public function getTypeName($fileName)
    {
        return self::PAYMENT;
    }
}
