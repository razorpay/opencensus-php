<?php

namespace RZP\Reconciliator\EmandateAxis;

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    public function getTypeName($fileName)
    {
        return self::EMANDATE_DEBIT;
    }
}
