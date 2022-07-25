<?php


namespace RZP\Models\Ledger;

use RZP\Models\Settlement;
use RZP\Models\Transaction;

class SettlementJournalEvents
{
    public static function createTransactionMessageForSettlement(Settlement\Entity $settlement, Transaction\Entity  $transaction): array
    {

        $transactionMessage = BaseJournalEvents::generateBaseForJournalEntry($transaction);

        $moneyParams = [
            Constants::BASE_AMOUNT  => strval($transaction->getAmount()),
            Constants::AMOUNT       => strval($transaction->getAmount()),
        ];

        $refundData = array(
            Constants::TRANSACTOR_ID        => $settlement->getPublicId(),
            Constants::TRANSACTOR_EVENT     => Constants::SETTLEMENT_PROCESSED,
            Constants::MONEY_PARAMS         => $moneyParams
        );
        return array_merge($transactionMessage, $refundData);
    }
}
