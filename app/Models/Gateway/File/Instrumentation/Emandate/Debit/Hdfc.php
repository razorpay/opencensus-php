<?php

namespace RZP\Models\Gateway\File\Instrumentation\Emandate\Debit;

use RZP\Trace\TraceCode;
use RZP\Base\RuntimeManager;
use RZP\Models\Gateway\File\Instrumentation\Constants;
use RZP\Gateway\Netbanking\Hdfc\EMandateDebitFileHeadings as Headings;

class Hdfc extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    public function processInput($data, $entries)
    {
        $totalEntries = count($entries);

        $this->trace->info(TraceCode::FILE_GENERATE_PROCESSING, [
            'total records for hdfc kafka processing' => $totalEntries
        ]);

        $processedEntries = 0;

        foreach ($entries as $entry)
        {
            try {
                $paymentId = $entry[Headings::TRANSACTION_REF_NO];

                $amount = $entry[Headings::AMOUNT];

                $event = array_merge($data,
                    array(
                        Constants::PAYMENT_ID => $paymentId,
                        Constants::TOTAL_RECORDS => $totalEntries,
                        Constants::AMOUNT => $amount,
                    )
                );

                $this->pushEntryToKafka($event);

                $processedEntries = $processedEntries + 1;
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

        $this->trace->info(TraceCode::FILE_GENERATE_PROCESSED_COUNT,
            [
                'kafka processed records for emandate hdfc' => $processedEntries
            ]);
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('4096M');

        RuntimeManager::setTimeLimit(7200);

        RuntimeManager::setMaxExecTime(7200);
    }
}
