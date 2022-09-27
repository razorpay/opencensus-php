<?php

namespace RZP\Models\Ledger;

use RZP\Models\Merchant\Credits\Entity as CreditEntity;
use RZP\Models\Merchant\Credits;
use RZP\Models\Merchant\Balance\Type as MerchantBalanceType;

class MerchantCreditJournalEvents
{
     private const CREDIT_TYPES = [
        Credits\Type::REFUND => [
            Constants::TRANSACTOR_EVENT => Constants::MERCHANT_REFUND_CREDIT_LOADING,
            Constants::CREDIT_BALANCE_TYPE => MerchantBalanceType::REFUND_CREDIT
        ],
        Credits\Type::FEE => [
            Constants::TRANSACTOR_EVENT => Constants::MERCHANT_FEE_CREDIT_LOADING,
            Constants::CREDIT_BALANCE_TYPE => MerchantBalanceType::FEE_CREDIT
        ],
     ];

    public static function createTransactionMessageForDebitJournal( CreditEntity $creditLogs, $payment): array
    {
        $credit_type= self::CREDIT_TYPES[$creditLogs->getType()][ Constants::CREDIT_BALANCE_TYPE];
        $paymentMerchantId = BaseJournalEvents::getRazorpayMerchantBasedOnFundType($payment, $credit_type);

        $moneyParams = self::generateMoneyParamsForDebitJournal($creditLogs);

        $transactionMessage = [
            Constants::MERCHANT_ID               => $paymentMerchantId,
            Constants::CURRENCY                  => Constants::INR_CURRENCY,
            Constants::TRANSACTION_DATE          => $creditLogs->getCreatedAt(),
        ];

        $transactionMessage[Constants::MONEY_PARAMS]      = $moneyParams;
        $transactionMessage[Constants::ADDITIONAL_PARAMS] = [ Constants::ENTRY_TYPE => Constants::ENTRY_TYPE_DEBIT ];

        return $transactionMessage;
    }

    public static function createTransactionMessageForCreditJournal( CreditEntity $creditLogs): array
    {
        $moneyParams = self::generateMoneyParamsForCreditJournal($creditLogs);

        $transactionMessage = [
            Constants::MERCHANT_ID               => $creditLogs->getMerchantId(),
            Constants::CURRENCY                  => Constants::INR_CURRENCY,
            Constants::TRANSACTION_DATE          => $creditLogs->getCreatedAt(),
        ];


        $transactionMessage[Constants::MONEY_PARAMS]      = $moneyParams;
        $transactionMessage[Constants::ADDITIONAL_PARAMS] = [ Constants::ENTRY_TYPE => Constants::ENTRY_TYPE_CREDIT ];

        return $transactionMessage;
    }

    public static function generateMoneyParamsForCreditJournal( CreditEntity $creditLogs): array
    {
        $moneyParams = [];

        $amount =  abs($creditLogs->getValue());

        $moneyParams[Constants::AMOUNT]                       = strval($amount);
        $moneyParams[Constants::BASE_AMOUNT]                  = strval($amount);
        $moneyParams[Constants::CREDIT_AMOUNT]                = strval($amount);
        $moneyParams[Constants::CREDIT_CONTROL_AMOUNT]        = strval($amount);

        return $moneyParams;
    }

    public static function generateMoneyParamsForDebitJournal( CreditEntity $creditLogs): array
    {
        $moneyParams = [];

        $amount = abs($creditLogs->getValue());

        $moneyParams[Constants::AMOUNT]                     = strval($amount);
        $moneyParams[Constants::BASE_AMOUNT]                = strval($amount);
        $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
        $moneyParams[Constants::CREDIT_CONTROL_AMOUNT]      = strval($amount);

        return $moneyParams;
    }


    public static function createBulkTransactionMessageForMerchantCreditLoading(CreditEntity $creditLogs, $payment): array
    {
        $credit_type= self::CREDIT_TYPES[$creditLogs->getType()];

        $transactorEvent = $credit_type[Constants::TRANSACTOR_EVENT];

        $creditJournal = self::createTransactionMessageForCreditJournal($creditLogs);
        $debitJournal = self::createTransactionMessageForDebitJournal($creditLogs, $payment);

        $bulkJournals = [$debitJournal, $creditJournal];

        $transactionMessage = [
            Constants::TRANSACTOR_EVENT             => $transactorEvent,
            Constants::TRANSACTOR_ID                => $creditLogs->getPublicId(),
            Constants::TRANSACTION_DATE             => $creditLogs->getCreatedAt(),
            Constants::CURRENCY                     => Constants::INR_CURRENCY,
            Constants::JOURNALS                     => $bulkJournals,
        ];

        return $transactionMessage;
    }





}

