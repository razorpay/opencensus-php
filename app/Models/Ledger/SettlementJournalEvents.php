<?php


namespace RZP\Models\Ledger;

use RZP\Models\Settlement;
use RZP\Models\Transaction;

class SettlementJournalEvents
{
    public static function createTransactionMessageForSettlement(Settlement\Entity $settlement, Transaction\Entity  $transaction): array
    {

        $transactionMessage = BaseJournalEvents::generateBaseForJournalEntry($transaction);

        $refundData = array(
            Constants::TRANSACTOR_ID                 => $settlement->getId(),
            Constants::TRANSACTOR_EVENT              => Constants::SETTLEMENT_PROCESSED,
        );
        return array_merge($transactionMessage, $refundData);
    }
}
