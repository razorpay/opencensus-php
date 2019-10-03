<?php

namespace RZP\Models\Batch\Helpers;

use RZP\Models\Merchant;
use RZP\Models\Batch\Header;
use RZP\Models\Payout as PayoutModel;
use RZP\Models\FundAccount as FundAccountModel;

class Payout
{
    public static function getPayoutInput(
        array $entry,
        FundAccountModel\Entity $fundAccount,
        Merchant\Entity $merchant): array
    {

        // Call to validateAndTranslateAccountNumberForBanking() expect the key in snake case.
        $entry['account_number'] = $entry[Header::RAZORPAYX_ACCOUNT_NUMBER];
        // Optimization: Have a map of account number to balance id to avoid multiple read calls.

        /** @var Merchant\Validator $merchantValidator */
        $merchantValidator = $merchant->getValidator();

        $merchantValidator->validateAndTranslateAccountNumberForBanking($entry);

        $input = [
            PayoutModel\Entity::PURPOSE         => $entry[Header::PAYOUT_PURPOSE],
            PayoutModel\Entity::NARRATION       => $entry[Header::PAYOUT_NARRATION],
            PayoutModel\Entity::AMOUNT          => $entry[Header::PAYOUT_AMOUNT],
            PayoutModel\Entity::CURRENCY        => $entry[Header::PAYOUT_CURRENCY],
            // Key balance_id got appended in above validation call.
            PayoutModel\Entity::BALANCE_ID      => $entry['balance_id'],
            PayoutModel\Entity::FUND_ACCOUNT_ID => $fundAccount->getPublicId(),
            PayoutModel\Entity::MODE            => $entry[Header::PAYOUT_MODE],
            PayoutModel\Entity::REFERENCE_ID    => $entry[Header::PAYOUT_REFERENCE_ID],
            // Notes is optional.
            PayoutModel\Entity::NOTES           => $entry[Header::NOTES] ?? [],
        ];

        // Returns removing attributes with empty values.
        return array_filter($input);
    }
}
