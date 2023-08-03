<?php

namespace RZP\Models\Ledger\ReverseShadow\ReserveBalanceLoading;

use Ramsey\Uuid\Uuid;
use RZP\Models\Adjustment\Entity as AdjustmentEntity;
use RZP\Models\Base;
use RZP\Models\Ledger\BaseJournalEvents as BaseJournalEvents;
use RZP\Models\Ledger\Constants;
use RZP\Models\Ledger\ReverseShadow\ReverseShadowTrait;
use RZP\Models\Merchant\Balance\Type as MerchantBalanceType;

class Core extends Base\Core
{
    protected $merchant;

    use ReverseShadowTrait;

    public function __construct()
    {
        parent::__construct();

        $this->merchant = $this->app['basicauth']->getMerchant();
    }

    public function createReserveBalanceLoadingReverseShadowLedgerEntries(AdjustmentEntity $adjustment, $payment)
    {
        $transactionMessage = $this->createBulkTransactionMessageForReserveBalanceLoading($adjustment, $payment);

        $transactorId = $transactionMessage[Constants::TRANSACTOR_ID];

        $transactorEvent = $transactionMessage[Constants::TRANSACTOR_EVENT];

        $payloadName = $this->getPayloadName($transactorId, $transactorEvent);

        $outboxPayload = $this->prepareOutboxPayload($payloadName, $transactionMessage);

        $this->saveToLedgerOutbox($outboxPayload, $transactorEvent);
    }

    private function createBulkTransactionMessageForReserveBalanceLoading(AdjustmentEntity $adjustment, $payment)
    {
        $transactorEvent = Constants::MERCHANT_RESERVE_BALANCE_LOADING;

        $creditJournal = $this->createTransactionMessageForCreditJournal($adjustment);

        $debitJournal = $this->createTransactionMessageForDebitJournal($adjustment, $payment);

        $bulkJournals = [$debitJournal, $creditJournal];

        $transactionMessage = [
            Constants::TRANSACTOR_EVENT             => $transactorEvent,
            Constants::TRANSACTOR_ID                => $adjustment->getPublicId(),
            Constants::TRANSACTION_DATE             => $adjustment->getCreatedAt(),
            Constants::CURRENCY                     => Constants::INR_CURRENCY,
            Constants::JOURNALS                     => $bulkJournals,
            Constants::IDEMPOTENCY_KEY              => Uuid::uuid1(),
            Constants::LEDGER_INTEGRATION_MODE      => Constants::REVERSE_SHADOW,
            Constants::TENANT                       => Constants::TENANT_PG
        ];

        return $transactionMessage;
    }

    public function createTransactionMessageForDebitJournal( AdjustmentEntity $adjInput, $payment): array
    {
        $fundType = MerchantBalanceType::RESERVE_BALANCE;

        $paymentMerchantId = BaseJournalEvents::getRazorpayMerchantBasedOnFundType($payment, $fundType);

        $moneyParams = $this->generateMoneyParamsForDebitJournal($adjInput);

        $transactionMessage = [
            Constants::MERCHANT_ID               => $paymentMerchantId,
            Constants::CURRENCY                  => Constants::INR_CURRENCY,
            Constants::TRANSACTION_DATE          => $adjInput->getCreatedAt(),
        ];

        $transactionMessage[Constants::MONEY_PARAMS]      = $moneyParams;
        $transactionMessage[Constants::ADDITIONAL_PARAMS] = [ Constants::ENTRY_TYPE => Constants::ENTRY_TYPE_DEBIT ];

        return $transactionMessage;
    }

    public function createTransactionMessageForCreditJournal(AdjustmentEntity $adjInput): array
    {
        $moneyParams = $this->generateMoneyParamsForCreditJournal($adjInput);

        $transactionMessage = [
            Constants::MERCHANT_ID               => $adjInput->getMerchantId(),
            Constants::CURRENCY                  => Constants::INR_CURRENCY,
            Constants::TRANSACTION_DATE          => $adjInput->getCreatedAt(),
        ];

        $transactionMessage[Constants::MONEY_PARAMS]      = $moneyParams;
        $transactionMessage[Constants::ADDITIONAL_PARAMS] = [ Constants::ENTRY_TYPE => Constants::ENTRY_TYPE_CREDIT ];

        return $transactionMessage;
    }

    private function generateMoneyParamsForCreditJournal(AdjustmentEntity $adjInput): array
    {
        $moneyParams = [];

        $creditAmount = abs($adjInput->getAmount());

        $moneyParams[Constants::AMOUNT]                       = strval($creditAmount);
        $moneyParams[Constants::BASE_AMOUNT]                  = strval($creditAmount);
        $moneyParams[Constants::RESERVE_BALANCE_AMOUNT]       = strval($creditAmount);
        $moneyParams[Constants::CREDIT_CONTROL_AMOUNT]        = strval($creditAmount);

        return $moneyParams;
    }

    public function generateMoneyParamsForDebitJournal(AdjustmentEntity $adjInput): array
    {
        $moneyParams = [];

        $amount = abs($adjInput->getAmount());

        $moneyParams[Constants::AMOUNT]                     = strval($amount);
        $moneyParams[Constants::BASE_AMOUNT]                = strval($amount);
        $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
        $moneyParams[Constants::CREDIT_CONTROL_AMOUNT]      = strval($amount);

        return $moneyParams;
    }
}
