<?php

namespace RZP\Models\Ledger;


use RZP\Models\Adjustment\Entity as AdjustmentEntity;
use RZP\Models\Merchant\Balance\Type as MerchantBalanceType;

class MerchantReserveBalanceJournalEvents
{

    public static function createTransactionMessageForDebitJournal( AdjustmentEntity $adjInput, $payment): array
    {
        $fundType = MerchantBalanceType::RESERVE_BALANCE;
        $paymentMerchantId = BaseJournalEvents::getRazorpayMerchantBasedOnFundType($payment, $fundType);

        $moneyParams = self::generateMoneyParamsForDebitJournal($adjInput);

        $transactionMessage = [
            Constants::MERCHANT_ID               => $paymentMerchantId,
            Constants::CURRENCY                  => Constants::INR_CURRENCY,
            Constants::TRANSACTION_DATE          => $adjInput->getCreatedAt(),
        ];

        $transactionMessage[Constants::MONEY_PARAMS]      = $moneyParams;
        $transactionMessage[Constants::ADDITIONAL_PARAMS] = [ Constants::ENTRY_TYPE => Constants::ENTRY_TYPE_DEBIT ];

        return $transactionMessage;
    }

    public static function createTransactionMessageForCreditJournal( AdjustmentEntity $adjInput): array
    {
        $moneyParams = self::generateMoneyParamsForCreditJournal($adjInput);

        $transactionMessage = [
            Constants::MERCHANT_ID               => $adjInput->getMerchantId(),
            Constants::CURRENCY                  => Constants::INR_CURRENCY,
            Constants::TRANSACTION_DATE          => $adjInput->getCreatedAt(),
        ];

        $transactionMessage[Constants::MONEY_PARAMS]      = $moneyParams;
        $transactionMessage[Constants::ADDITIONAL_PARAMS] = [ Constants::ENTRY_TYPE => Constants::ENTRY_TYPE_CREDIT ];

        return $transactionMessage;
    }
    private static function generateMoneyParamsForCreditJournal(AdjustmentEntity $adjInput): array
    {
        $moneyParams = [];

        $creditAmount = abs($adjInput->getAmount());

        $moneyParams[Constants::AMOUNT]                       = strval($creditAmount);
        $moneyParams[Constants::BASE_AMOUNT]                  = strval($creditAmount);
        $moneyParams[Constants::RESERVE_BALANCE_AMOUNT]                = strval($creditAmount);
        $moneyParams[Constants::CREDIT_CONTROL_AMOUNT]        = strval($creditAmount);

        return $moneyParams;
    }

    public static function generateMoneyParamsForDebitJournal( AdjustmentEntity $adjInput): array
    {
        $moneyParams = [];

        $amount = abs($adjInput->getAmount());

        $moneyParams[Constants::AMOUNT]                     = strval($amount);
        $moneyParams[Constants::BASE_AMOUNT]                = strval($amount);
        $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
        $moneyParams[Constants::CREDIT_CONTROL_AMOUNT]      = strval($amount);

        return $moneyParams;
    }

    public static function createBulkTransactionMessageForMerchantReserveBalanceLoading(AdjustmentEntity $adjInput, $payment): array
    {

        $creditJournal = self::createTransactionMessageForCreditJournal($adjInput);
        $debitJournal = self::createTransactionMessageForDebitJournal($adjInput, $payment);

        $bulkJournals = [$debitJournal, $creditJournal];

        $transactionMessage = [
            Constants::TRANSACTOR_EVENT             => Constants::MERCHANT_RESERVE_BALANCE_LOADING,
            Constants::TRANSACTOR_ID                => $adjInput->getPublicId(),
            Constants::TRANSACTION_DATE             => $adjInput->getCreatedAt(),
            Constants::CURRENCY                     => Constants::INR_CURRENCY,
            Constants::JOURNALS                     => $bulkJournals,
        ];

        return $transactionMessage;
    }




}
