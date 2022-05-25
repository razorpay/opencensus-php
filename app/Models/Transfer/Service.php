<?php

namespace RZP\Models\Transfer;

use RZP\Models\Base;
use RZP\Trace\Tracer;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Reversal;
use RZP\Models\Transfer;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Exception\BadRequestException;
use RZP\Constants\Entity as EntityConstant;
use RZP\Models\Settlement\Entity as Settlement;
use RZP\Jobs\Transfers\TransferSettlementStatus;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;
use RZP\Jobs\Transfers\LinkedAccountBankVerificationStatusBackfill;

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

        return $transfer->toArrayPublicWithExpand();
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

    /**
     * @param array $input
     * @return int
     */
    public function updateSettlementStatusInTransfer(array $input):int
    {
        $limit = $input['limit'] ?? 1000;

        $settlementIds = $this->repo->settlement->fetchSettlementIdsWithIncorrectStatusOnTransfers($limit);

        $this->trace->info(TraceCode::SETTLEMENT_IDS_FOR_STATUS_UPDATE_ON_TRANSFERS,
        [
            'limit'          => $limit,
            'count'          => count($settlementIds),
            'settlement_ids' => $settlementIds,
        ]);

        foreach ($settlementIds as $settlementId)
        {
            TransferSettlementStatus::dispatch($this->mode, $settlementId);
        }
        return count($settlementIds);
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

            $order = null;

            try
            {
                $order = $this->repo->order->findOrFail($orderId);
            }
            catch (\Throwable $e)
            {
                $this->trace->info(
                    TraceCode::ORDER_NOT_FOUND,
                    [
                        'error' => $e->getMessage()
                    ]);
            }

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

    /**
     * Dispatches async jobs to backfill settlement_status and error_code data in transfers table.
     * This function is currently disconnected from the controller. If there is a need to use it
     * again in the future, point a controller to it and hit from production environment.
     * Note that hitting from the dark environment won't work since SQSs are not configured on dark.
     *
     * @param array $input
     */
    public function dispatchBackfillJob(array $input)
    {
        $type = $input['type'] ?? '';

        $merchantIds = array();

        if ($type === 'parent_mids')
        {
            foreach ($input['merchant_ids'] as $id)
            {
                $merchantIds = array_merge($merchantIds, $this->repo->merchant->fetchActivatedLinkedAccountIdsForParentMerchant($id));
            }
        }
        elseif ($type === 'linked_account_mids')
        {
            $merchantIds = $input['merchant_ids'];
        }
        else
        {
            $merchantIds = $this->repo->merchant->fetchAllActiveLinkedAccounts();
        }

        $size = (count($merchantIds) % 2 === 0) ? count($merchantIds)/4 : count($merchantIds)/4+1; // divides array into 4 parts

        $merchantIdChunks = array_chunk($merchantIds, $size);

        foreach ($merchantIdChunks as $chunk)
        {
            LinkedAccountBankVerificationStatusBackfill::dispatch($this->mode, $chunk);
        }

        $this->trace->info(TraceCode::LA_BANK_VERIFICATION_STATUS_UPDATE_JOB_ENQUEUED,
            [
                'count'        => count($merchantIds)
            ]);
    }

    public function getTransferInput(string $transferId)
    {
        $transfer = $this->repo->transfer->findByPublicId($transferId);

        if ($transfer->isFailed() === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ONLY_FAILED_TRANSFER_EXPECTED,
                null,
                [
                    'transfer_id' => $transferId,
                ]
            );
        }

        if ($transfer->getSourceType() !== Constant::PAYMENT)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ONLY_PAYMENT_TRANSFER_EXPECTED,
                null,
                [
                    'transfer_id' => $transferId,
                ]
            );
        }

        return $this->core->getTransferInput($transfer);
    }
}
