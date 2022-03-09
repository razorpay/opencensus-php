<?php


namespace RZP\Models\Ledger;

use RZP\Models\Transaction;

class BaseJournalEvents
{
    public static function generateBaseForJournalEntry(Transaction\Entity  $transaction): array
    {
        $tax = $transaction->getTax() != null ? abs($transaction->getTax()) : 0;
        $fee = $transaction->getFee() != null ? abs($transaction->getFee()) - $tax : 0;

        return array(
            Constants::BASE_AMOUNT               => strval(abs($transaction->getAmount())),
            Constants::API_TRANSACTION_ID        => $transaction->getId(),
            Constants::MERCHANT_ID               => $transaction->getMerchantId(),
            Constants::CURRENCY                  => $transaction->getCurrency(),
            Constants::AMOUNT                    => strval(abs($transaction->getAmount())),
            Constants::TAX                       => strval(abs($tax)),
            Constants::COMMISSION                => strval(abs($fee)),
            Constants::TRANSACTION_DATE          => $transaction->getCreatedAt(),
        );
    }
}
