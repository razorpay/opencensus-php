<?php

namespace RZP\Models\Ledger\ReverseShadow\CreditLoading;

use Carbon\Carbon;
use RZP\Models\Base;
use Ramsey\Uuid\Uuid;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Models\Ledger\Constants;
use RZP\Models\Merchant\Credits;
use RZP\Models\Ledger\BaseJournalEvents as BaseJournalEvents;
use RZP\Models\Ledger\ReverseShadow\ReverseShadowTrait;
use RZP\Models\Merchant\Credits\Entity as CreditEntity;
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

    private const CREDIT_TYPES = [
        Credits\Type::REFUND => [
            Constants::TRANSACTOR_EVENT => Constants::MERCHANT_REFUND_CREDIT_LOADING,
            Constants::CREDIT_BALANCE_TYPE => MerchantBalanceType::REFUND_CREDIT
        ],
        Credits\Type::FEE => [
            Constants::TRANSACTOR_EVENT => Constants::MERCHANT_FEE_CREDIT_LOADING,
            Constants::CREDIT_BALANCE_TYPE => MerchantBalanceType::FEE_CREDIT
        ],
        Credits\Type::AMOUNT => [
            Constants::TRANSACTOR_EVENT => Constants::MERCHANT_AMOUNT_CREDIT_LOADING,
            Constants::CREDIT_BALANCE_TYPE => MerchantBalanceType::AMOUNT_CREDIT
        ],
    ];

    public function createTransactionMessageForDebitJournal(CreditEntity $creditLogs, $payment): array
    {
        $creditType = self::CREDIT_TYPES[$creditLogs->getType()];

        $creditBalanceType = $creditType[Constants::CREDIT_BALANCE_TYPE];

        $paymentMerchantId = BaseJournalEvents::getRazorpayMerchantBasedOnFundType($payment, $creditBalanceType);

        $moneyParams = $this->generateMoneyParamsForDebitJournal($creditLogs);

        $transactionMessage = [
            Constants::MERCHANT_ID               => $paymentMerchantId,
            Constants::CURRENCY                  => Constants::INR_CURRENCY,
            Constants::TRANSACTION_DATE          => $creditLogs->getCreatedAt(),
        ];

        $transactionMessage[Constants::MONEY_PARAMS]      = $moneyParams;
        $transactionMessage[Constants::ADDITIONAL_PARAMS] = [ Constants::ENTRY_TYPE => Constants::ENTRY_TYPE_DEBIT ];

        return $transactionMessage;
    }

    public function createTransactionMessageForCreditJournal( CreditEntity $creditLogs): array
    {
        $moneyParams = $this->generateMoneyParamsForCreditJournal($creditLogs);

        $transactionMessage = [
            Constants::MERCHANT_ID               => $creditLogs->getMerchantId(),
            Constants::CURRENCY                  => Constants::INR_CURRENCY,
            Constants::TRANSACTION_DATE          => $creditLogs->getCreatedAt(),
        ];


        $transactionMessage[Constants::MONEY_PARAMS]      = $moneyParams;
        $transactionMessage[Constants::ADDITIONAL_PARAMS] = [ Constants::ENTRY_TYPE => Constants::ENTRY_TYPE_CREDIT ];

        return $transactionMessage;
    }

    public function generateMoneyParamsForCreditJournal( CreditEntity $creditLogs): array
    {
        $moneyParams = [];

        $amount =  abs($creditLogs->getValue());

        $moneyParams[Constants::AMOUNT]                       = strval($amount);
        $moneyParams[Constants::BASE_AMOUNT]                  = strval($amount);
        $moneyParams[Constants::CREDIT_AMOUNT]                = strval($amount);
        $moneyParams[Constants::CREDIT_CONTROL_AMOUNT]        = strval($amount);

        return $moneyParams;
    }

    public function generateMoneyParamsForDebitJournal( CreditEntity $creditLogs): array
    {
        $moneyParams = [];

        $amount = abs($creditLogs->getValue());

        $moneyParams[Constants::AMOUNT]                     = strval($amount);
        $moneyParams[Constants::BASE_AMOUNT]                = strval($amount);
        $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
        $moneyParams[Constants::CREDIT_CONTROL_AMOUNT]      = strval($amount);

        return $moneyParams;
    }


    public function createBulkTransactionMessageForMerchantCreditLoading(CreditEntity $creditLogs, $payment): array
    {
        $credit_type= self::CREDIT_TYPES[$creditLogs->getType()];

        $transactorEvent = $credit_type[Constants::TRANSACTOR_EVENT];

        $creditJournal = $this->createTransactionMessageForCreditJournal($creditLogs);
        $debitJournal = $this->createTransactionMessageForDebitJournal($creditLogs, $payment);

        $bulkJournals = [$debitJournal, $creditJournal];

        $transactionMessage = [
            Constants::TRANSACTOR_EVENT             => $transactorEvent,
            Constants::TRANSACTOR_ID                => $creditLogs->getPublicId(),
            Constants::TRANSACTION_DATE             => $creditLogs->getCreatedAt(),
            Constants::CURRENCY                     => Constants::INR_CURRENCY,
            Constants::JOURNALS                     => $bulkJournals,
            Constants::IDEMPOTENCY_KEY              => Uuid::uuid1(),
            Constants::LEDGER_INTEGRATION_MODE      => Constants::REVERSE_SHADOW,
            Constants::TENANT                       => Constants::TENANT_PG
        ];

        return $transactionMessage;
    }

    public function createReverseShadowLedgerEntries(CreditEntity $creditLogs, $payment)
    {
        $transactionMessage = [];
        if($creditLogs->getType() === Credits\Type::AMOUNT )
        {
            $transactionMessage = $this->createTransactionMessageForMerchantAmountCreditLoading($creditLogs);
        }
        else
        {
            $transactionMessage = $this->createBulkTransactionMessageForMerchantCreditLoading($creditLogs, $payment);
        }

        $transactorId = $transactionMessage[Constants::TRANSACTOR_ID];
        $transactorEvent = $transactionMessage[Constants::TRANSACTOR_EVENT];

        $payloadName = $this->getPayloadName($transactorId, $transactorEvent);

        $outboxPayload = $this->prepareOutboxPayload($payloadName, $transactionMessage);

        $this->saveToLedgerOutbox($outboxPayload, $transactorEvent);
    }

    public function createTransactionMessageForMerchantAmountCreditLoading(CreditEntity $creditLogs): array
    {
        $credit_type= self::CREDIT_TYPES[Credits\Type::AMOUNT];

        $transactorEvent = $credit_type[Constants::TRANSACTOR_EVENT];
        $amount =  abs($creditLogs->getValue());

        return array(
            Constants::TRANSACTOR_ID             => $creditLogs->getPublicId(),
            Constants::TRANSACTOR_EVENT          => $transactorEvent,
            Constants::MONEY_PARAMS              => [
                Constants::AMOUNT_CREDITS   => strval($amount),
                Constants::RAZORPAY_REWARD  => strval($amount)
            ],
            Constants::ADDITIONAL_PARAMS         => null,
            Constants::MERCHANT_ID               => $creditLogs->getMerchantId(),
            Constants::CURRENCY                  => Constants::INR_CURRENCY,
            Constants::TRANSACTION_DATE          => $creditLogs->getCreatedAt(),
            Constants::IDEMPOTENCY_KEY              => Uuid::uuid1(),
            Constants::LEDGER_INTEGRATION_MODE      => Constants::REVERSE_SHADOW,
            Constants::TENANT                       => Constants::TENANT_PG
        );
    }

}
