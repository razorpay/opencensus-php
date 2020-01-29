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
use RZP\Constants\Entity as EntityConstant;
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

        return $transfer->toArrayPublic();
    }

    public function fetchMultiple(array $input)
    {
        $merchantId = $this->merchant->getId();

        $input[Entity::STATUS] = Constant::FETCH_STATUS;

        $transfers = $this->repo->transfer->fetch($input, $merchantId);

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

        if (self::checkIsRazorxFlagEnabled() and $this->merchant->isDisplayParentPaymentId())
        {
            (new Merchant\Validator)->validateLinkedAccount($this->merchant);

            Payment\Entity::verifyIdAndStripSign($paymentId);

            if (isset($input["transfer_id"]))
            {
                $transferId = Transfer\Entity::verifyIdAndStripSign($input["transfer_id"]);

                return $this->fetchLinkedAccountWithTransferAndPaymentId($paymentId, $transferId);

            }
            else
            {
                $payment = $this->repo->payment->findOrFailPublicWithRelations($paymentId);

                return $this->createTransferResponseFromParentPaymentAndTransfer($payment, $paymentId);
            }
        }

        $response = [];

        $transfers =  new Base\PublicCollection($response);

        return $transfers->toArrayWithItems();
    }

    /**
     * Fetching the linkedaccounts with paymentId and transferId
     *
     * @param  string $id
     * @param  string $trans
     * @return array
     */
    public function fetchLinkedAccountWithTransferAndPaymentId(string $id,string $trans): array
    {

        $merchantId = $this->merchant->getParentId();

        $payment = $this->repo->payment->findOrFailPublicWithRelations($id);

        return $this->createTransferResponseFromParentPaymentAndTransfer($payment, $id, $trans);
    }

    public function fetchLinkedAccountTransfers(array $input): array
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
    private function createTransferResponseFromParentPaymentAndTransfer($payment, $parentPaymentId, $transId = null) : array
    {
        $orderId = $payment->getApiOrderId();

        $transferData = [];

        $merchantId = $this->merchant->getId();

        if ($orderId == null)
        {
            $sourceId = $payment->getId();

            $source = EntityConstant::PAYMENT;

        } else
        {

            $sourceId = $orderId;

            $source = EntityConstant::ORDER;
        }

        if ($transId == null)
        {

            $paymentTransfers =  $this->repo->transfer->getTransfersByPayments($source, $sourceId, $merchantId);

        } else
        {
            $paymentTransfers = $this->repo->transfer->getTransfersByPaymentsAndTransId($source, $sourceId, $merchantId, $transId);

        }
        foreach ($paymentTransfers as  $trans)
        {

            $transferPublic = $trans->toArrayPublic();

            if ($this->merchant->isDisplayParentPaymentId())
            {

                $transferPublic[Transfer\Entity::PARENT_PAYMENT_ID] = $parentPaymentId;
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


        if (self::checkIsRazorxFlagEnabled() and  $merchant->isDisplayParentPaymentId())
        {

            $transferData[Transfer\Entity::PARENT_PAYMENT_ID] = $payment->transfer->parentpaymentId;
        }

        return $transferData;
    }

    private function  checkIsRazorxFlagEnabled() : bool
    {
        $app = $this->app;

        $variant = $app['razorx']->getTreatment($this->merchant->getId(),
            Merchant\RazorxTreatment::DISPLAY_PARENT_PAYMENT_ID,
            $app['basicauth']->getMode()
        );

        return strtolower($variant) === 'on';
    }

    public function processPendingOrderTransfers()
    {
        $orderIds = $this->repo->transfer->fetchPendingTransfersToRetry(EntityConstant::ORDER);

        $this->trace->info(
            TraceCode::PENDING_ORDER_TRANSFER_PROCESS,
            [
                'order_ids' => $orderIds,
            ]
        );

        return $this->processOrderTransfers($orderIds);
    }

    public function processFailedOrderTransfers()
    {
        $orderIds = $this->repo->transfer->fetchFailedTransfersToRetry(EntityConstant::ORDER);

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
                continue;
            }

            if ((new PaymentProcessor($payment->merchant))->shouldProcessOrderTransfer($payment) === false) {
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

                Jobs\OrderTransferProcess::dispatch($this->mode, $payment);

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
}
