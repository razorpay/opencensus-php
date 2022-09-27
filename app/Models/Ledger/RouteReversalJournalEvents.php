<?php

namespace RZP\Models\Ledger;

use RZP\Models\Transaction;
use RZP\Models\Ledger\Constants as LedgerConstants;

class RouteReversalJournalEvents extends BaseJournalEvents
{
    public static function createTransactionMessageForReversalDebit(Transaction\Entity $refundTxn): array
    {
        $moneyParams = self::generateMoneyParamsForReversalDebit($refundTxn);

        $transactionMessage = BaseJournalEvents::generateBaseForJournalEntry($refundTxn);

        unset($transactionMessage[LedgerConstants::BASE_AMOUNT]);

        $transactionMessage[LedgerConstants::MONEY_PARAMS] = $moneyParams;

        $additionalParams = [
            Constants::ENTRY_TYPE => Constants::ENTRY_TYPE_DEBIT
        ];

        if ($refundTxn->isRefundCredits() === true)
        {
            $additionalParams[Constants::CREDIT_ACCOUNTING] = Constants::REFUND_CREDITS;
        }

        $transactionMessage[LedgerConstants::ADDITIONAL_PARAMS] = $additionalParams;

        return $transactionMessage;
    }

    public static function createTransactionMessageForReversalCredit(Transaction\Entity $reversalTxn): array
    {
        $moneyParams = self::generateMoneyParamsForReversalCredit($reversalTxn);

        $transactionMessage = BaseJournalEvents::generateBaseForJournalEntry($reversalTxn);

        unset($transactionMessage[LedgerConstants::BASE_AMOUNT]);

        $transactionMessage[LedgerConstants::MONEY_PARAMS] = $moneyParams;
        $transactionMessage[LedgerConstants::ADDITIONAL_PARAMS] = [ Constants::ENTRY_TYPE => Constants::ENTRY_TYPE_CREDIT ];

        return $transactionMessage;
    }

    public static function generateMoneyParamsForReversalCredit(Transaction\Entity $reversalTxn): array
    {
        $moneyParams = [];

        $amount = $reversalTxn->getAmount();

        $moneyParams[Constants::AMOUNT]                     = strval($amount);
        $moneyParams[Constants::BASE_AMOUNT]                = strval($amount);
        $moneyParams[Constants::MERCHANT_PAYABLE_AMOUNT]    = strval($amount);
        $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);

        return $moneyParams;
    }

    public static function generateMoneyParamsForReversalDebit(Transaction\Entity $refundTxn): array
    {
        $moneyParams = [];

        $amount = $refundTxn->getAmount();

        $moneyParams[Constants::AMOUNT]                     = strval($amount);
        $moneyParams[Constants::BASE_AMOUNT]                = strval($amount);
        $moneyParams[Constants::MERCHANT_PAYABLE_AMOUNT]    = strval($amount);

        if ($refundTxn->isRefundCredits() === true)
        {
            $moneyParams[Constants::REFUND_CREDITS]         = strval($amount);
        }
        else
        {
            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
        }

        return $moneyParams;
    }

    public static function createBulkTransactionMessageForRouteReversal($reversal, $refund): array
    {

        $reversalTxn  =  $reversal->transaction;
        $reversalCreditJournal = self::createTransactionMessageForReversalCredit($reversalTxn);

        $refundTxn  =  $refund->transaction;
        $reversalDebitJournal = self::createTransactionMessageForReversalDebit($refundTxn);

        $bulkJournals = [$reversalDebitJournal, $reversalCreditJournal];

        $transactionMessage = [
            Constants::TRANSACTOR_EVENT             => Constants::TRANSFER_REVERSAL_PROCESSED,
            Constants::TRANSACTOR_ID                => $reversal->getPublicId(),
            Constants::TRANSACTION_DATE             => $reversal->getCreatedAt(),
            Constants::CURRENCY                     => $reversal->getCurrency(),
            Constants::JOURNALS                     => $bulkJournals,
        ];

        return $transactionMessage;

    }

}
