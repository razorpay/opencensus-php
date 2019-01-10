<?php

namespace RZP\Reconciliator\Amex;

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{

    const KEY_COLUMN_NAMES = [
        'type' => self::PAYMENT,
    ];

    protected function getTypeName($fileName)
    {
        return self::COMBINED;
    }

    /*
     * in amex recon file there are some rows that are present before actual payments
     * we need to jump to that line to start processing, this line number keeps changing
     * so getting the column header from where to start
     */
    public function getKeyColumnNames(array $fileDetails = [])
    {
        return self::KEY_COLUMN_NAMES;
    }
}
