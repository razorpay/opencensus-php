<?php

namespace RZP\Models\Gateway\File\Instrumentation\Emandate\Debit;

use RZP\Gateway\Enach;
use RZP\Trace\TraceCode;
use RZP\Models\Gateway\File\Instrumentation\Constants;
use RZP\Gateway\Enach\Rbl\DebitFileHeadings as Headings;

class EnachRbl extends Base
{

    public function __construct()
    {
        parent::__construct();

    }

    public function processInput($data, $entries)
    {
        $totalEntries = count($entries);

        $this->trace->info(TraceCode::FILE_GENERATE_PROCESSING, [
            'total records for enach rbl kafka processing' => $totalEntries
        ]);

        $processedEntries = 0;

        foreach ($entries as $entry)
        {
            try {
                $paymentId = $entry[Headings::TRANSACTIONREFERENCE];

                $amount = $entry[Headings::AMOUNT];

                $utilityCode = $entry[Headings::UTILITYCODE];

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
                'kafka processed records for enach rbl' => $processedEntries
            ]);
    }
}
