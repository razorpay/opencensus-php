<?php

namespace RZP\Models\Ledger\ReverseShadow\Transfers;

use App;
use Carbon\Carbon;
use RZP\Constants\Metric;
use RZP\Constants\Timezone;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Jobs\AsyncBalanceUpdateForTransfer;
use RZP\Models\Base;
use Ramsey\Uuid\Uuid;
use RZP\Models\Ledger\Constants;
use RZP\Models\Feature;
use RZP\Models\Currency;
use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Transaction\ReconciledType;
use RZP\Models\Transfer;
use RZP\Models\Pricing\Fee;
use RZP\Models\Transaction;
use RZP\Models\Merchant\Balance;
use RZP\Models\Merchant\Balance\BalanceConfig;
use RZP\Models\Ledger\ReverseShadow\ReverseShadowTrait;
use RZP\Services\KafkaProducer;
use RZP\Trace\TraceCode;
use function PHPUnit\Framework\assertEquals;

class Core extends Base\Core
{

    protected $merchant;

    use ReverseShadowTrait;

    public function __construct()
    {
        parent::__construct();

        $this->merchant = $this->app['basicauth']->getMerchant();
    }

    public function createBulkTransactionMessageForTransfer($transfer, $transferPaymentMerchant, $merchantAccountBalances, $fee, $tax): array
    {
        $transferDebitJournal = $this->createTransactionMessageForDebitJournal($transfer, $merchantAccountBalances, $fee, $tax);

        $transferCreditJournal = $this->createTransactionMessageForCreditJournal($transfer, $transferPaymentMerchant);

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

    public function createTransactionMessageForCreditJournal(Transfer\Entity $transfer, Merchant\Entity $transferPaymentMerchant): array
    {
        $moneyParams = $this->generateMoneyParamsForTransferCredit($transfer);

        $transactionMessage = [
            Constants::MERCHANT_ID               => $transferPaymentMerchant->getId(),
            Constants::CURRENCY                  => $transferPaymentMerchant->getCurrency(),
            Constants::TRANSACTION_DATE          => $transfer->getUpdatedAt(),
        ];

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

    public function saveOrderAndPaymentTransferReverseShadowLedgerEntriesToOutbox($transfer, $transferPaymentMerchant)
    {
        $ledgerService = $this->app['ledger'];

        $merchantAccountBalances = $this->getMerchantAccountBalances($ledgerService, $transfer->getMerchantId());

        list($fee, $tax, $feesSplit) = (new Fee())->calculateMerchantFees($transfer);

        $transactionMessage = $this->createBulkTransactionMessageForTransfer($transfer, $transferPaymentMerchant, $merchantAccountBalances, $fee, $tax);

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

        $journalPayload = $this->createBulkTransactionMessageForTransfer($transfer, $transferPayment->merchant, $merchantAccountBalances, $fee, $tax);

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

    public function createTransferTxnAndTransferPaymentTxnAndPushForSettlement($transfer, $debitJournal, $creditJournal)
    {
        $transferPayment = $this->repo->payment->findByTransferIdAndMerchant($transfer->getId(), $transfer->getToId());

        $transferPayment = $this->repo->payment->findOrFail($transferPayment->getId());

        $transferMerchant = $transfer->merchant;

        $paymentMerchant = $transferPayment->merchant;

        if (($transferMerchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === false)
            or ($paymentMerchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === false))
        {
            return;
        }

        $transferTxn = $this->createTransferTransactionFromLedgerJournal($debitJournal, $transfer);

        $transferPaymentTxn = $this->createTransferPaymentTransactionFromLedgerJournal($creditJournal, $transferPayment);

        $txnCore = (new Transaction\Core());

        $txnCore->dispatchForSettlementBucketing($transferTxn);

        $txnCore->dispatchForSettlementBucketing($transferPaymentTxn);

        AsyncBalanceUpdateForTransfer::dispatch($this->mode, $transfer->getId())->delay(10 * 60);

        $this->trace->info(
            TraceCode::ASYNC_BALANCE_UPDATE_TXN_DISPATCHED,
            [
                'transfer_id'         => $transfer->getId(),
                'merchant_id'         => $transfer->getMerchantId(),
            ]);
        
        $this->trace->info(TraceCode::TRANSFER_REVERSE_SHADOW_TXN_CREATION_SUCCESS,
            [
                'transfer_id'               => $transfer->getId(),
                'transfer_txn_id'           => $transferTxn->getId(),
                'transfer_payment_txn_id'   => $transferPaymentTxn->getId(),
            ]);
    }

    public function createTransferTransactionFromLedgerJournal($journal, $transfer)
    {
        $txn = $this->transformJournalResponseToTransactionEntityBase($journal);

        $merchant = $transfer->merchant;

        $txn->sourceAssociate($transfer);

        $txn->merchant()->associate($merchant);

        if ($txn->isGratis() === true && $txn->getCreditType() === Transaction\CreditType::AMOUNT)
        {
            $pricingRuleId = (new Fee)->getZeroPricingPlanRule($transfer)->getId();

            $txn->setPricingRule($pricingRuleId);
        }
        else
        {
            $amount = $txn->getAmount();

            $fee = $txn->getFee();

            $isPrepaid = $merchant->isPrepaid();

            // Add fee to debit only for prepaid merchants
            $debit  = ($isPrepaid === true) ? abs($amount + $fee) : $amount;

            $txn->setDebit($debit);
        }

        $settledAt = Carbon::now(Timezone::IST)->getTimestamp();

        if ($transfer->getSourceType() === Transfer\Constant::PAYMENT)
        {
            $paymentTxn = $transfer->source->transaction;

            // Setting current timestamp to transfer settled_at when $paymentTxn->getSettledAt() is null to support async_txn_fill_details feature
            // Slack ref - https://razorpay.slack.com/archives/CNXC0JHQF/p1649241605237939?thread_ts=1648804095.677009&cid=CNXC0JHQF
            if ($paymentTxn->isSettled() === false && $paymentTxn->getSettledAt() !== null)
            {
                $settledAt = $paymentTxn->getSettledAt();
            }
        }

        $values = [
            Transaction\Entity::GATEWAY_FEE     => 0,
            Transaction\Entity::RECONCILED_AT   => time(),
            Transaction\Entity::RECONCILED_TYPE => ReconciledType::NA,
            Transaction\Entity::SETTLED         => 0,
            Transaction\Entity::SETTLED_AT      => $settledAt,
            Transaction\Entity::CHANNEL         => $transfer->merchant->getChannel(),
        ];

        $txn->fill($values);

        $txn->setBalanceUpdated(false);

        $this->repo->saveOrFail($txn);

        $this->repo->saveOrFail($transfer);

        $this->trace->info(TraceCode::TRANSFER_TXN_CREATED_IN_REVERSE_SHADOW,
            [
                'txn_id'                => $txn->getId(),
                'transfer_id'           => $transfer->getId(),
                'transfer_source_id'    => $transfer->getSourceId(),
                'transfer_source_type'  => $transfer->getSourceType(),
            ]);

        return $txn;
    }

    public function createTransferPaymentTransactionFromLedgerJournal($journal, $transferPayment)
    {
        $txn = $this->transformJournalResponseToTransactionEntityBase($journal);

        if ($transferPayment->hasTransaction() === true)
        {
            $txn = $this->repo->transaction->fetchByEntityAndAssociateMerchant($transferPayment);
        }
        else
        {
            $txn->sourceAssociate($transferPayment);

            $txn->merchant()->associate($transferPayment->merchant);
        }

        $txnData = [
            Transaction\Entity::CHANNEL         => $transferPayment->merchant->getChannel(),
        ];

        if ($transferPayment->getGateway() === Payment\Gateway::WALLET_OPENWALLET)
        {
            $txnData[Transaction\Entity::RECONCILED_AT]     = time();
            $txnData[Transaction\Entity::RECONCILED_TYPE]   = ReconciledType::NA;
        }

        $txn->fill($txnData);

        $settledAt = (new Transaction\Core())->getSettledAtTimestamp($transferPayment);

        $onHold = $transferPayment->getOnHold() ?? false;

        $txn->setReconciledAt(time());

        $txn->setReconciledType(ReconciledType::NA);

        $txn->setAttribute(Transaction\Entity::SETTLED_AT, $settledAt);

        $txn->setAttribute(Transaction\Entity::ON_HOLD, $onHold);

        $txn->setBalanceUpdated(false);

        $this->repo->saveOrFail($txn);

        $this->repo->saveOrFail($transferPayment);

        $this->trace->info(TraceCode::TRANSFER_TXN_CREATED_IN_REVERSE_SHADOW,
            [
                'txn_id'                => $txn->getId(),
                'payment_id'            => $transferPayment->getId(),
                'transfer_id'           => $transferPayment->getTransferId(),
            ]);

        return $txn;
    }
}
