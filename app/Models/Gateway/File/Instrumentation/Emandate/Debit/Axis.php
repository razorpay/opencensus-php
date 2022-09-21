<?php

namespace RZP\Models\Gateway\File\Instrumentation\Emandate\Debit;

use RZP\Trace\TraceCode;
use RZP\Models\Gateway\File\Instrumentation\Constants;

class Axis extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    public function processInput($data, $entries)
    {
        $totalEntries = count($entries);

        $this->trace->info(TraceCode::FILE_GENERATE_PROCESSING, [
            'total records for axis kafka processing' => $totalEntries
        ]);

        $processedEntries = 0;

        foreach ($entries as $entry)
        {
            try {
                $paymentId = $entry[0];

                $amount = $entry[6];

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
                'kafka processed records for emandate axis' => $processedEntries
            ]);
    }
}
