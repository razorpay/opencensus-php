<?php

namespace RZP\Reconciliator\VirtualAccYesBank;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Reconciliator\Base;
use RZP\Reconciliator\RequestProcessor\Base as RequestProcessor;

class Reconciliate extends Base\Reconciliate
{
    const HEADERS = [
        'cust_code',
        'remitter_code',
        'customer_subcode',
        'invoice_no',
        'bene_account_no',
        'amount',
        'rmtr_account_no',
        'rmtr_account_ifsc',
        'transaction_ref_no',
        'trans_received_at',
        'trans_status',
        'validation_status',
        'transfer_type',
        'credit_ref',
        'notify_status',
        'notify_result',
        'return_ref',
        'returned_at',
        'rmtr_full_name',
        'rmtr_add',
        'udf11',
        'udf12',
        'udf13',
        'udf14',
    ];

    /**
     *
     * @param string $fileName
     * @return string
     */
    protected function getTypeName($filename)
    {
        return self::PAYMENT;
    }

    public function getColumnHeadersForType($type)
    {
        return self::HEADERS;
    }

    /**
     * YesBank sends files in each hour and multiple files during midnight.
     * But during first 15 minutes near midnight, they send cumulative file for whole day.
     * We want to reconcile daily cumulative file, hence adding check of midnight hours.
     * Daily cumulative file is sent at around 12:11 AM daily.
     * And also, if it is manual upload, irrespective of time we will read that file.
     *
     * @param array $fileDetails
     * @param array $inputDetails
     * @return bool
     */
    public function inExcludeList(array $fileDetails, array $inputDetails = [])
    {
        //
        // If source is manual, we will always read the file.
        //
        if ($inputDetails[RequestProcessor::SOURCE] === RequestProcessor::MANUAL)
        {
            return false;
        }

        $midnight = Carbon::today(Timezone::IST);

        $thirtyMinutesSinceMidnight = Carbon::today(Timezone::IST)->minute(30);

        //
        // Will only include automated files from midnight to 12:30.
        //
        if (Carbon::now(Timezone::IST)->between($thirtyMinutesSinceMidnight, $midnight) === false)
        {
            return true;
        }

        return false;
    }
}
