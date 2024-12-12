<?php

namespace RZP\Models\Ledger\ReverseShadow\Transfers\Reversal;

use App;
use Carbon\Carbon;
use Neves\Events\TransactionalClosureEvent;
use Razorpay\Trace\Logger as Trace;
use RZP\Jobs\Transfers\TransferReversalCreateTransaction;
use RZP\Models\Base;
use RZP\Models\Merchant\Balance;
use RZP\Models\Merchant;
use RZP\Models\Feature;
use RZP\Models\Transaction;
use RZP\Models\Payment\Refund as Refund;
use RZP\Models\Transaction\ReconciledType;
use RZP\Trace\TraceCode;
use RZP\Constants\Metric;
use RZP\Models\Payment\Refund\Constants as RefundConstants;
use RZP\Models\Payment\Refund\Entity as RefundEntity;
use RZP\Models\Reversal\Entity as ReversalEntity;
use RZP\Models\Settlement\Bucket;
use RZP\Services\KafkaProducer;
use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Models\LedgerOutbox\Core as LedgerOutboxCore;
use RZP\Models\Ledger\ReverseShadow\ReverseShadowTrait;
use RZP\Models\Ledger\ReverseShadow\Refunds\Core as RefundReverseShadowCore;
use RZP\Models\Ledger\ReverseShadow\Transfers as ReverseShadowTransfer;
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

    public function getReversalDebitJournalPayloadBalanceSplitzResponse($merchantId)
    {
        try
        {
            $properties = [
                'id'            => $merchantId,
                'experiment_id' => $this->app['config']->get('app.reversal_debit_journal_payload_harvester_balance_experiment'),
            ];
            $response = $this->app['splitzService']->evaluateRequest($properties);

            return $response['response']['variant']['name'] ?? '';
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::SPLITZ_ERROR, [
                'merchant_id'   => $merchantId,
            ]);

            return '';
        }

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

        $tiDBBalanceFetch = $this->getReversalDebitJournalPayloadBalanceSplitzResponse($refund->merchant->getId()) === 'enable';

        if ($tiDBBalanceFetch === true)
        {
            $balance = (new Balance\Repository())->getMerchantBalanceByTypeTiDBOrFail($refund->merchant->getId(), RefundConstants::PRIMARY);
        }
        else
        {
            $balance = $refund->merchant->getBalanceByTypeOrFail(RefundConstants::PRIMARY);
        }


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
        // Called from parent/LA reversal flow
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

        if ($this->isMerchantEnabledForTxnDualWrite($reversal->merchant))
        {
            $this->setTxnIdsInSourceEntities($reversalAndRefundJournalIds);

            $this->pushTransferReversalDataToKafkaForAPIDualWrite($reversalAndRefundJournalIds, $journals);
        }
        else
        {
            \Event::dispatch(new TransactionalClosureEvent(function () use ($reversalAndRefundJournalIds, $producerKey) {

                $this->dispatchForTransferReversalTransactionCreation($reversalAndRefundJournalIds);
            }));
        }

        $this->dispatchToSettlementFromJournalIfApplicable($reversalAndRefundJournalIds, $journals);

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
            LedgerConstants::NOTES                        => [
                LedgerConstants::REFUND_ID  => $refund->getId(),
            ]
        ];

        $reversalJournalPayload = array_merge($transactionMessage, $reversalCreditJournal);

        $refundJournalPayload = array_merge($transactionMessage, $reversalDebitJournal);

        return [$refundJournalPayload, $reversalJournalPayload];
    }

    public function createReverseShadowLedgerEntriesForTransferReversalBulk(array $atomicJournalPayload, RefundEntity $customerRefund, array $results, $isRearchRefund = false)
    {
        // called from scrooge reverse transfers
        $sourcePayment = $customerRefund->payment;
        $sourceRefundJournalPayload = (new RefundReverseShadowCore())->createRefundJournalPayload($customerRefund, $sourcePayment);

        $atomicJournalPayload[] = $sourceRefundJournalPayload;

        $journalPayload = [
            LedgerConstants::JOURNALS   => $atomicJournalPayload
        ];


        $journals = $this->createJournalInLedger($journalPayload, false, true);

        list($reversalAndRefundJournalIds, $producerKey) = $this->createPayloadForAPITransactionCreation($results, $customerRefund, $journals, true, $isRearchRefund);

        if ($this->isMerchantEnabledForTxnDualWrite($sourcePayment->merchant))
        {
            $this->pushTransferReversalDataToKafkaForAPIDualWrite($reversalAndRefundJournalIds, $journals);
        }
        else
        {
            \Event::dispatch(new TransactionalClosureEvent(function () use ($reversalAndRefundJournalIds) {

                $this->dispatchForTransferReversalTransactionCreation($reversalAndRefundJournalIds);

            }));
        }

        $this->dispatchToSettlementFromJournalIfApplicable($reversalAndRefundJournalIds, $journals);

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

    private function dispatchToSettlementFromJournalIfApplicable($reversalAndRefundJournalIds, $journals)
    {
        try
        {
            $isRearchRefund = $reversalAndRefundJournalIds["is_rearch_refund"];

            $reversals = $reversalAndRefundJournalIds["reversals"];

            $customerRefundId =  $reversalAndRefundJournalIds["customer_refund_id"] ?? "";

            if($customerRefundId !== "")
            {
                if($isRearchRefund)
                {
                    $customerRefund = (new Refund\Repository())->fetchExternalRefundById($customerRefundId, '', [], true);
                }
                else
                {
                    $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
                        'method'       => 'createTransferReversalTransactions',
                    ]);
                    $customerRefund = $this->repo->refund->findOrFail($customerRefundId);
                }

                $customerRefundJournalId = $reversalAndRefundJournalIds["customer_refund_journal_id"];

                $filteredCustomerRefundJournal = array_filter($journals, function ($item) use ($customerRefundJournalId)
                {
                    return $item['id'] === $customerRefundJournalId;
                });

                $customerRefundJournal = reset($filteredCustomerRefundJournal);

                if (empty($customerRefundJournal) === false)
                {
                    $this->dispatchToSettlementFromJournalForRefund($customerRefundJournal, $customerRefundId);
                }
            }


            foreach ($reversals as $item)
            {

                $reversalId             = $item["transfer_reversal_id"];
                $reversalJournalId      = $item["transfer_reversal_journal_id"];
                $dummyRefundId          = $item["refund_id"];
                $dummyRefundJournalId   = $item["refund_journal_id"];

                $reversal = $this->repo->reversal->findOrFail($reversalId);

                $filteredReversalJournal = array_filter($journals, function ($item) use ($reversalJournalId)
                {
                    return $item['id'] === $reversalJournalId;
                });

                $reversalJournal = reset($filteredReversalJournal);

                if (empty($reversalJournal) === false)
                {
                    $this->dispatchToSettlementFromJournalForReversal($reversalJournal);
                }

                if($isRearchRefund)
                {
                    $dummyRefund = (new Refund\Repository())->fetchExternalRefundById($dummyRefundId, '', [], true);
                }
                else
                {
                    $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
                        'method'       => 'createTransferReversalTransactions',
                    ]);
                    $dummyRefund = $this->repo->refund->findOrFail($dummyRefundId);
                }

                $filteredRefundJournal = array_filter($journals, function ($item) use ($dummyRefundJournalId)
                {
                    return $item['id'] === $dummyRefundJournalId;
                });

                $dummyRefundJournal = reset($filteredRefundJournal);

                if (empty($dummyRefundJournal) === false)
                {
                    $this->dispatchToSettlementFromJournalForRefund($dummyRefundJournal, $dummyRefundId);
                }
            }
        }
        catch (\Throwable $ex)
        {

            $this->trace->traceException(
                $ex,
                Trace::CRITICAL,
                TraceCode::TRANSFER_REVERSAL_TXN_EARLY_DISPATCH_FAILURE,
                [
                    'message'                       => 'transfer reversal txn early dispatch failed',
                    'reversalJournal'               => $journals,
                    'reversalAndRefundJournalIds'   => $reversalAndRefundJournalIds,
                    'mode'                          =>  $this->mode,
                ]);
        }
    }

    private function dispatchToSettlementFromJournalForRefund($refundJournal, $refundId)
    {
        // early dispatch refund transaction
        $virtualDummyRefundTransaction = $this->transformJournalResponseToTransactionEntityForRefund($refundJournal, $refundId);

        $bucketCore = new Bucket\Core;

        $status = $bucketCore->shouldProcessViaNewService($virtualDummyRefundTransaction->getMerchantId());

        if ($status === true)
        {
            $bucketCore->publishForSettlement($virtualDummyRefundTransaction);
        }

    }

    private function dispatchToSettlementFromJournalForReversal($reversalJournal)
    {
        // early dispatch reversal transaction
        $virtualReversalTransaction = $this->transformJournalResponseToTransactionEntityForReversal($reversalJournal);

        $bucketCore = new Bucket\Core;

        $status = $bucketCore->shouldProcessViaNewService($virtualReversalTransaction->getMerchantId());

        if ($status === true)
        {
            $bucketCore->publishForSettlement($virtualReversalTransaction);
        }

    }

    private function isMerchantEnabledForTxnDualWrite($merchant)
    {
        $properties = [
            'request_data' => json_encode(["merchant_id" => $merchant->getId()]),
            'id'            => $merchant->getId(),
            'experiment_id' => $this->app['config']->get('app.api_ledger_dual_write_rearch'),
        ];

        return (new Merchant\Core())->isSplitzExperimentEnable($properties, 'enable');
    }

    private function pushTransferReversalDataToKafkaForAPIDualWrite($reversalAndRefundJournalIds, $journals)
    {
        if (($this->app->runningUnitTests() === true))
        {
            return;
        }

        $producerKey =  '';  // check if needed

        $data = [
            'payload_api'=>[
                'id' => md5(json_encode($reversalAndRefundJournalIds)),
                'reversal_and_refund_journal_ids' => $reversalAndRefundJournalIds,
                'journals' => $journals
            ]
        ];

        $message = [
            LedgerConstants::KAFKA_MESSAGE_DATA       => $data,
            LedgerConstants::KAFKA_MESSAGE_TASK_NAME  => LedgerConstants::DUAL_WRITE_TRANSACTION_FOR_API_EVENTS
        ];

        $topic = env('DUAL_WRITE_TRANSACTION_FOR_API_EVENTS', LedgerConstants::DUAL_WRITE_TRANSACTION_FOR_API_EVENTS);

        try
        {
            $kafkaProducer = (new KafkaProducer($topic, stringify($message)));

            $kafkaProducer->Produce();

            $this->trace->info(TraceCode::KAFKA_TRANSFER_REVERSAL_API_TXN_PUSH_SUCCESS, [
                LedgerConstants::PRODUCER_KEY => $producerKey,
                LedgerConstants::TOPIC        => $topic,
                LedgerConstants::MESSAGE      => $message
            ]);

            $this->trace->count(Metric::KAFKA_TRANSFER_REVERSAL_API_TXN_PUSH_SUCCESS, [
                LedgerConstants::TOPIC        => $topic,
            ]);

        }
        catch (\Exception $ex)
        {
            $this->trace->count(Metric::KAFKA_TRANSFER_REVERSAL_API_TXN_PUSH_FAILURE, [
                LedgerConstants::TOPIC        => $topic,
            ]);

            $this->trace->traceException(
                $ex,
                500,
                TraceCode::KAFKA_TRANSFER_REVERSAL_API_TXN_PUSH_FAILURE,
                [
                    LedgerConstants::PRODUCER_KEY => $producerKey,
                    LedgerConstants::TOPIC        => $topic,
                    LedgerConstants::MESSAGE      => $message
                ]);

            throw $ex;
        }
    }

    private function setTxnIdsInSourceEntities($reversalAndRefundJournalIds)
    {
        $reversals = $reversalAndRefundJournalIds['reversals'];

        $isRearchRefund = $reversalAndRefundJournalIds["is_rearch_refund"];

        foreach ($reversals as $item)
        {
            $reversalId             = $item["transfer_reversal_id"];
            $reversalJournalId      = $item["transfer_reversal_journal_id"];
            $dummyRefundId          = $item["refund_id"];
            $dummyRefundJournalId   = $item["refund_journal_id"];

            $reversal = $this->repo->reversal->findOrFail($reversalId);

            $reversal->setTransactionId($reversalJournalId);

            $this->repo->saveOrFail($reversal);

            if($isRearchRefund)
            {
                $dummyRefund = (new Refund\Repository())->fetchExternalRefundById($dummyRefundId, '', [], true);
            }
            else
            {
                $dummyRefund = $this->repo->refund->findOrFail($dummyRefundId);
            }

            $dummyRefund->setTransactionId($dummyRefundJournalId);

            $this->repo->refund->saveOrFail($dummyRefund);
        }

        $isRearchRefund = $reversalAndRefundJournalIds["is_rearch_refund"];

        $customerRefundId =  $input["customer_refund_id"] ?? "";

        if($customerRefundId !== "")
        {
            if($isRearchRefund)
            {
                $customerRefund = (new Refund\Repository())->fetchExternalRefundById($customerRefundId, '', [], true);
            }
            else
            {
                $customerRefund = $this->repo->refund->findOrFail($customerRefundId);
            }

            $customerRefundJournalId = $input["customer_refund_journal_id"];

            $customerRefund->setTransactionId($customerRefundJournalId);

            $this->repo->refund->saveOrFail($customerRefund);
        }
    }
}
