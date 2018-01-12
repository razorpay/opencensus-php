<?php

namespace RZP\Models\FundTransfer\Icici\Reconciliation;

use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Settlement\Channel;
use RZP\Models\FundTransfer\Icici\Headings;
use RZP\Models\FundTransfer\Base\Reconciliation\FileProcessor as BaseProcessor;

class FileProcessor extends BaseProcessor
{
    protected static $fileToReadName = 'Icici_Settlement';

    protected static $channel = Channel::ICICI;

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
        $date = Carbon::createFromFormat('m/d/Y', $data[0][Headings::PAYMENT_DATE]);

        //update the format so that recon mail is appended to settlement mail
        $this->date = $date->format('d-m-Y');
    }

    protected function parseTextRowWithHeadingMismatch($headings, $values, $ix): array
    {
        $count = count($values);

        $this->trace->info(TraceCode::MISC_TRACE_CODE, ['count' => $count]);

        if (($count < 11) or ($count > 12))
        {
            throw new Exception\LogicException(
                'Invalid count: ' . $count . ' Should be either 11 or 12.');
        }

        $headings = array_slice($headings, 0, $count);

        $values = array_combine($headings, $values);

        return $values;
    }

    protected function storeFile($reconFile)
    {
        $this->storeReconciledFile($reconFile);
    }
}