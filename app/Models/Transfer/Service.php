<?php

namespace RZP\Models\Transfer;

use RZP\Jobs;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Reversal;
use RZP\Models\Transfer;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Base\ConnectionType;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Jobs\Transfers\TransferRecon;
use RZP\Constants\Entity as EntityConstant;
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
        $transfer =  $this->repo
                          ->transfer
                          ->findByPublicIdAndMerchant($id, $this->merchant, $input);

        if ($transfer->isCreated() or $transfer->isFailed())
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID);
        }

        return $transfer->toArrayPublicWithExpand();
    }

    public function UpdateTransfersWithSettlementId($settlementIds)
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

        $input[Entity::STATUS] = Constant::FETCH_STATUS;

        $transfers = $this->repo->transfer->fetch($input, $merchantId, ConnectionType::DATA_WAREHOUSE);

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

                Jobs\TransferProcess::dispatch($this->mode, $paymentId, Transfer\Constant::PAYMENT);

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

                Jobs\TransferProcess::dispatch($this->mode, $payment->getId());

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
}
