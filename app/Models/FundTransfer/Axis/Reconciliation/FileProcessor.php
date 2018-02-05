<?php

namespace RZP\Models\FundTransfer\Axis\Reconciliation;

use Carbon\Carbon;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Models\Settlement\Channel;
use RZP\Models\FundTransfer\Axis\Headings;
use RZP\Models\FundTransfer\Base\Reconciliation\FileProcessor as BaseProcessor;

class FileProcessor extends BaseProcessor
{
    protected static $fileToReadName = 'Axis_Settlement_Reconciliation';

    protected static $fileToWriteName = 'Axis_Settlement_Reconciliation';

    protected static $channel = Channel::AXIS;

    protected static $delimiter = ',';

    public static function getHeadings()
    {
        return Headings::getResponseFileHeadings();
    }

    protected function getRowProcessorNamespace($row)
    {
        return __NAMESPACE__ . '\\RowProcessor';
    }

    protected function setDate($data)
    {
        // Date in the excel file are formatted to get the proper date from the integer value use the below method
        $date = \PHPExcel_Style_NumberFormat::toFormattedString($data[0][Headings::SETTLEMENT_DATE], 'DD-MM-YYYY');

        //update the format so that recon mail is appended to settlement mail
        $this->date = $date;
    }

    protected function storeFile($reconFile)
    {
        $this->storeReconciledFile($reconFile);
    }
}
