<?php

namespace RZP\Models\Transfer;

use RZP\Jobs;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Error\Error;
use RZP\Models\Base;
use RZP\Trace\Tracer;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Reversal;
use RZP\Models\Transfer;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Base\ConnectionType;
use Razorpay\Trace\Logger as Trace;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Jobs\Transfers\TransferRecon;
use RZP\Constants\Entity as EntityConstant;
use RZP\Jobs\Transfers\TransferBackfillJob;
use RZP\Models\Settlement\Entity as Settlement;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }

    public function fetch(string $id, array $input): array
    {
        $transfer = Tracer::inSpan(['name' => 'transfer.fetch'], function() use ($id, $input)
        {
            return $this->repo
                        ->transfer
                        ->findByPublicIdAndMerchant($id, $this->merchant, $input);
        });

        $variant = $this->app['razorx']->getTreatment(
            $this->merchant->getId(),
            Merchant\RazorxTreatment::ROUTE_TRANSFER_STATE,
            $this->mode
        );

        $this->trace->info(
            TraceCode::ROUTE_TRANSFER_STATE_RAZORX_REQUEST,
            [
                'merchant_id'   => $this->merchant->getId(),
                'mode'          => $this->mode,
                'variant'       => $variant,
            ]
        );

        if (strtolower($variant) !== 'on')
        {
            if ($transfer->isCreated() or $transfer->isFailed())
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID);
            }
        }

        return $transfer->toArrayPublicWithExpand();
    }

    public function updateTransfersWithSettlementIdOldFlow($settlementIds)
    {
        $this->trace->info(
            TraceCode::TRANSFER_RECON_INITIATED,
            [
                'settlement_ids' => $settlementIds
            ]
        );

        foreach ($settlementIds as $settlementId)
        {
            $startTime = microtime(true);

            $settlement = $this->repo->settlement->findOrFail($settlementId);

            $merchant = $this->repo->merchant->findOrFail($settlement->getMerchantId());

            if ($merchant->isLinkedAccount() === false)
            {
                continue;
            }

            $transferIds = [];

            try
            {
                for ($skip = 0; true; $skip += Constant::CHUNK)
                {
                    $transactionIds = $this->repo->transaction->fetchLinkedAccountTransactionIdsBySettlementId($settlementId, $skip);

                    $transferIdsChunk = $this->updateSettlementIdInTransfer($settlementId, $transactionIds);

                    $transferIds = array_merge($transferIds, $transferIdsChunk);

                    if (count($transactionIds) < Constant::CHUNK)
                    {
                        break;
                    }
                }
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::CRITICAL,
                    TraceCode::TRANSFER_RECON_FAILURE,
                    [
                        'settlement_id' => $settlementId,
                    ]
                );
            }

            $endTime = microtime(true);

            $this->trace->info(
                TraceCode::TRANSFER_RECON_COMPLETE,
                [
                    'settlement_id'     => $settlementId,
                    'time_taken'        => $endTime - $startTime,
                    'transfers_ids'     => $transferIds,
                ]
            );

            $this->fireTransferSettledWebhookIfApplicable($transferIds, $settlement);
        }
    }

    protected function updateSettlementIdInTransfer($settlementId, $transactionIds)
    {
        $transferIds = [];

        try
        {
            foreach ($transactionIds as $transactionId)
            {
                $this->trace->info(
                    TraceCode::TRANSACTION_FETCHED_FOR_SETTLEMENT_ID,
                    [
                        'settlement_id'     => $settlementId,
                        'transaction_id'    => $transactionId,
                    ]
                );

                $transaction = $this->repo->transaction->findOrFail($transactionId);

                $transfer = $transaction->source->transfer;

                $transfer->setRecipientSettlementId($settlementId);

                $this->repo->saveOrFail($transfer);

                $transferIds[] = $transfer->getPublicId();
            }
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::CRITICAL,
                TraceCode::TRANSFER_UPDATE_SETTLEMENT_ID_FAILED,
                [
                    'settlement_id'     => $settlementId,
                    'transaction_ids'   => $transactionIds,
                ]);
        }

        return $transferIds;
    }

    public function fetchMultiple(array $input)
    {
        $this->trace->info(
            TraceCode::TRANSFER_FETCH_MULTIPLE_REQUEST,
            [
                'input' => $input,
            ]
        );

        $merchantId = $this->merchant->getId();

        $variant = $this->app['razorx']->getTreatment(
            $this->merchant->getId(),
            Merchant\RazorxTreatment::ROUTE_TRANSFER_STATE,
            $this->mode
        );

        $this->trace->info(
            TraceCode::ROUTE_TRANSFER_STATE_RAZORX_REQUEST,
            [
                'merchant_id'   => $this->merchant->getId(),
                'mode'          => $this->mode,
                'variant'       => $variant,
            ]
        );

        if (strtolower($variant) !== 'on')
        {
            $input[Entity::STATUS] = Constant::FETCH_STATUS;
        }

        $transfers = Tracer::inSpan(['name' => 'transfer.fetch_multiple'], function() use ($input, $merchantId)
        {
            return $this->repo
                        ->transfer
                        ->fetch($input, $merchantId);
        });

        $this->trace->info(
            TraceCode::TRANSFER_FETCH_MULTIPLE_RESPONSE,
            [
                'transfers' => $transfers->toArrayPublic(),
            ]
        );

        return $transfers->toArrayPublic();
    }

    public function fetchReversalsOfTransfer(string $id): array
    {
        $merchantId = $this->merchant->getId();

        $options = [
            Reversal\Entity::ENTITY_ID      => Entity::verifyIdAndStripSign($id),
            Reversal\Entity::ENTITY_TYPE    => EntityConstant::TRANSFER
        ];

        $reversals = $this->repo->reversal->fetch($options, $merchantId);

        return $reversals->toArrayPublic();
    }

    public function fetchLinkedAccountReversalsOfTransfer(string $transferId): array
    {
        (new Merchant\Validator)->validateLinkedAccount($this->merchant);

        $transferId = Entity::verifyIdAndStripSign($transferId);

        $merchantId = $this->merchant->getId();

        $reversals = $this->repo->reversal->fetchLaReversalsOfTransfer($transferId, $merchantId);

        return $reversals->toArrayPublic();
    }

    public function create(array $input): array
    {
        try
        {
            $transfer = $this->core->createForMerchant($input, $this->merchant);

            (new Metric)->pushCreateSuccessMetrics($input);

            return $transfer->toArrayPublic();
        }
        catch (\Exception $e)
        {
            (new Metric)->pushCreateFailedMetrics($e);

            throw $e;
        }
    }

    public function edit(string $id, array $input) : array
    {
        $this->trace->info(
            TraceCode::TRANSFER_EDIT_REQUEST,
            [
                'transfer_id' => $id,
                'input'       => $input,
            ]
        );

        $transfer =  $this->repo
                           ->transfer
                           ->findByPublicIdAndMerchant($id, $this->merchant);

        $transfer = $this->core->edit($transfer, $input);

        return $transfer->toArrayPublic();
    }

    public function reverse(string $id, array $input) : array
    {
        $this->trace->info(
            TraceCode::TRANSFER_REVERSAL_REQUEST,
            [
                'transfer_id' => $id,
                'input'       => $input
            ]
        );

        try
        {
            $transfer = $this->repo
                             ->transfer
                             ->findByPublicIdAndMerchant($id, $this->merchant);

            $reversal = (new Reversal\Core)->reverseForTransferAndCustomerRefund($transfer, $input, $this->merchant, $this->merchant);

            (new Metric)->pushReversalSuccessMetrics();

            return $reversal->toArrayPublic();
        }
        catch (\Exception $e)
        {
            (new Metric)->pushReversalFailedMetrics($e);

            throw $e;
        }
    }

    public function linkedAccountReversal(string $id, array $input): array
    {
        $this->trace->info(
            TraceCode::TRANSFER_REVERSAL_REQUEST_BY_LINKED_ACCOUNT,
            [
                'transfer_id' => $id,
                'input'       => $input
            ]
        );

        try
        {
            $transfer = $this->repo
                             ->transfer
                             ->fetchByPublicIdAndLinkedAccountMerchant($id, $this->merchant);

            $reversal = (new Reversal\Core)->linkedAccountReverseForTransfer($transfer, $input, $this->merchant);

            return $reversal->toArrayPublic();
        }
        catch (\Exception $e)
        {
            (new Metric)->pushReversalFailedMetrics($e);

            throw $e;
        }
    }

    public function fetchLinkedAccountTransfer(string $id): array
    {
        (new Merchant\Validator)->validateLinkedAccount($this->merchant);

        $merchantId = $this->merchant->getId();

        Transfer\Entity::verifyIdAndStripSign($id);

        $relations = ['transfer', 'transfer.recipientSettlement'];

        $payment = $this->repo->payment->findByTransferIdAndMerchant($id, $merchantId, $relations);

        return $this->createTransferResponseFromPayment($payment);
    }

    public function fetchLinkedAccountTransferByPaymentId(string $paymentId, array $input = []): array
    {

        if ($this->merchant->isDisplayParentPaymentId() === true)
        {
            (new Merchant\Validator)->validateLinkedAccount($this->merchant);

            Payment\Entity::verifyIdAndStripSign($paymentId);

            $transferId = null;

            if (isset($input["id"]))
            {
                $transferId = Transfer\Entity::verifyIdAndStripSign($input["id"]);

            }

            return $this->createTransferResponseFromParentPaymentAndTransfer($paymentId,$transferId);

        }

        $response = [];

        $transfers =  new Base\PublicCollection($response);

        return $transfers->toArrayWithItems();
    }


    public function fetchLinkedAccountTransfers(array $input): array
    {

        if (isset($input["parent_payment_id"]))
        {

            return $this->fetchLinkedAccountTransferByPaymentId($input["parent_payment_id"],$input);
        }
        else
        {
            (new Merchant\Validator)->validateLinkedAccount($this->merchant);

            $merchantId = $this->merchant->getId();

            $input['expand'] = ['transfer', 'transfer.recipient_settlement'];

            // fetching by payments fetch to handle notes search.
            $payments = $this->repo->payment->fetch($input, $merchantId);

            $transfers = $this->createResponse($payments);

            $transfers = new Base\PublicCollection($transfers);

            return $transfers->toArrayWithItems();
        }
    }

    private function createResponse($payments): array
    {
        $transfers = [];
        foreach ($payments as $payment) {
            $transferData = $this->createTransferResponseFromPayment($payment);

            $transfers[] = $transferData;
        }

        return $transfers;
    }

    /**
     * @param $payment
     * @param $parentPaymentId
     * @param $transId
     * @return array
     */
    private function createTransferResponseFromParentPaymentAndTransfer($parentPaymentId, $transId) : array
    {

        $transferData = [];

        $merchantId = $this->merchant->getId();

        if ($transId == null)
        {

            $paymentTransfers =  $this->repo->transfer->getTransfersByPayments($parentPaymentId, $merchantId);

            if ($paymentTransfers->count() == 0)
            {
                $payment = $this->repo->payment->findOrFailPublicWithRelations($parentPaymentId);

                $orderId = $payment->getApiOrderId();

                $paymentTransfers =  $this->repo->transfer->getTransfersByPayments($orderId, $merchantId);
            }

        }
        else
        {
            $paymentTransfers =  $this->repo->transfer->getTransfersByPaymentsAndTransId($parentPaymentId, $merchantId,$transId);

            if ($paymentTransfers->count() == 0)
            {
                $payment = $this->repo->payment->findOrFailPublicWithRelations($parentPaymentId);

                $orderId = $payment->getApiOrderId();

                $paymentTransfers =  $this->repo->transfer->getTransfersByPaymentsAndTransId($orderId, $merchantId,$transId);
            }

        }

        foreach ($paymentTransfers as  $trans)
        {

            $transferPublic = $trans->toArrayPublic();

            if ($this->merchant->isDisplayParentPaymentId())
            {

                $transferPublic[Transfer\Entity::PARENT_PAYMENT_ID] = Payment\Entity::getSignedId($parentPaymentId);
            }

            $transferData [] = $transferPublic;
        }

        $transfers = new Base\PublicCollection($transferData);

        return $transfers->toArrayWithItems();

    }


    /**
     * @param $payment
     * @return array
     */
    private function createTransferResponseFromPayment($payment): array
    {
        $result = $payment->toArrayPublic();

        $merchant = $this->merchant;

        $transferData = $result[Payment\Entity::TRANSFER];

        $transferData[Transfer\Entity::NOTES] = $result[Payment\Entity::NOTES];


        if ($merchant->isDisplayParentPaymentId() === true)
        {

            $transferData[Transfer\Entity::PARENT_PAYMENT_ID] = $payment->transfer->parentpaymentId;
        }

        return $transferData;
    }

    public function processPendingOrderTransfers(array $input)
    {
        $orderIds = $this->repo->transfer->fetchPendingTransfersToRetry(EntityConstant::ORDER, $input['limit'] ?? 300);

        $this->trace->info(
            TraceCode::PENDING_ORDER_TRANSFER_PROCESS,
            [
                'order_ids' => $orderIds,
            ]
        );

        return $this->processOrderTransfers($orderIds);
    }

    public function processPendingPaymentTransfers(array $input)
    {
        $paymentIds = $this->repo->transfer->fetchPendingTransfersToRetry(EntityConstant::PAYMENT, $input['limit'] ?? 300);

        $this->trace->info(
            TraceCode::PENDING_ORDER_TRANSFER_PROCESS_CRON,
            [
                'payement_ids' => $paymentIds,
            ]
        );

        return $this->processPaymentTransfers($paymentIds);
    }

    protected function processPaymentTransfers(array $paymentIds)
    {
        $payments = [];

        foreach ($paymentIds as $paymentId)
        {
            try
            {
                $this->trace->info(
                    TraceCode::PAYMENT_TRANSFER_PROCESS_SQS_PUSH_INIT,
                    [
                        'payment_id' => $paymentId,
                        'mode'       => $this->mode,
                    ]
                );

                $payment = $this->repo->payment->find($paymentId);

                $this->core->dispatchForTransferProcessing(Constant::PAYMENT, $payment);

                array_push($payments, $paymentId);

            }
            catch (\Throwable $e)
            {
                $this->trace->critical(
                    TraceCode::PAYMENT_TRANSFER_PROCESS_SQS_PUSH_FAILED,
                    [
                        'payment_id' => $paymentId,
                        'message'    => $e->getMessage(),
                    ]
                );
            }
        }
        return $payments;
    }

    public function processTransfersSettelements(string $settelementId)
    {
        $this->trace->info(
            TraceCode::TRANSFERS_SETTELEMENT_UPDATE,
            [
                '$settelementId' => $settelementId,
            ]
        );

        $transfers =  $this->repo->transfer->updatetransfersWithSettelement($settelementId);

        $toalcount = $transfers->count();

        $this->trace->info(
            TraceCode::TRANSFERS_SETTELEMENT_UPDATE,
            [
                'transfers'   => $transfers,
                '$toalcount'  => $toalcount,
            ]
        );

        return $toalcount;
    }

    public function processFailedOrderTransfers(array $input)
    {
        $orderIds = $this->repo->transfer->fetchFailedTransfersToRetry(EntityConstant::ORDER, $input['limit'] ?? 300);

        $this->trace->info(
            TraceCode::FAILED_ORDER_TRANSFER_PROCESS,
            [
                'order_ids' => $orderIds,
            ]
        );

        return $this->processOrderTransfers($orderIds);
    }

    protected function processOrderTransfers(array $orderIds)
    {
        $transferOrderIds = [];

        foreach ($orderIds as $orderId)
        {
            $this->trace->info(
                TraceCode::ORDER_TRANSFER_PROCESS_RETRY,
                [
                    'order_id' => $orderId,
                ]
            );

            $order = $this->repo->order->find($orderId);

            if ($order === null) {
                continue;
            }

            $payment = $this->repo->payment->getCapturedPaymentForOrder($order->getId());

            if ($payment === null) {
                $this->core->fetchTransfersAndIncrementAttempts($order);

                continue;
            }

            if ((new PaymentProcessor($payment->merchant))->shouldProcessOrderTransfer($payment) === false) {
                $this->core->fetchTransfersAndIncrementAttempts($order);

                continue;
            }

            try
            {
                $this->trace->info(
                    TraceCode::ORDER_TRANSFER_PROCESS_SQS_PUSH_INIT,
                    [
                        'order_id'   => $payment->getApiOrderId(),
                        'payment_id' => $payment->getId(),
                        'mode'       => $this->mode,
                    ]
                );

                $this->core->dispatchForTransferProcessing(Constant::ORDER, $payment);

                array_push($transferOrderIds, $orderId);
            }
            catch (\Throwable $e)
            {
                $this->trace->critical(
                    TraceCode::ORDER_TRANSFER_PROCESS_SQS_PUSH_FAILED,
                    [
                        'order_id'   => $payment->getApiOrderId(),
                        'payment_id' => $payment->getId(),
                        'message'    => $e->getMessage(),
                    ]
                );
            }
        }

        $this->trace->info(
            TraceCode::ORDER_TRANSFER_PROCESS_RETRY_DONE,
            [
                               'processed_order_ids' => $transferOrderIds
            ]
        );

        return $transferOrderIds;
    }

    public function updateTransfersWithSettlementId($transactionIds)
    {
        $this->trace->info(
            TraceCode::TRANSFER_RECON_INITIATED,
            [
                'transaction_ids' => $transactionIds,
            ]
        );

        $startTime = microtime(true);

        $transferIds = [];

        try
        {
            foreach ($transactionIds as $transactionId)
            {
                $transaction = $this->repo->transaction->findOrFail($transactionId);

                $this->trace->info(
                    TraceCode::TRANSACTION_FETCHED_FOR_TRANSFER_RECON,
                    [
                        'transaction_id' => $transactionId,
                    ]
                );

                if ($transaction->source->getEntityName() !== EntityConstant::PAYMENT)
                {
                    continue;
                }

                $payment = $transaction->source;

                if ($payment->transfer === null)
                {
                    continue;
                }

                $transfer = $payment->transfer;

                $this->trace->info(
                    TraceCode::TRANSFER_FETCHED_FOR_RECON,
                    [
                        'transfer_id' => $transfer->getId(),
                    ]
                );

                $settlementId = $transaction->getSettlementId();

                $transfer->setRecipientSettlementId($settlementId);

                $this->repo->saveOrFail($transfer);

                $this->trace->info(
                    TraceCode::TRANSFER_RECIPIENT_SETTLEMENT_ID_UPDATED,
                    [
                        'transfer_id'               => $transfer->getId(),
                        'recipient_settlement_id'   => $transfer->getRecipientSettlementId(),
                    ]
                );

                $transferIds[$settlementId] = array_merge(
                    $transferIds[$settlementId] ?? [],
                    array($transfer->getId())
                );
            }
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::CRITICAL,
                TraceCode::TRANSFER_RECON_FAILURE,
                [
                    'settlement_ids'    => array_keys($transferIds),
                    'transfer_ids'      => $transferIds,
                ]
            );
        }

        $endTime = microtime(true);

        $this->trace->info(
            TraceCode::TRANSFER_RECON_COMPLETE,
            [
                'settlement_ids'    => array_keys($transferIds),
                'transfers_ids'     => $transferIds,
                'time_taken'        => $endTime - $startTime,
            ]
        );
    }

    public function triggerTransferSettledWebhook(string $settlementId)
    {
        $this->trace->info(
            TraceCode::TRANSFER_SETTLED_WEBHOOK_REQUEST,
            [
                'settlement_id' => $settlementId,
            ]
        );

        $transferIds = $this->repo->transfer->getIdsByRecipientSettlementId($settlementId);

        Entity::getSignedIdMultiple($transferIds);

        $settlement = $this->repo->settlement->findOrFail($settlementId);

        $this->fireTransferSettledWebhookIfApplicable($transferIds, $settlement);
    }

    /**
     * Requirement was to fire a webhook to the parent merchant when transfer
     * settlements to the linked account: FlaHVYQCGKbK2t are processed. This
     * solution is applicable only when the linked account settlements happen
     * via the new settlements service, in which case this flow is triggered
     * after settlements are processed.
     *
     * @param array $transferIds
     * @param Settlement $settlement
     */
    protected function fireTransferSettledWebhookIfApplicable(array $transferIds, Settlement $settlement)
    {
        if ($settlement->isStatusProcessed() === false)
        {
            return;
        }

        $linkedAccountId = $settlement->getMerchantId();

        if (in_array($linkedAccountId, Merchant\Preferences::TRANSFER_SETTLED_WEBHOOK_MIDS) === false)
        {
            return;
        }

        foreach ($transferIds as $transferId)
        {
            $transfer = $this->repo->transfer->findByPublicId($transferId);

            if ($transfer->getToType() === ToType::CUSTOMER)
            {
                continue;
            }

            $this->trace->info(
                TraceCode::FIRING_TRANSFER_SETTLED_WEBHOOK,
                [
                    'linked_account_id' => $linkedAccountId,
                    'settlement_id'     => $settlement->getPublicId(),
                    'transfer_id'       => $transferId,
                ]
            );

            $this->fireTransferSettledWebhook($transfer, $settlement);
        }
    }

    protected function fireTransferSettledWebhook(Entity $transfer, Settlement $settlement)
    {
        $eventPayload = [
            ApiEventSubscriber::MAIN => $transfer,
            ApiEventSubscriber::WITH => $settlement,
        ];

        $this->app['events']->dispatch('api.transfer.settled', $eventPayload);
    }

    public function transferRecon(array $input)
    {
        $hours = 24;

        if (isset($input['hours']) === true)
        {
            $hours = $input['hours'];
        }

        $linkedAccountSettlementIds = $this->core->getLinkedAccountSettlementIds($hours);

        if (empty($linkedAccountSettlementIds) === true)
        {
            return 0;
        }

        $settlementIdsToQueue = [];

        foreach ($linkedAccountSettlementIds as $recipientSettlementId)
        {
            if ($this->core->isReconDoneForSettlementId($recipientSettlementId) === false)
            {
                $settlementIdsToQueue[] = $recipientSettlementId;
            }
        }

        if (empty($settlementIdsToQueue) === true)
        {
            $this->trace->info(
                TraceCode::LINKED_ACCOUNT_SETTLEMENTS_ALREADY_RECONCILED,
                [
                    'settlement_ids' => $linkedAccountSettlementIds,
                ]
            );

            return 0;
        }

        try
        {
            TransferRecon::dispatch($settlementIdsToQueue, $this->mode);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::CRITICAL,
                TraceCode::LINKED_ACCOUNT_SETTLEMENTS_PUSH_TO_QUEUE_FAILED,
                [
                    'settlement_ids' => $settlementIdsToQueue,
                ]
            );
        }

        $this->trace->info(
            TraceCode::LINKED_ACCOUNT_SETTLEMENTS_PUSHED_TO_QUEUE,
            [
                'settlement_ids' => $settlementIdsToQueue,
            ]
        );

        return count($settlementIdsToQueue);
    }

    public function createReversalFromBatch(string $transferId, array $input)
    {
        $this->trace->info(
            TraceCode::TRANSFER_REVERSAL_REQUEST_VIA_BATCH,
            [
                'transfer_id'   => $transferId,
                'input'         => $input,
            ]
        );

        $this->core()->parseAttributesForTransferReversalBatch($input);

        try
        {
            $reversal = $this->reverse($transferId, $input);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::TRANSFER_REVERSAL_VIA_BATCH_FAILED,
                [
                    'transfer_id'   => $transferId,
                    'input'         => $input,
                ]
            );

            (new Transfer\Metric())->pushReversalFailedMetrics($ex);

            throw $ex;
        }

        (new Transfer\Metric())->pushReversalSuccessMetrics();

        $this->trace->info(
            TraceCode::TRANSFER_REVERSAL_VIA_BATCH_SUCCESSFUL,
            [
                'transfer_id'   => $transferId,
                'reversal_id'   => $reversal[Reversal\Entity::ID],
            ]
        );

        return $reversal;
    }

    public function pushTransactionIdsIntoQueue(array $input)
    {
        $settlementIds = $input['settlement_ids'];

        $allTransactionIds = [];

        foreach ($settlementIds as $settlementId)
        {
            $allTransactionIds[$settlementId] = $this->repo->transaction->fetchTransactionIdsForSettlementId($settlementId);

            $this->trace->info(
                TraceCode::TRANSACTION_IDS_FETCHED_FOR_SETTLEMENT_ID,
                [
                    'settlement_id'         => $settlementId,
                    'transactions_count'    => count($allTransactionIds[$settlementId]),
                ]
            );
        }

        foreach ($allTransactionIds as $settlementId => $transactionIds)
        {
            $this->trace->info(
                TraceCode::TRANSACTION_IDS_DISPATCH_REQUEST,
                [
                    'settlement_id'         => $settlementId,
                    'transactions_count'    => count($transactionIds),
                ]
            );

            $this->dispatchTransactionIdsInChunks($settlementId, $transactionIds, $input['chunk']);
        }
    }

    protected function dispatchTransactionIdsInChunks(string $settlementId, array $transactionIds, $chunk = 1000)
    {
        $transactionIdChunks = array_chunk($transactionIds, $chunk);

        $count = 0;

        foreach ($transactionIdChunks as $transactionIdChunk)
        {
            $input['transaction_ids'] = $transactionIdChunk;

            try
            {
                TransferRecon::dispatch($input, $this->mode);

                $count += count($transactionIdChunk);

                $this->trace->info(
                    TraceCode::TRANSACTION_IDS_DISPATCHED_FOR_TRANSFER_RECON,
                    [
                        'settlement_id'     => $settlementId,
                        'transaction_ids'   => $transactionIdChunk,
                    ]
                );
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::INFO,
                    TraceCode::TRANSACTION_IDS_DISPATCH_FOR_TRANSFER_RECON_FAILED,
                    [
                        'settlement_id'     => $settlementId,
                        'transactions_ids'  => $transactionIdChunk,
                    ]
                );
            }
        }

        $this->trace->info(
            TraceCode::TRANSACTION_IDS_DISPATCH_SUMMARY,
            [
                'settlement_id'         => $settlementId,
                'dispatched_txns_count' => $count,
            ]
        );
    }

    public function dispatchBackfillJob(array $input)
    {
        $midJun     = Carbon::createFromDate(2021, 6, 16, Timezone::IST)->getTimestamp();
        $startJul   = Carbon::createFromDate(2021, 7, 1, Timezone::IST)->getTimestamp();
        $startAug   = Carbon::createFromDate(2021, 8, 1, Timezone::IST)->getTimestamp();
        $startSept  = Carbon::createFromDate(2021, 9, 1, Timezone::IST)->getTimestamp();
        $startOct   = Carbon::createFromDate(2021, 10, 1, Timezone::IST)->getTimestamp();
        // Don't need post this since code is already live from mid September.

        foreach ($input['merchant_ids'] as $merchantId)
        {
            TransferBackfillJob::dispatch($this->mode, $merchantId, $midJun, $startJul);
            TransferBackfillJob::dispatch($this->mode, $merchantId, $startJul, $startAug);
            TransferBackfillJob::dispatch($this->mode, $merchantId, $startAug, $startSept);
            TransferBackfillJob::dispatch($this->mode, $merchantId, $startSept, $startOct);

            $this->trace->info(
                TraceCode::TRANSFER_BACKFILL_JOB_ENQUEUED,
                [
                    'merchant_id' => $merchantId,
                ]
            );
        }
    }

    public function updateSettlementStatusAndErrorCode(string $merchantId, int $startDate, int $endDate)
    {
        $totalCount = 0;
        $chunk = 1000;

        for ($skip = 0; true; $skip = $skip + $chunk)
        {
            $transferIds = $this->repo->transfer->getByMerchantId($merchantId, $startDate, $endDate, $skip, $chunk);

            $count = count($transferIds);

            $totalCount += $count;

            foreach ($transferIds as $transferId)
            {
                $transfer = $this->repo->transfer->find($transferId);

                if ($transfer->isDirectTransfer() === true)
                {
                    continue;
                }

                $this->updateSettlementStatus($transfer);

                $this->updateErrorCodeIfApplicable($transfer);

                $this->repo->saveOrFail($transfer);
            }

            $this->trace->info(
                TraceCode::TRANSFER_BACKFILL_DONE_FOR_CHUNK,
                [
                    'merchant_id'   => $merchantId,
                    'chunk_count'   => $count,
                    'transfer_ids'  => $transferIds,
                ]
            );

            if ($count < $chunk)
            {
                break;
            }
        }

        return $totalCount;
    }

    protected function updateSettlementStatus(Entity $transfer)
    {
        if ($transfer->getSettlementStatus() !== null)
        {
            return;
        }

        $recipientSettlementId = $transfer->getRecipientSettlementId();

        if ($recipientSettlementId === null)
        {
            if (($transfer->getOnHold() === true) and
                (in_array($transfer->getStatus(), [Status::PROCESSED, Status::REVERSED, Status::PARTIALLY_REVERSED]) === true))
            {
                $transfer->setSettlementStatus(SettlementStatus::ON_HOLD);

                return;
            }
            else if (($transfer->getOnHold() === false) and
                    (in_array($transfer->getStatus(), [Status::PROCESSED, Status::REVERSED, Status::PARTIALLY_REVERSED]) === true))
            {
                $transfer->setSettlementStatus(SettlementStatus::PENDING);

                return;
            }
            else
            {
                return;
            }
        }

        $settlement = $this->repo->settlement->find($recipientSettlementId);

        if ($settlement->isStatusProcessed() === true)
        {
            if (in_array($transfer->getStatus(), [Status::PROCESSED, Status::PARTIALLY_REVERSED]) === true)
            {
                $transfer->setSettlementStatus(SettlementStatus::SETTLED);

                return;
            }
            else if ($transfer->getStatus() === Status::REVERSED)
            {
                // If transfer was reversed before getting settled then `pending`, else `settled`.
//                $transferSettlementId = $this->repo->transaction->getSettlementIdForTransfer($transfer->getId(), $transfer->getToId()); // Same as $recipientSettlementId.

                $reversalId = $this->repo->reversal->getLatestReversalIdForTransfer($transfer->getId(), $transfer->getMerchantId());

                $reversalSettlementId = $this->repo->transaction->getSettlementIdForReversal($reversalId, $transfer->getToId());

                if ($recipientSettlementId === $reversalSettlementId)
                {
                    $transfer->setSettlementStatus(SettlementStatus::PENDING);
                }
                else
                {
                    $transfer->setSettlementStatus(SettlementStatus::SETTLED);
                }

                return;
            }
            else
            {
                return;
            }
        }
    }

    protected function updateErrorCodeIfApplicable(Entity $transfer)
    {
        if ($transfer->getErrorCode() !== null)
        {
            return;
        }

        if ($transfer->isFailed() === false)
        {
            return;
        }

        $message = $transfer->getMessage();

        if ($message !== null)
        {
            $transfer->setErrorCode(ErrorCodeMapping::getErrorCodeFromDescription($message));
        }
    }
}
