<?php

namespace RZP\Reconciliator\CardFssSbi;

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    protected function getTypeName($fileName)
    {
        return self::COMBINED;
    }
}
