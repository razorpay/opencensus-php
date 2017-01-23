<?php

namespace RZP\Reconciliator\Freecharge;

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    // Number of lines to skip from EOF while reading MIS file
    const NUM_LINES_TO_SKIP_FROM_BOTTOM = 3;

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

    public function getNumLinesToSkip()
    {
        return [
            self::LINES_FROM_TOP => 0,
            self::LINES_FROM_BOTTOM => 3
        ];
    }
}
