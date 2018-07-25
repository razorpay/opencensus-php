<?php

namespace RZP\Models\FundTransfer\Icici\Reconciliation;

use Carbon\Carbon;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Models\Settlement\Channel;
use RZP\Models\FundTransfer\Icici\Headings;
use RZP\Models\FundTransfer\Base\Reconciliation\FileProcessor as BaseProcessor;

class Processor extends BaseProcessor
{
    protected static $fileToReadName  = 'Icici_Settlement_Reconciliation';

    protected static $fileToWriteName = 'Icici_Settlement_Reconciliation';

    protected static $channel = Channel::ICICI;

    protected static $delimiter = ',';

    protected static $fileExtensions = [
        FileStore\Format::TXT
    ];

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
                'Invalid count: ' . $count . ' Should be between 11 and 13. Row',
                null,
                [
                    'line'      => $ix,
                    'content'   => $values
                ]);
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
