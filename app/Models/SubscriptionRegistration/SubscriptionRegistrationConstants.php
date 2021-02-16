<?php

namespace RZP\Models\SubscriptionRegistration;

use App;
use RZP\Trace\TraceCode;

class SubscriptionRegistrationConstants
{
    const SUCCESS          = 'success';
    const ERRORS           = 'errors';
    const PAYMENT_RESPONSE = 'payment_response';
    const URL              = 'url';

    //
    // auth link status
    // indicates payment is pending from bank
    //
    const PENDING = 'pending';

    /**
     * Banks on which we can charge while doing Mandate Registration
     * Exact bank names here as invoice/subscription entity does not return bank code
     */
    const banksForDebitOnMandateRegister    = [ 'ICICI Bank', 'HDFC Bank' ];
    const authTypeForDebitOnMandateRegister = [ 'netbanking' ];

    /**
     * Fetch Merged Bank details from csv file
     */
    const BANK_MERGER_IFSC_FILE_PATH = 'files/app/bank_merger_ifsc_%s.csv';

    public static function getMergedBanksPaperNach()
    {
        $mappedIfsc = array();

        $filePath = storage_path(sprintf(self::BANK_MERGER_IFSC_FILE_PATH, 'nach'));

        if (file_exists($filePath) === false)
        {
            return $mappedIfsc;
        }

        $handle = fopen($filePath,"r");

        if ($handle === false)
        {
            return $mappedIfsc;
        }

        try
        {
            $header = fgetcsv($handle);

            while ($row = fgetcsv($handle))
            {
                $key = array_shift($row);

                $mappedIfsc[$key] = $row[0];

            }
        }
        catch (\Exception $exception)
        {
            $app = App::getFacadeRoot();

            $app['trace']->traceException(
                $exception,
                null,
                TraceCode::ERROR_RESPONSE_FILE_READING_FAILED,
                ['payment_method' => 'nach']);
        }
        finally
        {
            fclose($handle);
        }

        return $mappedIfsc;
    }
}
