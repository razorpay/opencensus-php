<?php

namespace RZP\Models\Payout;

use RZP\Models\Merchant;
use RZP\Models\FundAccount;
use RZP\Models\Payout as PayoutModel;

class BatchHelper
{
    const RAZORPAYX_ACCOUNT_NUMBER = 'razorpayx_account_number';
    const AMOUNT                   = 'amount';
    const AMOUNT_IN_RUPEES         = 'amount_in_rupees';
    const CURRENCY                 = 'currency';
    const MODE                     = 'mode';
    const PURPOSE                  = 'purpose';
    const NARRATION                = 'narration';
    const REFERENCE_ID             = 'reference_id';
    const PAYOUT                   = 'payout';
    const NOTES                    = 'notes';
    const BALANCE_ID               = 'balance_id';
    const SCHEDULED_AT             = 'scheduled_at';
    const PAYOUT_UPDATE_ACTION     = 'payout_update_action';
    const PAYOUT_MODE              = 'mode';

    // Different types of payout amounts used in bulk payouts
    const PAISE                    = 'paise';
    const RUPEES                   = 'rupees';

    public static function getPayoutInput(
        array $entry,
        array $fundAccount,
        Merchant\Entity $merchant): array
    {
        // Call to validateAndTranslateAccountNumberForBanking() expect the key in snake case.
        $entry['account_number'] = trim($entry[self::RAZORPAYX_ACCOUNT_NUMBER]);
        // Optimization: Have a map of account number to balance id to avoid multiple read calls.
        $merchant->getValidator()->validateAndTranslateAccountNumberForBanking($entry);

        $input = [
            PayoutModel\Entity::PURPOSE         => $entry[self::PAYOUT][self::PURPOSE],
            PayoutModel\Entity::NARRATION       => $entry[self::PAYOUT][self::NARRATION],
            PayoutModel\Entity::CURRENCY        => $entry[self::PAYOUT][self::CURRENCY],
            // Key balance_id got appended in above validation call.
            PayoutModel\Entity::BALANCE_ID      => $entry[self::BALANCE_ID],
            PayoutModel\Entity::FUND_ACCOUNT_ID => $fundAccount[FundAccount\Entity::ID],
            PayoutModel\Entity::MODE            => $entry[self::PAYOUT][self::MODE],
            PayoutModel\Entity::REFERENCE_ID    => $entry[self::PAYOUT][self::REFERENCE_ID],
            // Notes is optional.
            PayoutModel\Entity::NOTES           => $entry[self::NOTES] ?? [],
            PayoutModel\Entity::IDEMPOTENCY_KEY => $entry[Entity::IDEMPOTENCY_KEY],
        ];

        if (empty($entry[self::PAYOUT][self::AMOUNT]) === true)
        {
            // using bcmul() to avoid floating point inaccuracies
            $input[PayoutModel\Entity::AMOUNT] = (int) bcmul($entry[self::PAYOUT][self::AMOUNT_IN_RUPEES], '100');
        }
        else
        {
            $input[PayoutModel\Entity::AMOUNT] = $entry[self::PAYOUT][self::AMOUNT];
        }

        if ((isset($entry[self::PAYOUT][self::SCHEDULED_AT]) === true) and
            (empty($entry[self::PAYOUT][self::SCHEDULED_AT]) === false))
        {
            $input[PayoutModel\Entity::SCHEDULED_AT] = $entry[self::PAYOUT][self::SCHEDULED_AT];
        }

        $input[PayoutModel\Entity::NOTES] = self::formatNotesInput($input[PayoutModel\Entity::NOTES]);

        $input[PayoutModel\Entity::ORIGIN] = PayoutModel\Entity::DASHBOARD;

        // Returns removing attributes with empty values.
        return array_filter($input);
    }

    /**
     * Formats notes array. If any key has empty string, it removes it from array.
     *
     * @param array $notes
     *
     * @return array
     */
    private static function formatNotesInput(array $notes): array
    {
        $notesArray = [];

        foreach ($notes as $key => $value)
        {
            if (empty($value) === false)
            {
                $notesArray[$key] = $value;
            }
        }
        return $notesArray;
    }
}


