<?php

namespace RZP\Models\FundTransfer\Kotak\Reconciliation\Base;

use Mail;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Settlement\Channel;
use RZP\Trace\TraceCode;
use RZP\Models\FundTransfer\Kotak;
use RZP\Models\FundTransfer\Attempt as FundTransferAttempt;
use RZP\Models\FundTransfer\Base\Reconciliation\Processor as BaseProcessor;

class Processor extends BaseProcessor
{
    use Kotak\FileHandlerTrait;

    protected static $fileToReadName = 'Kotak_Settlement_Reconciliation';

    protected static $fileToWriteName = 'Kotak_Settlement_Reconciliation';

    protected static $channel = Channel::KOTAK;

    protected $date;

    protected function parseFile($file)
    {
        $this->parseTextFile($file);
    }

    /**
     * Reads row from reconciliation file, and returns array of parsed data from that
     *
     * @param           Entity
     * @param   Array   Row to be parsed
     *
     * @return  Array   Parsed data
     */

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
                'Invalid count: ' . $count . ' Should be either 54 or 55.');
        }

        $headings = array_slice($headings, 0, $count);

        $values = array_combine($headings, $values);

        return $values;
    }
}
