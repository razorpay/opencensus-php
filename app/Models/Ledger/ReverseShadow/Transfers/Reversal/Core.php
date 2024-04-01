<?php

namespace RZP\Models\Ledger\ReverseShadow\Transfers\Reversal;

use App;
use Neves\Events\TransactionalClosureEvent;
use Razorpay\Trace\Logger as Trace;
use RZP\Jobs\Transfers\TransferReversalCreateTransaction;
use RZP\Models\Base;
use RZP\Models\Merchant\Balance;
use RZP\Models\Transaction;
use RZP\Trace\TraceCode;
use RZP\Constants\Metric;
use RZP\Models\Payment\Refund\Constants as RefundConstants;
use RZP\Models\Payment\Refund\Entity as RefundEntity;
use RZP\Models\Reversal\Entity as ReversalEntity;
use RZP\Services\KafkaProducer;
use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Models\LedgerOutbox\Core as LedgerOutboxCore;
use RZP\Models\Ledger\ReverseShadow\ReverseShadowTrait;
use RZP\Models\Ledger\ReverseShadow\Refunds\Core as RefundReverseShadowCore;
use function Clue\StreamFilter\append;

class Core extends Base\Core
{
    protected $merchant;

    use ReverseShadowTrait;

    public function __construct()
    {
        parent::__construct();

        $this->merchant = $this->app['basicauth']->getMerchant();
    }

    public function createTransactionMessageForReversalDebit(RefundEntity $refund): array
    {
        $moneyParams = self::generateMoneyParamsForReversalDebit($refund);

        $transactionMessage = $this->generateBaseForJournalEntry($refund, $refund->getCreatedAt());

        $transactionMessage[LedgerConstants::MONEY_PARAMS] = $moneyParams;

        $additionalParams = [
            LedgerConstants::ENTRY_TYPE => LedgerConstants::ENTRY_TYPE_DEBIT
        ];

        if($this->isRefundCredits($refund->merchant))
        {
            $additionalParams[LedgerConstants::CREDIT_ACCOUNTING] = LedgerConstants::REFUND_CREDITS;
        }

        $transactionMessage[LedgerConstants::ADDITIONAL_PARAMS] = $additionalParams;

        return $transactionMessage;
    }

    public function createTransactionMessageForReversalCredit(ReversalEntity $reversal): array
    {
        $moneyParams = self::generateMoneyParamsForReversalCredit($reversal);

        $transactionMessage = $this->generateBaseForJournalEntry($reversal);

        $transactionMessage[LedgerConstants::MONEY_PARAMS] = $moneyParams;

        $transactionMessage[LedgerConstants::ADDITIONAL_PARAMS] = [ LedgerConstants::ENTRY_TYPE => LedgerConstants::ENTRY_TYPE_CREDIT ];

        return $transactionMessage;
    }

    public function generateMoneyParamsForReversalCredit(ReversalEntity $reversal): array
    {
        $moneyParams = [];

        $amount = $reversal->getAmount();

        $moneyParams[LedgerConstants::AMOUNT]                     = strval($amount);
        $moneyParams[LedgerConstants::BASE_AMOUNT]                = strval($amount);
        $moneyParams[LedgerConstants::MERCHANT_PAYABLE_AMOUNT]    = strval($amount);
        $moneyParams[LedgerConstants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);

        return $moneyParams;
    }

    public function generateMoneyParamsForReversalDebit(RefundEntity $refund): array
    {
        $moneyParams = [];

        $amount = $refund->getBaseAmount();

        $moneyParams[LedgerConstants::AMOUNT]                     = strval($amount);
        $moneyParams[LedgerConstants::BASE_AMOUNT]                = strval($amount);
        $moneyParams[LedgerConstants::MERCHANT_PAYABLE_AMOUNT]    = strval($amount);

        if($this->isRefundCredits($refund->merchant))
        {
            $moneyParams[LedgerConstants::REFUND_CREDITS]         = strval($amount);
        }
        else
        {
            $moneyParams[LedgerConstants::MERCHANT_BALANCE_AMOUNT]    = strval($amount);
        }

        $txnType = Transaction\Type::REFUND;

        $balance = $refund->merchant->getBalanceByTypeOrFail(RefundConstants::PRIMARY);

        $balanceConfigCore = new Balance\BalanceConfig\Core();

        $negativeAllowedFlows = $balanceConfigCore->getNegativeFlowsForBalance($balance->getId());

        $negativeLimit = (new Balance\BalanceConfig\Core)->getMaxNegativeAmountManualForBalanceId($balance->getId());

        if (in_array($txnType, $negativeAllowedFlows) === false)
        {
            $negativeLimit = 0;
        }

        $moneyParams[LedgerConstants::MERCHANT_BALANCE_LIMIT] = strval($negativeLimit);

        return $moneyParams;
    }

    public function createBulkTransactionMessageForTransferReversal($reversal, $refund, $sourceRefund, $sourcePayment, $isCustomerRefundApplicable, $isRearchRefund = false)
    {
        $transferReversalAtomicPayload = $this->createBulkTransactionMessageForReversalAndRefundEntity($reversal, $refund);

        if ($isCustomerRefundApplicable === true)
        {
            $sourceRefundJournalPayload = (new RefundReverseShadowCore())->createRefundJournalPayload($sourceRefund, $sourcePayment);

            array_push($transferReversalAtomicPayload, $sourceRefundJournalPayload);
        }

        $journalPayload = [
            LedgerConstants::JOURNALS => $transferReversalAtomicPayload
        ];

        $journals = $this->createJournalInLedger($journalPayload, false, true);

        list($reversalAndRefundJournalIds, $producerKey) = $this->createPayloadForAPITransactionCreation([[$reversal,$refund]],$sourceRefund, $journals, $isCustomerRefundApplicable, $isRearchRefund);

        \Event::dispatch(new TransactionalClosureEvent(function () use ($reversalAndRefundJournalIds, $producerKey) {

            $this->dispatchForTransferReversalTransactionCreation($reversalAndRefundJournalIds);
        }));

        return $reversalAndRefundJournalIds;

    }

    public function createBulkTransactionMessageForReversalAndRefundEntity(ReversalEntity $reversal, RefundEntity $refund)
    {
        $reversalCreditJournal = $this->createTransactionMessageForReversalCredit($reversal);

        $reversalDebitJournal = $this->createTransactionMessageForReversalDebit($refund);

        $transactionMessage = [
            LedgerConstants::TRANSACTOR_EVENT             => LedgerConstants::TRANSFER_REVERSAL_PROCESSED,
            LedgerConstants::TRANSACTOR_ID                => $reversal->getPublicId(),
            LedgerConstants::TRANSACTION_DATE             => $reversal->getUpdatedAt(),
            LedgerConstants::CURRENCY                     => $reversal->merchant->getCurrency() ?? "INR",
        ];

        $reversalJournalPayload = array_merge($transactionMessage, $reversalCreditJournal);

        $refundJournalPayload = array_merge($transactionMessage, $reversalDebitJournal);

        return [$refundJournalPayload, $reversalJournalPayload];
    }

    public function createReverseShadowLedgerEntriesForTransferReversalBulk(array $atomicJournalPayload, RefundEntity $customerRefund, array $results, $isRearchRefund = false)
    {

        $sourcePayment = $customerRefund->payment;
        $sourceRefundJournalPayload = (new RefundReverseShadowCore())->createRefundJournalPayload($customerRefund, $sourcePayment);

        $atomicJournalPayload[] = $sourceRefundJournalPayload;

        $journalPayload = [
            LedgerConstants::JOURNALS   => $atomicJournalPayload
        ];

        $journals = $this->createJournalInLedger($journalPayload, false, true);

        list($reversalAndRefundJournalIds, $producerKey) = $this->createPayloadForAPITransactionCreation($results, $customerRefund, $journals, true, $isRearchRefund);

        \Event::dispatch(new TransactionalClosureEvent(function () use ($reversalAndRefundJournalIds) {

            $this->dispatchForTransferReversalTransactionCreation($reversalAndRefundJournalIds);

        }));

        return $reversalAndRefundJournalIds;
    }

    private function createPayloadForAPITransactionCreation( $results, RefundEntity $sourceRefund, $journals, $isCustomerRefundApplicable, $isRearchRefund = false)
    {

        $segregatedJournalsByReversalId = [];
        $sourceRefundJournal = [];

        foreach ($journals as $journal)
        {
            $transactorEvent = $journal['transactor_event'];

            if ($transactorEvent === 'refund_processed')
            {
                $sourceRefundJournal = $journal;
            }

            if($transactorEvent === 'transfer_reversal_processed')
            {
                $transactorId = $journal['transactor_id'];

                if (!isset($segregatedJournalsByReversalId[$transactorId]))
                {
                    $segregatedJournalsByReversalId[$transactorId] = [];
                }

                $segregatedJournalsByReversalId[$transactorId][] = $journal;
            }
        }

        $refundsByReversalId = [];

        foreach ($results as $result) {
            [$reversal, $refund] = $result;

            $reversalId = $reversal->getPublicId();

            $refundsByReversalId[$reversalId] = ['reversal' => $reversal, 'refund' => $refund];
        }

        $reversalTransactionPayload = [];
        $producerKey = "";

        foreach ($segregatedJournalsByReversalId as $reversalId => $transferReversalJournals)
        {
            $reversal = $refundsByReversalId[$reversalId]['reversal'];

            $refund = $refundsByReversalId[$reversalId]['refund'];

            [$creditJournalId, $debitJournalId] = $this->determineJournalIdForAPITransaction($transferReversalJournals, "merchant_balance", "merchant_balance");

            $reversalTransactionPayload[] = [
                "transfer_reversal_id" => $reversal->getId(),
                "transfer_reversal_journal_id" => $creditJournalId,
                "refund_id" => $refund->getId(),
                "refund_journal_id" => $debitJournalId,
            ];

            $producerKey = $reversal->getId();
        }

        $data = [
            "reversals"       => $reversalTransactionPayload,
            "is_rearch_refund" => $isRearchRefund
        ];

        if ($isCustomerRefundApplicable === true)
        {
            $data["customer_refund_id"] = $sourceRefund->getId();
            $data["customer_refund_journal_id"] = $sourceRefundJournal['id'];
            $producerKey =  $sourceRefund->getId();
        }

        return [$data, $producerKey];
    }

    public function pushReversalToKafkaForAPITransactionCreation($reversalAndRefundJournalIds, $producerKey)
    {
        if (($this->app->runningUnitTests() === true))
        {
            return;
        }

        $message = [
            LedgerConstants::KAFKA_MESSAGE_DATA => $reversalAndRefundJournalIds,
            LedgerConstants::KAFKA_MESSAGE_TASK_NAME  => LedgerConstants::CREATE_TRANSACTION_FOR_TRANSFER_REVERSAL
        ];

        $topic = env('CREATE_REFUND_TXN_API', LedgerConstants::CREATE_REFUND_TXN_API);

        try {
            $kafkaProducer = (new KafkaProducer($topic, stringify($message), $producerKey));

            $kafkaProducer->Produce();

            $this->trace->info(TraceCode::KAFKA_REVERSAL_API_TXN_PUSH_SUCCESS, [
                LedgerConstants::PRODUCER_KEY => $producerKey,
                LedgerConstants::TOPIC => $topic,
                LedgerConstants::MESSAGE => $message
            ]);

            $this->trace->count(Metric::KAFKA_REVERSAL_API_TXN_PUSH_SUCCESS, [
                LedgerConstants::TOPIC => $topic,
            ]);

        } catch (\Exception $ex) {
            $this->trace->count(Metric::KAFKA_REVERSAL_API_TXN_PUSH_FAILURE, [
                LedgerConstants::TOPIC => $topic,
            ]);

            $this->trace->traceException(
                $ex,
                500,
                TraceCode::KAFKA_REVERSAL_API_TXN_PUSH_FAILED,
                [
                    LedgerConstants::PRODUCER_KEY => $producerKey,
                    LedgerConstants::TOPIC => $topic,
                    LedgerConstants::MESSAGE => $message
                ]);

        }

    }

    public function dispatchForTransferReversalTransactionCreation(array $reversalAndRefundJournalIds = [])
    {
        $delaySecs = 900;

        try
        {
            TransferReversalCreateTransaction::dispatch($this->mode, $reversalAndRefundJournalIds, $delaySecs)->delay($delaySecs);

            $this->trace->info(TraceCode::TRANSFER_REVERSAL_API_TXN_DISPATCH_SUCCESS, [
                'reversalAndRefundJournalIds' =>  $reversalAndRefundJournalIds,
                'mode' =>  $this->mode,
            ]);

        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::CRITICAL,
                TraceCode::TRANSFER_REVERSAL_API_TXN_DISPATCH_FAILURE,
                [
                    'message'           => 'transfer reversal api txn job push failed',
                    'reversalAndRefundJournalIds' => $reversalAndRefundJournalIds,
                    'mode' =>  $this->mode,
                ]);

            $this->trace->count(Metric::TRANSFER_REVERSAL_API_TXN_DISPATCH_TO_QUEUE_FAILURE);
        }
    }
}
