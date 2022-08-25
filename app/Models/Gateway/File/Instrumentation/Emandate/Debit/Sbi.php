<?php

namespace RZP\Models\Gateway\File\Instrumentation\Emandate\Debit;

use RZP\Base\RuntimeManager;
use RZP\Trace\TraceCode;
use RZP\Models\Gateway\File\Instrumentation\Constants;

class Sbi extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    public function parseTextRow($row)
    {
        return explode(',', $row);
    }

    public function processInput($data, $entries)
    {
        $totalEntries = count($entries);

        $this->trace->info(TraceCode::FILE_GENERATE_PROCESSING, ['total records for sbi' => $totalEntries]);

        foreach ($entries as $entry)
        {
            try
            {
                $paymentId = $entry[8];

                $amount = $entry[7];

                $event = array_merge($data,
                    array(
                        Constants::PAYMENT_ID => $paymentId,
                        Constants::TOTAL_RECORDS => $totalEntries,
                        Constants::AMOUNT => $amount,
                    )
                );

                $this->pushEntryToKafka($event);
            }
            catch (\Exception $ex)
            {
                $this->trace->info(TraceCode::FILE_GENERATE_PROCESSING_ERROR,
                    [
                        'error' => $ex,
                        'entry' => $entry
                    ]);
            }
        }
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('12288M');

        RuntimeManager::setTimeLimit(7200);

        RuntimeManager::setMaxExecTime(7200);
    }
}
