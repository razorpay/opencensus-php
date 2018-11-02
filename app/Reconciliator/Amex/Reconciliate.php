<?php

namespace RZP\Reconciliator\Amex;

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    // in amex recon file there are some rows that are present before actual payments
    // we need to jump to that line to start processing
    const START_ROW = '21';

    protected function getTypeName($fileName)
    {
        return self::COMBINED;
    }

    public function getStartRow($fileDetails)
    {
        return self::START_ROW;
    }
}
