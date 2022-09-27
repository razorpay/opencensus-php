<?php


namespace RZP\Models\Ledger;

use RZP\Models\Merchant;
use RZP\Models\Transaction;

class BaseJournalEvents
{
    public static function generateBaseForJournalEntry(Transaction\Entity  $transaction): array
    {
        return array(
            Constants::BASE_AMOUNT               => strval(abs($transaction->getAmount())),
            Constants::API_TRANSACTION_ID        => $transaction->getId(),
            Constants::MERCHANT_ID               => $transaction->getMerchantId(),
            Constants::CURRENCY                  => $transaction->getCurrency(),
            Constants::TRANSACTION_DATE          => $transaction->getCreatedAt(),
        );
    }

    public static function getRazorpayMerchantBasedOnFundType($payment, $fundType): string
    {
        if ($payment === null)
        {
            return (new Merchant\Core())->getRazorpayMerchantBasedOnType($fundType)[Constants::MERCHANT_ID];
        }
        else
        {
            return $payment[Constants::MERCHANT_ID];
        }
    }
}
