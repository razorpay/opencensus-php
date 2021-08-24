<?php

namespace RZP\Reconciliator\checkout_dot_com;

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    /**
     * Figures out what kind of reconciliation is it
     * depending on the file name. It should be either
     * 'refund', 'payment' or 'combined'.
     * 'combined' is used when a file has both payments and refunds reports.
     * In case of excel sheets, the file name is the sheet name
     * and not the excel file name.
     *
     * @param string $fileName
     * @return null|string
     */
    protected function getTypeName($fileName)
    {
        return self::COMBINED;
    }
}
