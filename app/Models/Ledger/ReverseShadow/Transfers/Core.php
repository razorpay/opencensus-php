<?php

namespace RZP\Models\Ledger\ReverseShadow\Transfers;

use RZP\Constants\Metric;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Base;
use Ramsey\Uuid\Uuid;
use RZP\Models\Ledger\Constants;
use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Transfer;
use RZP\Models\Pricing\Fee;
use RZP\Models\Transaction;
use RZP\Models\Merchant\Balance;
use RZP\Models\Merchant\Balance\BalanceConfig;
use RZP\Models\Ledger\ReverseShadow\ReverseShadowTrait;
use RZP\Services\KafkaProducer;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{

    protected $merchant;

    use ReverseShadowTrait;

    public function __construct()
    {
        parent::__construct();

        $this->merchant = $this->app['basicauth']->getMerchant();
    }

    public function createBulkTransactionMessageForTransfer($transfer, $transferPayment, $merchantAccountBalances, $fee, $tax): array
    {
        $transferDebitJournal = $this->createTransactionMessageForDebitJournal($transfer, $merchantAccountBalances, $fee, $tax);

        $transferCreditJournal = $this->createTransactionMessageForCreditJournal($transfer, $transferPayment);

        $bulkJournals = [$transferDebitJournal, $transferCreditJournal];

        return [
            LedgerConstants::TRANSACTOR_EVENT             => LedgerConstants::TRANSFER,
            LedgerConstants::TRANSACTOR_ID                => $transfer->getPublicId(),
            LedgerConstants::TRANSACTION_DATE             => $transfer->getUpdatedAt(),
            LedgerConstants::CURRENCY                     => LedgerConstants::INR_CURRENCY,
            LedgerConstants::JOURNALS                     => $bulkJournals,
            LedgerConstants::IDEMPOTENCY_KEY              => Uuid::uuid1(),
            LedgerConstants::LEDGER_INTEGRATION_MODE      => LedgerConstants::REVERSE_SHADOW,
            LedgerConstants::TENANT                       => LedgerConstants::TENANT_PG
        ];

    }

    public function createTransactionMessageForDebitJournal($transfer, $merchantAccountBalances, $fee, $tax): array
    {
        $moneyParams = $this->generateMoneyParamsForTransferDebit($transfer, $merchantAccountBalances, $fee, $tax);

        $additionalParams = $this->fetchRulesForTransferDebit($transfer, $merchantAccountBalances, $fee, $tax);

        $transactionMessage = $this->generateBaseForJournalEntry($transfer);

        $transactionMessage[LedgerConstants::MONEY_PARAMS]           = $moneyParams;
        $transactionMessage[LedgerConstants::ADDITIONAL_PARAMS]      = array_merge(
            $additionalParams, [LedgerConstants::ENTRY_TYPE => LedgerConstants::ENTRY_TYPE_DEBIT]
        );

        return $transactionMessage;
    }

    public function generateMoneyParamsForTransferDebit(Transfer\Entity $transfer, $merchantAccountBalances, $fee, $tax): array
    {
        $moneyParams = [];

        $feeCredits = $merchantAccountBalances[LedgerConstants::MERCHANT_FEE_CREDITS];
        $amountCredits = $merchantAccountBalances[LedgerConstants::MERCHANT_AMOUNT_CREDITS];

        $fee = $fee - $tax;

        $amount = $transfer->getAmount();

        $moneyParams[LedgerConstants::AMOUNT]                         = strval($amount);
        $moneyParams[LedgerConstants::BASE_AMOUNT]                    = strval($amount);

        if ($amountCredits > 0)
        {
            $moneyParams[LedgerConstants::MERCHANT_PAYABLE_AMOUNT]    = strval($amount);
            $moneyParams[LedgerConstants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
            $moneyParams[LedgerConstants::RAZORPAY_REWARDS]           = strval($amount);
            $moneyParams[LedgerConstants::AMOUNT_CREDITS]             = strval($amount);
        }
        else if($this->isFeeCredits($feeCredits, $fee + $tax) === true)
        {
            $moneyParams[LedgerConstants::MERCHANT_PAYABLE_AMOUNT]    = strval($amount);
            $moneyParams[LedgerConstants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
            $moneyParams[LedgerConstants::TAX]                        = strval($tax);
            $moneyParams[LedgerConstants::TRANSFER_COMMISSION]        = strval($fee);
            $moneyParams[LedgerConstants::FEE_CREDITS]                = strval($tax + $fee);
        }
        // if merchant is on postpaid model
        else if($this->isTransferPostpaid($transfer) === true)
        {
            $moneyParams[LedgerConstants::MERCHANT_PAYABLE_AMOUNT]    = strval($amount);
            $moneyParams[LedgerConstants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
            $moneyParams[LedgerConstants::TAX]                        = strval($tax);
            $moneyParams[LedgerConstants::TRANSFER_COMMISSION]        = strval($fee);
            $moneyParams[LedgerConstants::MERCHANT_RECEIVABLE_AMOUNT] = strval($tax + $fee);
        }
        // Normal transfer debit scenario (commissions considered)
        else
        {
            $moneyParams[LedgerConstants::MERCHANT_PAYABLE_AMOUNT]    = strval($amount);
            $moneyParams[LedgerConstants::MERCHANT_BALANCE_AMOUNT]    = strval($amount + $fee + $tax);
            $moneyParams[LedgerConstants::TAX]                        = strval($tax);
            $moneyParams[LedgerConstants::TRANSFER_COMMISSION]        = strval($fee);
        }

        $maxNegativeLimit = $this->getMaxNegativeLimitForTransfer($transfer);

        if ($maxNegativeLimit !== 0)
        {
            $moneyParams[LedgerConstants::MERCHANT_BALANCE_LIMIT] = strval($maxNegativeLimit);
        }

        return $moneyParams;
    }

    public function fetchRulesForTransferDebit(Transfer\Entity $transfer, $merchantAccountBalances, $fee, $tax) : array
    {
        $feeCredits = $merchantAccountBalances[LedgerConstants::MERCHANT_FEE_CREDITS];
        $amountCredits = $merchantAccountBalances[LedgerConstants::MERCHANT_AMOUNT_CREDITS];

        $rule = [];

        if ($amountCredits > 0)
        {
            $rule[LedgerConstants::CREDIT_ACCOUNTING] = LedgerConstants::AMOUNT_CREDITS_REDEMPTION;
        }
        else if($this->isFeeCredits($feeCredits, $fee) === true)
        {
            $rule[LedgerConstants::CREDIT_ACCOUNTING] = LedgerConstants::FEE_CREDITS;
        }
        else if($this->isTransferPostpaid($transfer))
        {
            $rule[LedgerConstants::CREDIT_ACCOUNTING] = LedgerConstants::POSTPAID;
        }

        return $rule;
    }

    public function createTransactionMessageForCreditJournal(Transfer\Entity $transfer, Payment\Entity $transferPayment): array
    {
        $moneyParams = $this->generateMoneyParamsForTransferCredit($transfer);

        $transactionMessage = $this->generateBaseForJournalEntry($transferPayment);

        $transactionMessage[LedgerConstants::MONEY_PARAMS]           = $moneyParams;
        $transactionMessage[LedgerConstants::ADDITIONAL_PARAMS]      = [ LedgerConstants::ENTRY_TYPE => LedgerConstants::ENTRY_TYPE_CREDIT ];

        return  $transactionMessage;
    }

    public function generateMoneyParamsForTransferCredit(Transfer\Entity $transfer): array
    {
        $amount = $transfer->getAmount();

        return [
            LedgerConstants::AMOUNT                     => strval($amount),
            LedgerConstants::BASE_AMOUNT                => strval($amount),
            LedgerConstants::MERCHANT_PAYABLE_AMOUNT    => strval($amount),
            LedgerConstants::MERCHANT_BALANCE_AMOUNT    => strval($amount),
        ];
    }

    public function saveOrderAndPaymentTransferReverseShadowLedgerEntriesToOutbox($transfer, $transferPayment)
    {
        $ledgerService = $this->app['ledger'];

        $merchantAccountBalances = $this->getMerchantAccountBalances($ledgerService, $transfer->getMerchantId());

        list($fee, $tax, $feesSplit) = (new Fee())->calculateMerchantFees($transfer);

        $transactionMessage = $this->createBulkTransactionMessageForTransfer($transfer, $transferPayment, $merchantAccountBalances, $fee, $tax);

        $transactorId = $transactionMessage[LedgerConstants::TRANSACTOR_ID];

        $transactorEvent = $transactionMessage[LedgerConstants::TRANSACTOR_EVENT];

        $payloadName = $this->getPayloadName($transactorId, $transactorEvent);

        $outboxPayload = $this->prepareOutboxPayload($payloadName, $transactionMessage);

        $this->saveToLedgerOutbox($outboxPayload, $transactorEvent);

        return [$fee, $tax];
    }

    public function createLedgerEntriesForTransferReverseShadowInSync($transfer, $transferPayment)
    {
        $ledgerService = $this->app['ledger'];

        $merchantAccountBalances = $this->getMerchantAccountBalances($ledgerService, $transfer->getMerchantId());

        list($fee, $tax, $feesSplit) = (new Fee())->calculateMerchantFees($transfer);

        $journalPayload = $this->createBulkTransactionMessageForTransfer($transfer, $transferPayment, $merchantAccountBalances, $fee, $tax);

        $journalResponse = $this->createJournalInLedger($journalPayload, true);

        [$creditJournalId, $debitJournalId] = $this->determineJournalIdForAPITransaction($journalResponse, "merchant_balance", "merchant_balance" );

        if((empty($creditJournalId)) or
            (empty($debitJournalId)))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_EXPECTED_FUND_ACCOUNT_TYPE_NOT_PRESENT,
                null,
                [
                    LedgerConstants::TRANSFER_ID      => $transfer->getId(),
                ]);
        }

        $this->pushTransferDataToKafkaForAPITransactionCreation($transfer, $transferPayment,$creditJournalId, $debitJournalId);

        return [$fee, $tax];
    }

    protected function isTransferPostpaid(Transfer\Entity $transfer): bool
    {
        return ($transfer->merchant->getFeeModel() === Merchant\FeeModel::POSTPAID);
    }

    public function isNegativeBalanceEnabledForTxnTypeAndMerchant(string $txnType, string $balanceType = Balance\Type::PRIMARY) : bool
    {
        if ((array_key_exists($balanceType, Balance\Core::NEGATIVE_FLOWS) === false) or
            (in_array($txnType, Balance\Core::NEGATIVE_FLOWS[$balanceType]) === false))
        {
            return false;
        }

        return true;
    }

    private function getMaxNegativeLimitForTransfer(Transfer\Entity $transfer): int
    {
        $balanceType = Balance\Type::PRIMARY;
        $txnType = Transaction\Type::TRANSFER;

        $isNegativeBalanceEnabled = $this->isNegativeBalanceEnabledForTxnTypeAndMerchant($txnType, $balanceType);

        if ($isNegativeBalanceEnabled === false)
        {
            return 0;
        }

        $balance = $transfer->merchant->getBalanceByTypeOrFail($balanceType);

        $negativeAllowedFlows = (new BalanceConfig\Core())->getNegativeFlowsForBalance($balance->getId());

        if (in_array($txnType, $negativeAllowedFlows) === false)
        {
            return 0;
        }

        $maxNegative = (new BalanceConfig\Core())->getMaxNegativeAmountManualForBalanceId($balance->getId());

        return $maxNegative;

    }


    private function pushTransferDataToKafkaForAPITransactionCreation($transfer, $transferPayment, $creditJournalId, $debitJournalId)
    {
        if (($this->app->runningUnitTests() === true))
        {
            return;
        }

        $producerKey =  $transfer->getId().'_'.$transferPayment->getId();

        $data = [
            'transfer_id' => $transfer->getId(),
            'payment_id' => $transferPayment->getId(),
            'transfer_journal_id' => $debitJournalId,
            'payment_journal_id' => $creditJournalId

        ];

        $message = [
            Constants::KAFKA_MESSAGE_DATA      => $data,
            Constants::KAFKA_MESSAGE_TASK_NAME  => Constants::CREATE_TRANSACTION_FOR_DIRECT_TRANSFER
        ];

        $topic = env('CREATE_REFUND_TXN_API', Constants::CREATE_REFUND_TXN_API);

        try
        {
            $kafkaProducer = (new KafkaProducer($topic, stringify($message), $producerKey));

            $kafkaProducer->Produce();

            $this->trace->info(TraceCode::KAFKA_TRANSFER_API_TXN_PUSH_SUCCESS, [
                Constants::PRODUCER_KEY => $producerKey,
                Constants::TOPIC        => $topic,
                Constants::MESSAGE      => $message
            ]);

            $this->trace->count(Metric::KAFKA_TRANSFER_API_TXN_PUSH_FAILURE, [
                Constants::TOPIC        => $topic,
            ]);

        }
        catch (\Exception $ex)
        {
            $this->trace->count(Metric::KAFKA_TRANSFER_API_TXN_PUSH_FAILURE, [
                Constants::TOPIC        => $topic,
            ]);

            $this->trace->traceException(
                $ex,
                500,
                TraceCode::KAFKA_TRANSFER_API_TXN_PUSH_FAILURE,
                [
                    Constants::PRODUCER_KEY => $producerKey,
                    Constants::TOPIC        => $topic,
                    Constants::MESSAGE      => $message
                ]);

            throw $ex;
        }
    }

    public function createTransactionMessageForCustomerWalletLoading(Transfer\Entity $transfer): array
    {
        $debitTransaction = $transfer->transaction;
        $merchant = $debitTransaction->merchant;

        $transactionMessage = [
            Constants::API_TRANSACTION_ID        => $debitTransaction->getId(),
            Constants::MERCHANT_ID               => $debitTransaction->getMerchantId(),
            Constants::CURRENCY                  => $merchant->getCurrency(),
            Constants::TRANSACTION_DATE          => $debitTransaction->getCreatedAt(),
            Constants::LEDGER_INTEGRATION_MODE   => Constants::REVERSE_SHADOW,
            Constants::IDEMPOTENCY_KEY           => Uuid::uuid1(),
            Constants::TENANT                    => Constants::TENANT_PG,
        ];

        $additionalParams = $this->fetchRulesForTransferCredits($debitTransaction);
        $additionalParams = (count($additionalParams) > 0) ? $additionalParams : null;

        $moneyParams = $this->generateMoneyParamsForCustomerWalletLoadingDebit($debitTransaction);

        $transferData = [
            Constants::TRANSACTOR_EVENT             => Constants::CUSTOMER_WALLET_LOADING,
            Constants::TRANSACTOR_ID                => $transfer->getPublicId(),
            Constants::MONEY_PARAMS                 => $moneyParams,
            Constants::ADDITIONAL_PARAMS            => $additionalParams
        ];

        return array_merge($transactionMessage, $transferData);
    }

    public function fetchRulesForTransferCredits(Transaction\Entity $transaction)
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

    public function generateMoneyParamsForCustomerWalletLoadingDebit(Transaction\Entity  $transaction): array
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

}
