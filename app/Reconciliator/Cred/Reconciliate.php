<?php

namespace RZP\Reconciliator\Cred;

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    protected function getTypeName($fileName)
    {
        return self::COMBINED;
    }
}
