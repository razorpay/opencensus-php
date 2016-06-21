<?php

namespace Reconciliator\Axis;

use Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    const SALE = 'sale';
    const ACCEPTED_SHEET_NAMES = ['Refund', 'Sales', 'Sale'];

    /**
     * Figures out what kind of reconciliation is it
     * depending on the file name. It should be either
     * 'refund', 'payment' or 'combined'.
     * In case of excel sheets, the file name is the sheet name
     * and not the excel file name.
     *
     * @param string $fileName
     * @return null|string
     */
    protected function getTypeName($fileName)
    {
        if (strpos(self::REFUND, $fileName) !== false)
        {
            $typeName = self::REFUND;
        }
        else if (strpos(self::SALE, $fileName) !== false)
        {
            $typeName = self::PAYMENT;
        }
        else
        {
            return null;
        }

        return $typeName;
    }

    /**
     * The list of sheet names in the excel file which should be
     * used to run reconciliation.
     * Some excel files have sheets that should not be considered for
     * reconciliation.
     *
     * @return array
     */
    public function getSheetNames()
    {
        return self::ACCEPTED_SHEET_NAMES;
    }
}