<?php

namespace RZP\Reconciliator\Freecharge;

use RZP\Models\Payment\Gateway;
use RZP\Reconciliator\Base;
use RZP\Reconciliator\Orchestrator;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    const BANK = Orchestrator::FREECHARGE;

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
            FileProcessor::LINES_FROM_TOP    => 0,
            FileProcessor::LINES_FROM_BOTTOM => 3
        ];
    }

    public function fetchSettlementFileLink(string $text)
    {
        /**
         * 1. Fetch all hyperlinks 'a' tags
         * 2. Get the one with text as 'VIEW REPORT'
         * 3. Extract the href link
         */
    }
}
