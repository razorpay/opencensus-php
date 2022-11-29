<?php

namespace RZP\Models\Gateway\File\Instrumentation\Emandate\Debit;

use RZP\Base\RuntimeManager;
use RZP\Gateway\Enach;
use RZP\Trace\TraceCode;
use RZP\Models\Gateway\File\Instrumentation\Constants;

class EnachNpciNetbanking extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    public function processInput($data, $entries)
    {
        $totalEntries = count($entries);

        $this->trace->info(TraceCode::FILE_GENERATE_PROCESSING, [
            'total records for yes bank kafka processing' => $totalEntries
        ]);

        $processedEntries = 0;

        foreach ($entries as $entry)
        {
            try
            {
                $paymentId = $entry[0];

                $amount = $entry[2];

                $utilityCode = $entry[4];

                $event = array_merge($data,
                    array(
                        Constants::PAYMENT_ID => $paymentId,
                        Constants::TOTAL_RECORDS => $totalEntries,
                        Constants::AMOUNT => $amount,
                        Constants::UTILITY_CODE => $utilityCode
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
                'kafka processed records for npci yes bank' => $processedEntries
            ]);
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('8192M'); // 8gb
    }

}
