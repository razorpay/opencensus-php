<?php

namespace RZP\Models\Transfer;

use RZP\Models\Base;
use RZP\Trace\Tracer;
use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Models\Reversal;
use RZP\Models\Transfer;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Base\ConnectionType;
use RZP\Jobs\TransferProcess;
use RZP\Exception\LogicException;
use Razorpay\Trace\Logger as Trace;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Exception\BadRequestException;
use RZP\Constants\Entity as EntityConstant;
use RZP\Exception\SettlementIdUpdateException;
use RZP\Models\Settlement\Entity as Settlement;
use RZP\Jobs\Transfers\TransferSettlementStatus;
use RZP\Models\Merchant\Constants as MerchantConstant;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Merchant\AccessMap\Core as AccessMapCore;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;
use RZP\Jobs\Transfers\LinkedAccountBankVerificationStatusBackfill;
use Throwable;

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
        $transferTypeFilter = $this->getTransferTypeFilter($input);

        $transfer = Tracer::inSpan(['name' => 'transfer.fetch'], function() use ($id, $input)
        {
            return $this->repo
                        ->transfer
                        ->findByPublicIdAndMerchant($id, $this->merchant, $input);
        });

        $transfer = $transfer->toArrayPublicWithExpand();

        if($transferTypeFilter === Constant::PLATFORM )
        {
            $transfer = $this->setPartnerDetailsForTransfer($transfer);
        }

        return $transfer;
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

        $transferTypeFilter = $this->getTransferTypeFilter($input);

        $transfers = Tracer::inSpan(['name' => 'transfer.fetch_multiple'], function() use ($transferTypeFilter, $input, $merchantId)
        {
            try
            {
                if ($transferTypeFilter === Constant::PLATFORM )
                {
                    $linkedAccountIds = $this->repo->merchant->fetchActivatedLinkedAccountIdsForParentMerchant($this->merchant->getId());

                    $input[Constant::EXCLUDED_LINKED_ACCOUNTS] = $linkedAccountIds;
                }
                else
                {
                    $result = (new Merchant\Service())->isFeatureEnabledForPartnerOfSubmerchant(Feature\Constants::ROUTE_PARTNERSHIPS, $this->merchant->getId());

                    if( $result[Constant::FEATURE_ENABLED] === true)
                    {
                        $linkedAccountIds = $this->repo->merchant->fetchActivatedLinkedAccountIdsForParentMerchant($this->merchant->getId());

                        $input[Constant::INCLUDED_LINKED_ACCOUNTS] = $linkedAccountIds;
                    }
                }
            }
            catch (Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::TRANSFER_FILTER_SET_FAILED,
                    [
                        'merchant_id'       => $this->merchant->getId(),
                        'filter_type'       => $transferTypeFilter,
                        'filters'           => $input,
                    ]
                );
            }

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

    /**
     * Checks if we need to log the route + partnership guard logs
     * @param string $merchantId
     *
     * @return bool
     */
    protected function shouldLogRoutePartnershipV1Guard(string $merchantId): bool
    {
        if ($this->auth->isPartnerAuth() === true)
        {
            return false;
        }

        $isExpEnabled = (new Merchant\Core)->isSplitzExperimentEnable(
            [
                'id'            => $merchantId,
                'experiment_id' => $this->app['config']->get('app.route_partnership_v1_guards_exp_id'),
            ],
            'enable'
        );

        return (
            ($isExpEnabled === true) and
            ((new AccessMapCore)->isSubMerchant($merchantId) === true)
        );
    }

    public function create(array $input): array
    {
        try
        {
            $transfer = $this->core->createForMerchant($input, $this->merchant);

            $merchantId = $this->merchant->getId();

            if ($this->shouldLogRoutePartnershipV1Guard($merchantId) === true)
            {
                $partners = (new Merchant\Core)->fetchAffiliatedPartners($merchantId);

                $this->trace->info(
                    TraceCode::SUBMERCHANT_CREATED_DIRECT_TRANSFER,
                    [
                        'transfer_id'   => $transfer->getId(),
                        'merchant_id'   => $merchantId,
                        'input'         => $input,
                        'partner_ids'   => $partners->getIds()
                    ]
                );
            }

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

        $merchantId = $this->merchant->getId();

        if ($this->shouldLogRoutePartnershipV1Guard($merchantId) === true)
        {
            if ((bool)$input[Entity::ON_HOLD] === false)
            {
                $partners = (new Merchant\Core)->fetchAffiliatedPartners($merchantId);

                $this->trace->info(
                    TraceCode::SUBMERCHANT_INITIATED_SETTLE_NOW_ON_TRANSFER,
                    [
                        'transfer_id'   => $transfer->getId(),
                        'merchant_id'   => $merchantId,
                        'input'         => $input,
                        'partner_ids'   => $partners->getIds()
                    ]
                );
            }
        }

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
            if ($this->repo->payment->isExperimentEnabledForId(\RZP\Base\Repository::PAYMENT_QUERIES_TIDB_MIGRATION, 'fetchLinkedAccountTransfers') === true)
            {
                $payments = $this->repo->payment->fetch($input, $merchantId, ConnectionType::DATA_WAREHOUSE_MERCHANT);
            }
            else
            {
                $payments = $this->repo->payment->fetch($input, $merchantId);
            }

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
                $payment = $this->repo->payment->findOrFailPublic($parentPaymentId);

                $orderId = $payment->getApiOrderId();

                $paymentTransfers =  $this->repo->transfer->getTransfersByPayments($orderId, $merchantId);
            }

        }
        else
        {
            $paymentTransfers =  $this->repo->transfer->getTransfersByPaymentsAndTransId($parentPaymentId, $merchantId,$transId);

            if ($paymentTransfers->count() == 0)
            {
                $payment = $this->repo->payment->findOrFailPublic($parentPaymentId);

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
        $syncProcessing = (bool) ($input['sync'] ?? false);

        $limit = (int) ($input['limit'] ?? 300);

        $olderThanMinutes = (int) ($input['minutes'] ?? 3 * 60);

        $merchantIds = $this->getMerchantIdsFromCronApiInputIfPresent($input);

        $keyMerchantIds = $this->repo->feature->findMerchantIdsHavingFeatures(Constant::$keyMerchantFeatureIdentifiers);

        $startTime = microtime();

        $orderIds = $this->repo->transfer->fetchPendingOrderTransfers($merchantIds, $keyMerchantIds, $limit, $olderThanMinutes);

        $endTime = microtime();

        $this->trace->info(
            TraceCode::PENDING_ORDER_TRANSFERS_FETCHED,
            [
                'order_ids'      => $orderIds,
                'time_taken'     => ($endTime - $startTime),
                'count'          => array_count_values($orderIds),
                'sync'           => $syncProcessing,
                'older_than_min' => $olderThanMinutes
            ]
        );

        return $this->processOrderTransfers($orderIds, $syncProcessing);
    }

    public function processPendingOrderTransfersForKeyMerchants(array $input)
    {
        $syncProcessing = (bool) ($input['sync'] ?? false);

        $limit = (int) ($input['limit'] ?? 300);

        $olderThanMinutes = (int) ($input['minutes'] ?? 3 * 60);

        $keyMerchantIds = $this->repo->feature->findMerchantIdsHavingFeatures(Constant::$keyMerchantFeatureIdentifiers);

        $startTime = microtime();

        $orderIds = $this->repo->transfer->fetchPendingOrderTransfersForKeyMerchants($keyMerchantIds, $limit, $olderThanMinutes);

        $endTime = microtime();

        $this->trace->info(
            TraceCode::PENDING_ORDER_TRANSFERS_FOR_KEY_MERCHANTS_FETCHED,
            [
                'order_ids'      => $orderIds,
                'time_taken'     => ($endTime - $startTime),
                'count'          => array_count_values($orderIds),
                'sync'           => $syncProcessing,
                'older_than_min' => $olderThanMinutes
            ]
        );

        return $this->processOrderTransfers($orderIds, $syncProcessing);
    }

    /**
     * @param array $input
     * @return int
     */
    public function updateSettlementStatusInTransfer(array $input) : int
    {
        $status = $input['status'] ?? [];

        $limit = $input['limit'] ?? 1000;

        $settlementIds = $this->repo->settlement->fetchSettlementIdsWithIncorrectStatusOnTransfers($status, $limit);

        $count = count($settlementIds);

        $this->trace->info(
            TraceCode::SETTLEMENT_IDS_FOR_STATUS_UPDATE_ON_TRANSFERS,
            [
                'limit'             => $limit,
                'count'             => $count,
                'settlement_ids'    => $settlementIds,
            ]
        );

        foreach ($settlementIds as $settlementId)
        {
            TransferSettlementStatus::dispatch($this->mode, $settlementId);
        }

        return $count;
    }

    public function processPendingPaymentTransfers(array $input)
    {
        $syncProcessing = (bool) ($input['sync'] ?? false);

        $limit = (int) ($input['limit'] ?? 300);

        $olderThanMinutes = (int) ($input['minutes'] ?? 3 * 60);

        $merchantIds = $this->getMerchantIdsFromCronApiInputIfPresent($input);

        $keyMerchantIds = $this->repo->feature->findMerchantIdsHavingFeatures(Constant::$keyMerchantFeatureIdentifiers);

        $startTime = microtime();

        $paymentIds = $this->repo->transfer->fetchPendingTransfers(EntityConstant::PAYMENT, $merchantIds, $keyMerchantIds, $limit, $olderThanMinutes);

        $endTime = microtime();

        $this->trace->info(
            TraceCode::PENDING_PAYMENT_TRANSFERS_FETCHED,
            [
                'payment_ids'    => $paymentIds,
                'time_taken'     => ($endTime - $startTime),
                'count'          => array_count_values($paymentIds),
                'sync'           => $syncProcessing,
                'older_than_min' => $olderThanMinutes
            ]
        );

        if ($syncProcessing === true)
        {
            return $this->processPaymentTransfersSync($paymentIds);
        }

        return $this->processPaymentTransfersAsync($paymentIds);
    }

    public function processPendingPaymentTransfersForKeyMerchants(array $input)
    {
        $syncProcessing = (bool) ($input['sync'] ?? false);

        $limit = (int) ($input['limit'] ?? 300);

        $olderThanMinutes = (int) ($input['minutes'] ?? 3 * 60);

        $keyMerchantIds = $this->repo->feature->findMerchantIdsHavingFeatures(Constant::$keyMerchantFeatureIdentifiers);

        $startTime = microtime();

        $paymentIds = $this->repo->transfer->fetchPendingTransfersForKeyMerchants(EntityConstant::PAYMENT, $keyMerchantIds, $limit, $olderThanMinutes);

        $endTime = microtime();

        $this->trace->info(
            TraceCode::PENDING_PAYMENT_TRANSFERS_FOR_KEY_MERCHANTS_FETCHED,
            [
                'payment_ids'    => $paymentIds,
                'time_taken'     => ($endTime - $startTime),
                'count'          => array_count_values($paymentIds),
                'sync'           => $syncProcessing,
                'older_than_min' => $olderThanMinutes
            ]
        );

        if ($syncProcessing === true)
        {
            return $this->processPaymentTransfersSync($paymentIds);
        }

        return $this->processPaymentTransfersAsync($paymentIds);
    }

    protected function processPaymentTransfersAsync(array $paymentIds)
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

                $payment = $this->repo->payment->findOrFail($paymentId);

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

    protected function processPaymentTransfersSync(array $paymentIds)
    {
        $payments = [];

        foreach ($paymentIds as $paymentId)
        {
            try
            {
                $this->trace->info(
                    TraceCode::PAYMENT_TRANSFER_PROCESS_SYNC_INIT,
                    [
                        'payment_id' => $paymentId,
                        'mode'       => $this->mode,
                    ]
                );

                $payment = $this->repo->payment->findOrFail($paymentId);

                $transfer = new Transfer\PaymentTransfer($payment);

                $transfer->process();

                array_push($payments, $paymentId);

            }
            catch (\Throwable $e)
            {
                $this->trace->critical(
                    TraceCode::PAYMENT_TRANSFER_PROCESS_SYNC_FAILED,
                    [
                        'payment_id' => $paymentId,
                        'message'    => $e->getMessage(),
                    ]
                );
            }
        }
        return $payments;
    }

    public function processFailedOrderTransfers(array $input)
    {
        $syncProcessing = (bool) ($input['sync'] ?? false);

        $limit = (int) ($input['limit'] ?? 300);

        $orderIds = $this->repo->transfer->fetchFailedTransfersToRetry(EntityConstant::ORDER, $limit);

        $this->trace->info(
            TraceCode::FAILED_ORDER_TRANSFER_PROCESS,
            [
                'order_ids'      => $orderIds,
                'sync'           => $syncProcessing,
            ]
        );

        return $this->processOrderTransfers($orderIds, $syncProcessing);
    }

    protected function processOrderTransfers(array $orderIds, bool $syncProcessing = false)
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

            if ($syncProcessing === true)
            {
                try
                {
                    $this->processOrderTransferSync($payment);

                    array_push($transferOrderIds, $orderId);
                }
                catch (\Throwable $e)
                {
                    $this->trace->critical(
                        TraceCode::ORDER_TRANSFER_PROCESS_SYNC_FAILED,
                        [
                            'order_id'   => $payment->getApiOrderId(),
                            'payment_id' => $payment->getId(),
                            'message'    => $e->getMessage(),
                        ]
                    );
                }
            }
            else
            {
                try
                {
                    $this->processOrderTransferAsync($payment);

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
        }

        $this->trace->info(
            TraceCode::ORDER_TRANSFER_PROCESS_RETRY_DONE,
            [
                'processed_order_ids' => $transferOrderIds
            ]
        );

        return $transferOrderIds;
    }

    protected function processOrderTransferSync(Payment\Entity $payment)
    {
        $this->trace->info(
            TraceCode::ORDER_TRANSFER_PROCESS_SYNC_INIT,
            [
                'order_id'   => $payment->getApiOrderId(),
                'payment_id' => $payment->getId(),
                'mode'       => $this->mode,
            ]
        );

        $transfer = new Transfer\OrderTransfer($payment);

        $transfer->process();
    }

    protected function processOrderTransferAsync(Payment\Entity $payment)
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
    }

    /**
     * @throws SettlementIdUpdateException
     */
    public function updateTransfersWithSettlementId($transactionIds)
    {
        $this->trace->info(
            TraceCode::TRANSFER_RECON_INITIATED,
            [
                'transaction_ids' => $transactionIds,
            ]
        );

        $startTime = microtime(true);

        $transferIdsSuccessful = [];
        $transferIdsFailed = [];
        $transactionIdsFailed = [];

        foreach ($transactionIds as $transactionId)
        {
            $transferId = null;
            $settlementId = null;

            try
            {
                [$transferId, $settlementId] = $this->updateSingleTransferWithSettlementId($transactionId);

                if ($transferId === null and $settlementId === null)
                {
                    continue;
                }

                $transferIdsSuccessful[$settlementId] = array_merge(
                    $transferIdsSuccessful[$settlementId] ?? [],
                    array($transferId)
                );
            }
            catch (\Throwable $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::CRITICAL,
                    TraceCode::TRANSFER_RECON_FAILURE,
                    [
                        'transfer_id'    => $transferId,
                        'transaction_id' => $transactionId,
                        'settlement_id'  => $settlementId,
                    ]
                );

                $transferIdsFailed[$settlementId] = array_merge(
                    $transferIdsFailed[$settlementId] ?? [],
                    array($transferId)
                );

                array_push($transactionIdsFailed, $transactionId);
            }
        }

        $endTime = microtime(true);

        $this->trace->info(
            TraceCode::TRANSFER_RECON_COMPLETE,
            [
                'settlement_ids_successful'    => array_keys($transferIdsSuccessful),
                'settlement_ids_failed'        => array_keys($transferIdsFailed),
                'transfers_ids_successful'     => $transferIdsSuccessful,
                'transfers_ids_failed'         => $transferIdsFailed,
                'transaction_ids_successful'   => $transferIdsSuccessful,
                'transaction_ids_failed'       => $transactionIdsFailed,
                'time_taken'                   => $endTime - $startTime,
            ]
        );

        if ((empty($transactionIdsFailed) === false) and ($transactionIdsFailed !== $transactionIds))
        {
            // One or more tranferId update failed, but not all. Hence, we will push a new job
            throw new SettlementIdUpdateException($transactionIdsFailed, false);
        }
        else if((empty($transactionIdsFailed) === false) and ($transactionIdsFailed === $transactionIds))
        {
            // All transferIds have failed, hence we will retry same job
            throw new SettlementIdUpdateException($transactionIds, true);
        }
        else
        {
            $this->trace->info(
                TraceCode::TRANSFER_RECON_ALL_TXN_IDS_UPDATED,
                [
                    'transaction_ids'  => array_keys($transferIdsSuccessful),
                ]
            );
        }
    }

    protected function updateSingleTransferWithSettlementId(string $transactionId): array
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
            return [null, null];
        }

        $payment = $transaction->source;

        if ($payment->transfer === null)
        {
            return [null, null];
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

        return [$transfer->getId(), $settlementId];
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

    /**
     * Takes an option as input to determine which action to perform. Actions are to
     * process payment transfers or order transfers in sync, update settlement_status,
     * update recipient_settlement_id.
     * Takes a data array as input that contains relevant IDs.
     *
     * This route is used to manually process the transfers from dark env currently.
     *
     * @param array $input
     * @return void
     * @throws BadRequestValidationFailureException
     * @throws LogicException
     */
    public function debugRoute(array $input)
    {
        (new Validator())->validateInput('debug_route', $input);

        $option = $input['option'];

        switch ($option)
        {
            case 'payment_transfer':
            {
                $this->trace->info(
                    TraceCode::ROUTE_DEBUG_ENDPOINT_OPTION_1,
                    [
                        'input' => $input,
                    ]
                );

                $paymentIds = $input['data'];

                if (count($paymentIds) > 150)
                {
                    throw new BadRequestValidationFailureException('Maximum 150 payment IDs can be passed.');
                }

                foreach ($paymentIds as $paymentId)
                {
                    (new TransferProcess($this->mode, $paymentId, Constant::PAYMENT))->handle();

                    $this->trace->info(
                        TraceCode::PAYMENT_TRANSFER_PROCESSED_IN_SYNC,
                        [
                            'payment_id' => $paymentId,
                        ]
                    );
                }

                break;
            }

            case 'order_transfer':
            {
                $this->trace->info(
                    TraceCode::ROUTE_DEBUG_ENDPOINT_OPTION_2,
                    [
                        'input' => $input,
                    ]
                );

                $paymentIds = $input['data'];

                if (count($paymentIds) > 150)
                {
                    throw new BadRequestValidationFailureException('Maximum 150 payment IDs can be passed.');
                }

                foreach ($paymentIds as $paymentId)
                {
                    (new TransferProcess($this->mode, $paymentId, Constant::ORDER))->handle();

                    $this->trace->info(
                        TraceCode::ORDER_TRANSFER_PROCESSED_IN_SYNC,
                        [
                            'payment_id' => $paymentId,
                        ]
                    );
                }

                break;
            }

            case 'settlement_status_update':
            {
                $this->trace->info(
                    TraceCode::ROUTE_DEBUG_ENDPOINT_OPTION_3,
                    [
                        'input' => $input,
                    ]
                );

                $settlementIds = $input['data'];

                if (count($settlementIds) > 5000)
                {
                    throw new BadRequestValidationFailureException('Maximum 5000 settlement IDs can be passed.');
                }

                $this->syncSettlementStatus($settlementIds);

                break;
            }

            case 'settlement_id_update':
            {
                $this->trace->info(
                    TraceCode::ROUTE_DEBUG_ENDPOINT_OPTION_4,
                    [
                        'input' => $input,
                    ]
                );

                // Insert code here for recipient_settlement_id update.

                break;
            }

            default:
            {
                throw new LogicException('Option passed is invalid.');
            }
        }
    }

    protected function syncSettlementStatus(array $settlementIds)
    {
        foreach ($settlementIds as $settlementId)
        {
            $this->trace->info(
                TraceCode::TRANSFER_SETTLEMENT_STATUS_SYNC_INITIATE,
                [
                    'settlement_id' => $settlementId,
                ]
            );

            TransferSettlementStatus::dispatch($this->mode, $settlementId);
        }
    }

    /**
     * @param array $input
     * @return string
     */
    public function getTransferTypeFilter(array & $input)
    {
        if (isset($input['transfer_type']) === true)
        {
            $type = $input['transfer_type'];

            unset($input['transfer_type']);

            return $type;
        }

        return null;
    }

    private function setPartnerDetailsForTransfer($transfer)
    {
        $linkedAccount = $this->repo->account->findByPublicId($transfer[Entity::RECIPIENT]);

        $parentAccount = $this->repo->merchant->find($linkedAccount->getParentId());

        // Merchant Id and Parent will be same in case of regular transfers.
        // For Platform transfer parent account id will be partner Id.
        if($this->merchant->getId() == $parentAccount->getId())
        {
            return $transfer;
        }

        $transfer[Constant::PARTNER_DETAILS] = [
            MerchantConstant::NAME  => $parentAccount->getName(),
            MerchantConstant::ID    => $parentAccount->getId(),
            Constant::EMAIL         => $parentAccount->getEmail(),
        ];

        return $transfer;
    }

    /**
     * @param array $transfers
     * @return array
     */
    public function setPartnerDetailsForTransfers($transfers)
    {
        try
        {
            $linkedAccountIds = $this->repo->merchant->fetchActivatedLinkedAccountIdsForParentMerchant($this->merchant->getId());

            foreach ($transfers as $transfer)
            {
                if (in_array($transfer[Entity::TO_ID], $linkedAccountIds) === false)
                {
                    $linkedAccount = $this->repo->merchant->find($transfer[Entity::TO_ID]);
                    $partner = $this->repo->merchant->find($linkedAccount->getParentId());

                    $transfer[Constant::PARTNER_DETAILS] = [
                        MerchantConstant::NAME  => $partner->getName(),
                        MerchantConstant::ID    => $partner->getId(),
                        Constant::EMAIL         => $partner->getEmail(),
                    ];
                }
            }
        }
        catch (Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::TRANSFER_PARTNER_DETAILS_SET_FAILED
            );
        }


        return $transfers;
    }

    protected function getMerchantIdsFromCronApiInputIfPresent(array $input)
    {
        $merchantIds = array();

        if (isset($input['merchant_ids']) == true)
        {
            $merchantIdsList = $input['merchant_ids']['list'] ?? array();

            $merchantIds = array_merge($merchantIds, $merchantIdsList);

            $featureFlags = $input['merchant_ids']['feature_flags'] ?? array();

            $merchantIdsFromFeatureFlags = $this->repo->feature->findMerchantIdsHavingFeatures($featureFlags);

            $merchantIds = array_merge($merchantIds, $merchantIdsFromFeatureFlags);
        }

        return array_unique($merchantIds);
    }
}
