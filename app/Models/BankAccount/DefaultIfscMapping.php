<?php

namespace RZP\Models\BankAccount;

use App;

use Razorpay\Trace\Logger as Trace;

use RZP\Trace\TraceCode;
use RZP\Models\Bank\IFSC as BankIFSC;

class DefaultIfscMapping
{
    const DEFAULT_IFSC_NOT_FOUND = 'default_ifsc_not_found';
    const IFSC                   = 'ifsc';
    /*
     * Grameen banks have alphanumeric last 6 characters of IFSC code, this doesn't mean all non grameen banks will
     * always have numeric last 6 characters. But, we are assuming this here to ensure we don't return incorrect ifsc in
     * some grameen bank scenario which causes money loss, therefore we will consider all alpha numeric last 6 char ifsc
     * codes to be for grameen banks.
     */
    const NON_GRAMEEN_BANK_REGEX = '/^\d+$/';

    const OLD_IFSC                 = 'old_ifsc';
    const BANK_CODE                = 'bank_code';
    const BANK_DETAILS             = 'bank_details';
    const IFSC_LAST_SIX_CHARACTERS = 'ifsc_last_six_characters';

    public static function getDefaultIfsc(string $oldIfsc = '', string $bankCode = ''): string
    {
        $app = App::getFacadeRoot();

        /**
         * @var Trace $trace
         */
        $trace = $app['trace'];

        $trace->info(
            TraceCode::DEFAULT_IFSC_FETCH_REQUEST,
            [
                self::OLD_IFSC  => $oldIfsc,
                self::BANK_CODE => $bankCode,
            ]
        );

        $ifscLast6Chars = strtoupper(substr($oldIfsc, 5, 6));

        $ifscFirst4Chars = strtoupper(substr($oldIfsc, 0, 4));

        if (empty ($bankCode) === true)
        {
            /*
             * We are checking if grameen bank ifsc is sent without bank code, we don't fetch default ifsc as we can't
             * know the correct ifsc for grameen banks without bank code (because they rely on other major bak rails
             * like HDFC, Yesbank etc so their IFSCs start with first 4 chars of major banks). This is to avoid money
             * loss scenarios of transferring to money same account number on different ifsc code.
             */
            if (boolval(preg_match(self::NON_GRAMEEN_BANK_REGEX, $ifscLast6Chars)) === false)
            {
                $trace->info(
                    TraceCode::DEFAULT_IFSC_FETCH_FOR_GRAMEEN_BANK_WITHOUT_BANK_CODE,
                    [
                        self::OLD_IFSC                 => $oldIfsc,
                        self::BANK_CODE                => $bankCode,
                        self::IFSC_LAST_SIX_CHARACTERS => $ifscLast6Chars,
                    ]
                );

                return self::DEFAULT_IFSC_NOT_FOUND;
            }

            $bankCode = $ifscFirst4Chars;
        }
        else
        {
            /*
             * If a non grameen bank account ifsc code is passed as input with a non standard bank code (meaning for a
             * numeric last 6 character ifsc code if we get a bank code which is not same as first 4 characters of ifsc,
             * it is more than likely that this is a merchant mistake. We can't prioritise bank code or ifsc code here
             * as both can give different ifsc codes and only one of them would be the intended one. Hence we send the
             * failure response instead.
             */
            if ((boolval(preg_match(self::NON_GRAMEEN_BANK_REGEX, $ifscLast6Chars)) === true) and
                ($ifscFirst4Chars !== $bankCode))
            {
                $trace->info(
                    TraceCode::DEFAULT_IFSC_FETCH_FOR_NON_GRAMEEN_BANK_WITH_DIFFERENT_BANK_CODE,
                    [
                        self::OLD_IFSC                 => $oldIfsc,
                        self::BANK_CODE                => $bankCode,
                        self::IFSC_LAST_SIX_CHARACTERS => $ifscLast6Chars,
                    ]
                );

                return self::DEFAULT_IFSC_NOT_FOUND;
            }
        }

        $bankDetails = BankIFSC::getDetails($bankCode);

        $trace->info(
            TraceCode::DEFAULT_IFSC_FETCH_DETAILS,
            [
                self::OLD_IFSC     => $oldIfsc,
                self::BANK_CODE    => $bankCode,
                self::BANK_DETAILS => $bankDetails,
            ]
        );

        if (empty($bankDetails) === false)
        {
            if (empty($bankDetails[self::IFSC]) === false)
            {
                return $bankDetails[self::IFSC];
            }
        }

        return self::DEFAULT_IFSC_NOT_FOUND;
    }
}
