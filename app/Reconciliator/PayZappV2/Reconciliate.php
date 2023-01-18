<?php

namespace RZP\Reconciliator\PayZappV2;

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    protected function getTypeName($fileName): string
    {
        return self::COMBINED;
    }
}
