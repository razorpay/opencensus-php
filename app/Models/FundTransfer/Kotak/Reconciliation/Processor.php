<?php

namespace RZP\Models\FundTransfer\Kotak\Reconciliation;

use Mail;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\FileStore;
use RZP\Models\Settlement\Channel;
use RZP\Trace\TraceCode;
use RZP\Models\FundTransfer\Kotak;
use RZP\Models\FundTransfer\Attempt as FundTransferAttempt;
use RZP\Models\FundTransfer\Base\Reconciliation\FileProcessor as BaseProcessor;

class Processor extends BaseProcessor
{
    protected static $fileToReadName = 'Kotak_Settlement_Reconciliation';

    protected static $fileToWriteName = 'Kotak_Settlement_Reconciliation';

    protected static $channel = Channel::KOTAK;

    protected static $delimiter = '~';

    protected static $fileExtensions = [
        FileStore\Format::TXT
    ];

    protected function setDate($data)
    {
        $date = Carbon::createFromFormat('d-M-y', $data[0][Kotak\Headings::PAYMENT_DATE]);

        //update the format so that recon mail is appended to settlement mail
        $this->date = $date->format('d-m-Y');
    }

    protected function getRowProcessorNamespace($row)
    {
        $version = $this->getSettlementVersion($row);

        $rowProcessorNamespace = 'RZP\\Models\\FundTransfer\\Kotak\\Reconciliation\\' .
            ucwords($version) .
            '\\RowProcessor';

        return $rowProcessorNamespace;
    }

    protected function getSettlementVersion(array $row): string
    {
        $version = FundTransferAttempt\Version::V1;

        if (Kotak\Reconciliation\V2\RowProcessor::isV2($row) === true)
        {
            $version = FundTransferAttempt\Version::V2;
        }
        else if (Kotak\Reconciliation\V3\RowProcessor::isV3($row) === true)
        {
            $version = FundTransferAttempt\Version::V3;
        }

        return $version;
    }

    public static function getHeadings()
    {
        return Kotak\Headings::getResponseFileHeadings();
    }

    protected function parseTextRowWithHeadingMismatch($headings, $values, $ix): array
    {
        $count = count($values);

        $this->trace->info(TraceCode::MISC_TRACE_CODE, ['count' => $count]);

        if (($count < 54) or ($count > 55))
        {
            throw new Exception\LogicException(
                'Invalid count: ' . $count . ' Should be either 54 or 55. Row',
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
