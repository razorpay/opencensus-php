<?php

namespace RZP\Models\Ledger;

use RZP\Models\Payment;
use RZP\Models\Transfer;
use RZP\Models\Transaction;
use RZP\Models\Ledger\Constants as LedgerConstants;

class RouteJournalEvents extends BaseJournalEvents
{
    public static function createTransactionMessageForTransferDebit(Transfer\Entity $transfer, Transaction\Entity $txn): array
    {
        $moneyParams = self::generateMoneyParamsForTransferDebit($txn);

        $additionalParams = self::fetchRulesForTransferCredits($txn);

        $transactionMessage = BaseJournalEvents::generateBaseForJournalEntry($txn);

        unset($transactionMessage[LedgerConstants::BASE_AMOUNT]);

        $transactionMessage[LedgerConstants::MONEY_PARAMS]           = $moneyParams;
        $transactionMessage[LedgerConstants::ADDITIONAL_PARAMS]      = array_merge(
                                                                            $additionalParams, [Constants::ENTRY_TYPE => Constants::ENTRY_TYPE_DEBIT]
                                                                        );

        return $transactionMessage;
    }

    public static function createTransactionMessageForTransferCredit(Payment\Entity $payment, Transaction\Entity $txn): array
    {
        $moneyParams = self::generateMoneyParamsForTransferCredit($txn);

        $transactionMessage = BaseJournalEvents::generateBaseForJournalEntry($txn);

        unset($transactionMessage[LedgerConstants::BASE_AMOUNT]);

        $transactionMessage[LedgerConstants::MONEY_PARAMS]           = $moneyParams;
        $transactionMessage[Constants::ADDITIONAL_PARAMS]            = [ Constants::ENTRY_TYPE => Constants::ENTRY_TYPE_CREDIT ];

        return  $transactionMessage;
    }

    public static function generateMoneyParamsForTransferCredit(Transaction\Entity  $transaction): array
    {
        $moneyParams = [];

        $amount = $transaction->getAmount();

        $moneyParams[Constants::AMOUNT]                     = strval($amount);
        $moneyParams[Constants::BASE_AMOUNT]                = strval($amount);
        $moneyParams[Constants::MERCHANT_PAYABLE_AMOUNT]    = strval($amount);
        $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);

        return $moneyParams;
    }

    public static function generateMoneyParamsForTransferDebit(Transaction\Entity  $transaction): array
    {
        $moneyParams = [];

        $amount = $transaction->getAmount();
        $tax = $transaction->getTax() !== null ? $transaction->getTax() : 0;
        $fee = $transaction->getFee() != null ? $transaction->getFee() - $tax : 0;

        $moneyParams[Constants::AMOUNT]                         = strval($amount);
        $moneyParams[Constants::BASE_AMOUNT]                    = strval($amount);

        if($transaction->isFeeCredits() === true)
        {
            $moneyParams[Constants::MERCHANT_PAYABLE_AMOUNT]    = strval($amount);
            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
            $moneyParams[Constants::TAX]                        = strval($tax);
            $moneyParams[Constants::TRANSFER_COMMISSION]        = strval($fee);
            $moneyParams[Constants::FEE_CREDITS]                = strval($tax + $fee);
        }
        else if ($transaction->isGratis() === true)
        {
            $moneyParams[Constants::MERCHANT_PAYABLE_AMOUNT]    = strval($amount);
            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
        }
        // Normal transfer debit scenario (commissions considered)
        else
        {
            $moneyParams[Constants::MERCHANT_PAYABLE_AMOUNT]    = strval($amount);
            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount + $fee + $tax);
            $moneyParams[Constants::TAX]                        = strval($tax);
            $moneyParams[Constants::TRANSFER_COMMISSION]        = strval($fee);
        }

        return $moneyParams;
    }

    public static function fetchRulesForTransferCredits(Transaction\Entity $transaction)
    {
        $rule = [];

        if($transaction->isGratis() === true)
        {
            $rule[Constants::CREDIT_ACCOUNTING] = Constants::AMOUNT_CREDITS;
        }

        if($transaction->isFeeCredits() === true)
        {
            $rule[Constants::CREDIT_ACCOUNTING] = Constants::FEE_CREDITS;
        }

        return $rule;
    }

    public static function createBulkTransactionMessageForRoute(Payment\Entity $payment): array
    {
        $txn = $payment->transaction;

        $transferCreditJournal = self::createTransactionMessageForTransferCredit($payment, $txn);

        $transfer = $payment->transfer;

        $debitTransaction = $transfer->transaction;

        $transferDebitJournal = self::createTransactionMessageForTransferDebit($transfer, $debitTransaction);

        $bulkJournals = [$transferDebitJournal, $transferCreditJournal];

        $transactionMessage = [
            Constants::TRANSACTOR_EVENT             => Constants::TRANSFER,
            Constants::TRANSACTOR_ID                => $transfer->getPublicId(),
            Constants::TRANSACTION_DATE             => $transfer->getCreatedAt(),
            Constants::CURRENCY                     => Constants::INR_CURRENCY,
            Constants::JOURNALS                     => $bulkJournals,
        ];

        return $transactionMessage;

    }

    public static function generateMoneyParamsForCustomerWalletLoadingDebit(Transaction\Entity  $transaction): array
    {
        $moneyParams = [];

        $amount = $transaction->getAmount();
        $tax = $transaction->getTax() !== null ? $transaction->getTax() : 0;
        $fee = $transaction->getFee() != null ? $transaction->getFee() - $tax : 0;

        $moneyParams[Constants::AMOUNT]                         = strval($amount);
        $moneyParams[Constants::BASE_AMOUNT]                    = strval($amount);

        if($transaction->isFeeCredits() === true)
        {
            $moneyParams[Constants::CUSTOMER_WALLET_AMOUNT]     = strval($amount);
            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
            $moneyParams[Constants::TAX]                        = strval($tax);
            $moneyParams[Constants::TRANSFER_COMMISSION]        = strval($fee);
            $moneyParams[Constants::FEE_CREDITS]                = strval($tax + $fee);
        }
        else if ($transaction->isGratis() === true)
        {
            $moneyParams[Constants::CUSTOMER_WALLET_AMOUNT]     = strval($amount);
            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
        }
        // Normal transfer debit scenario (commissions considered)
        else
        {
            $moneyParams[Constants::CUSTOMER_WALLET_AMOUNT]     = strval($amount);
            $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount + $fee + $tax);
            $moneyParams[Constants::TAX]                        = strval($tax);
            $moneyParams[Constants::TRANSFER_COMMISSION]        = strval($fee);
        }

        return $moneyParams;
    }

    public static function createTransactionMessageForCustomerWalletLoading($transfer): array
    {
        $debitTransaction = $transfer->transaction;
        $transactionMessage = BaseJournalEvents::generateBaseForJournalEntry($debitTransaction);
        unset($transactionMessage[Constants::BASE_AMOUNT]);

        $additionalParams = self::fetchRulesForTransferCredits($debitTransaction);
        $moneyParams = self::generateMoneyParamsForCustomerWalletLoadingDebit($debitTransaction);

        $transferData = [
            Constants::TRANSACTOR_EVENT             => Constants::CUSTOMER_WALLET_LOADING,
            Constants::TRANSACTOR_ID                => $transfer->getPublicId(),
            Constants::MONEY_PARAMS                 => $moneyParams
        ];

        if (empty($additionalParams) === false)
        {
            $transferData[Constants::ADDITIONAL_PARAMS] = $additionalParams;
        }

        return array_merge($transactionMessage, $transferData);
    }


}
