<?php

namespace RZP\Models\Transfer;

use Throwable;
use Carbon\Carbon;
use Monolog\Logger;
use RZP\Constants\Timezone;
use Razorpay\Trace\Logger as Trace;

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
use RZP\Jobs\UpdateMerchantContext;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Exception\BadRequestException;
use RZP\Models\Ledger\ReverseShadow;
use RZP\Constants\Entity as EntityConstant;
use RZP\Exception\SettlementIdUpdateException;
use RZP\Models\Settlement\Entity as Settlement;
use RZP\Jobs\Transfers\TransferSettlementStatus;
use RZP\Models\Merchant\Constants as MerchantConstant;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Merchant\AccessMap\Core as AccessMapCore;
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
                    $linkedAccountIds = $this->repo->merchant->fetchLinkedAccountIdsForParentMerchant($this->merchant->getId());

                    $input[Constant::EXCLUDED_LINKED_ACCOUNTS] = $linkedAccountIds;
                }
                else
                {
                    $result = (new Merchant\Service())->isFeatureEnabledForPartnerOfSubmerchant(Feature\Constants::ROUTE_PARTNERSHIPS, $this->merchant->getId());

                    if( $result[Constant::FEATURE_ENABLED] === true)
                    {
                        $linkedAccountIds = $this->repo->merchant->fetchLinkedAccountIdsForParentMerchant($this->merchant->getId());

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
            (new Metric)->pushCreateFailedMetrics($e, $input);

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
                'count'          => count($orderIds),
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
                'count'          => count($orderIds),
                'sync'           => $syncProcessing,
                'older_than_min' => $olderThanMinutes
            ]
        );

        return $this->processOrderTransfers($orderIds, $syncProcessing);
    }

    public function processCreatedOrderTransfers(array $input)
    {
        if (isset($input['order_ids']) === false)
        {
            $limit = (int)($input['limit'] ?? 300);

            $olderThanMinutes = (int)($input['minutes'] ?? 3 * 60);

            $startTime = microtime();

            $orderIds = $this->repo->transfer->fetchCreatedOrderTransfers($limit, $olderThanMinutes);

            $endTime = microtime();

            $this->trace->info(
                TraceCode::CREATED_ORDER_TRANSFERS_FOR_KEY_MERCHANTS_FETCHED,
                [
                    'order_ids' => $orderIds,
                    'time_taken' => ($endTime - $startTime),
                    'count' => count($orderIds),
                    'older_than_min' => $olderThanMinutes
                ]
            );
        }
        else
        {
            $orderIds = $input['order_ids'];
        }

        foreach ($orderIds as $orderId)
        {
            $order = $this->repo->order->findOrFail($orderId);

            if (empty($order) === false)
            {
                $this->trace->info(
                    TraceCode::PAID_ORDER_CREATED_TRANSFER_PICKED_VIA_CRON,
                    [
                       'order_id' => $order->getId()
                    ]
                );
                $this->core->fetchTransfersAndMoveToPending($order);
            }
        }

        return $this->processOrderTransfers($orderIds, false);
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
                'count'          => count($paymentIds),
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
                'count'          => count($paymentIds),
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
                $payment = $this->repo->payment->findOrFail($paymentId);

                if ($payment->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === true)
                {
                    $core = new ReverseShadow\Transfers\Core();

                    $shouldProcess = $core->shouldProcessPaymentTransfersForReverseShadow(
                        $payment->getId(), $payment->merchant);

                    if ($shouldProcess === false)
                    {
                        continue;
                    }
                }

                $this->trace->info(
                    TraceCode::PAYMENT_TRANSFER_PROCESS_SQS_PUSH_INIT,
                    [
                        'payment_id' => $paymentId,
                        'mode'       => $this->mode,
                    ]
                );

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
                $payment = $this->repo->payment->findOrFail($paymentId);

                if ($payment->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === true)
                {
                    // Skip for sync mode, async mode is not skipped
                    continue;
                }

                $this->trace->info(
                    TraceCode::PAYMENT_TRANSFER_PROCESS_SYNC_INIT,
                    [
                        'payment_id' => $paymentId,
                        'mode'       => $this->mode,
                    ]
                );

                (new TransferProcess($this->mode, $payment->getId(), Constant::PAYMENT))->handle();

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

    public function createMissingTransactionForTransfers(array $input)
    {
        $limit = (int) ($input['limit'] ?? 500);

        $createdAtLessThanMinutes = (int) ($input['less_than'] ?? 60);

        $createdAtGreaterThanMinutes = (int) ($input['greater_than'] ?? 24*60);

        $merchant_ids = $input['merchant_ids'] ?? [];

        $transferIds = $this->repo->transfer->fetchTransfersToRetryCreatingTransaction($limit, $merchant_ids, $createdAtLessThanMinutes, $createdAtGreaterThanMinutes);

        $this->trace->info(
            TraceCode::FAILED_TRANSACTION_FOR_TRANSFERS,
            [
                'transfer_Ids'      => $transferIds,
            ]
        );

        return $this->core->createTransactionForTransferViaCron($transferIds);
    }

    public function processOrderTransfersForRearch(array $input)
    {
        $paymentId = $input['payment_id'];

        $payment = $this->repo->payment->findOrFail($paymentId);

        $paymentProcessor = new Payment\Processor\Processor($payment->merchant);

        $paymentProcessor->processTransferIfApplicable($payment);

        return ['status' => 'ok'];
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

            if ($order === null)
            {
                continue;
            }

            $payment = null;

            $orderId = $order->getId();

            $apiPayments = $this->repo->payment->fetchPaymentsForOrderId($orderId, $order->getMerchantId());

            $rearchPayments = $this->app['pg_router']->fetchOrderPayments($orderId, $order->getMerchantId());

            $allPayments = $apiPayments->merge($rearchPayments);

            foreach ($allPayments as $singlePayment)
            {
                if ($singlePayment->getStatus() === Payment\Status::CAPTURED)
                {
                    $payment = $singlePayment;

                    break;
                }
            }

            if ($payment === null)
            {
                foreach ($allPayments as $singlePayment)
                {
                    if ($singlePayment->getStatus() === Payment\Status::REFUNDED)
                    {
                        $payment = $singlePayment;

                        break;
                    }
                }
            }

            if ($payment->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === true)
            {
                $core = new ReverseShadow\Transfers\Core();

                $shouldProcess = $core->shouldProcessOrderTransfersForReverseShadow(
                    Constant::ORDER, $orderId, $order->merchant);

                if ($shouldProcess === false)
                {
                    continue;
                }
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

        (new TransferProcess($this->mode, $payment->getId(), Constant::ORDER))->handle();
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
                $merchantIds = array_merge($merchantIds, $this->repo->merchant->fetchLinkedAccountIdsForParentMerchant($id, true));
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
                // To process payment transfers in pending state.
                // Data should be array of payment IDs
                // Sample payload:
                // {"option": "payment_transfer", "data": ["NPBxWRRn778Om9", "X2xpdmU6d29hM1"]}

                $this->trace->info(
                    TraceCode::ROUTE_DEBUG_ENDPOINT_OPTION,
                    [
                        'option' => 'payment_transfer',
                        'input'  => $input,
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
                // To process order transfers in pending state.
                // Data should be array of payment IDs
                // Sample payload:
                // {"option": "order_transfer", "data": ["NPBxWRRn778Om9", "X2xpdmU6d29hM1"]}

                $this->trace->info(
                    TraceCode::ROUTE_DEBUG_ENDPOINT_OPTION,
                    [
                        'option' => 'order_transfer',
                        'input'  => $input,
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
                // To update settlement status in transfer entity
                // Data should be array of settlement IDs
                // Sample payload:
                // {"option": "settlement_status_update", "data": ["NPBxWRRn778Om9", "X2xpdmU6d29hM1"]}

                $this->trace->info(
                    TraceCode::ROUTE_DEBUG_ENDPOINT_OPTION,
                    [
                        'option' => 'settlement_status_update',
                        'input'  => $input,
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
                // To update receipient settlement IO in transfer entity
                // Data should be array of transaction IDs. The transaction ID is of the credit transaction to linked account
                // Sample payload:
                // {"option": "settlement_id_update", "data": ["NPBxWRRn778Om9", "X2xpdmU6d29hM1"]}

                $this->trace->info(
                    TraceCode::ROUTE_DEBUG_ENDPOINT_OPTION,
                    [
                        'option' => 'settlement_id_update',
                        'input'  => $input,
                    ]
                );

                $transactionIds = $input['data'];

                $this->updateTransfersWithSettlementId($transactionIds);

                break;
            }

            case 'mark_pending':
            {
                // To update the status of transfers to pending state
                // Data should be array of transfer IDs
                // Sample payload:
                // {"option": "mark_pending", "data": ["NPBxWRRn778Om9", "X2xpdmU6d29hM1"]}

                $this->trace->info(
                    TraceCode::ROUTE_DEBUG_ENDPOINT_OPTION,
                    [
                        'option' => 'mark_pending',
                        'input'  => $input,
                    ]
                );

                $transferIds = $input['data'];

                foreach ($transferIds as $transferId)
                {
                    $transfer = $this->repo->transfer->findOrFail($transferId);

                    $transfer->setStatus(Status::PENDING);

                    $transfer->setErrorCode(null);

                    $transfer->setMessage(null);

                    $transfer->setAttempts(1);

                    $transfer->saveOrFail();
                }

                break;
            }

            case 'mark_failed':
            {
                // To update the status of transfers to failed state
                // Data should be array of transfer IDs
                // Sample payload:
                // {"option": "mark_failed", "data": ["NPBxWRRn778Om9", "X2xpdmU6d29hM1"]}

                $this->trace->info(
                    TraceCode::ROUTE_DEBUG_ENDPOINT_OPTION,
                    [
                        'option' => 'mark_failed',
                        'input'  => $input,
                    ]
                );

                $transferIds = $input['data'];

                foreach ($transferIds as $transferId)
                {
                    $transfer = $this->repo->transfer->findOrFail($transferId);

                    $transfer->setStatus(Status::FAILED);

                    $transfer->setFailed();

                    $source = $transfer->getSourceType();

                    if ($source === Constant::PAYMENT)
                    {
                        $transfer->setAttempts(Constant::MAX_ALLOWED_PAYMENT_TRANSFER_PROCESS_ATTEMPTS);
                    }
                    else if ($source === Constant::ORDER)
                    {
                        $transfer->setAttempts(Constant::MAX_ALLOWED_ORDER_TRANSFER_PROCESS_ATTEMPTS);
                    }
                    else
                    {
                        $transfer->setAttempts(1);
                    }

                    $this->repo->saveOrFail($transfer);

                    (new Core())->eventTransferFailed($transfer);

                    (new \RZP\Models\LedgerOutbox\Core())->softDelete($transfer->getPublicId(), \RZP\Models\Ledger\Constants::TRANSFER);

                    $transfer->saveOrFail();
                }

                break;
            }

            case 'mark_processed':
            {
                // To update the status of transfers to processed state
                // Data should be array of transfer IDs
                // Sample payload:
                // {"option": "mark_processed", "data": ["NPBxWRRn778Om9", "X2xpdmU6d29hM1"]}

                $this->trace->info(
                    TraceCode::ROUTE_DEBUG_ENDPOINT_OPTION,
                    [
                        'option' => 'mark_processed',
                        'input'  => $input,
                    ]
                );

                $transferIds = $input['data'];

                foreach ($transferIds as $transferId)
                {
                    $transfer = $this->repo->transfer->findOrFail($transferId);

                    if (($transfer->getStatus() === Status::PENDING) or ($transfer->getStatus() === Status::FAILED))
                    {
                        if ($transfer->getStatus() === Status::FAILED)
                        {
                            $transfer->setErrorCode(null);

                            $transfer->setMessage(null);

                            $transfer->setAttempts(1);
                        }

                        $transfer->setStatus(Status::PROCESSED);

                        $transfer->saveOrFail();

                        (new Core())->eventTransferProcessed($transfer);

                        $this->core->createTransactionForTransferViaCron([$transferId]);
                    }
                }

                break;
            }

            case 'remove_on_hold':
            {
                // To remove the on_hold from transfers
                // Data should be array of transfer IDs
                // Sample payload:
                // {"option": "mark_processed", "data": ["NPBxWRRn778Om9", "X2xpdmU6d29hM1"]}

                $this->trace->info(
                    TraceCode::ROUTE_DEBUG_ENDPOINT_OPTION,
                    [
                        'option' => 'remove_on_hold',
                        'input'  => $input,
                    ]
                );

                $transferIds = $input['data'];

                foreach ($transferIds as $transferId)
                {
                    $transfer = $this->repo->transfer->findOrFail($transferId);

                    $transferInput = [];

                    $transferInput[Entity::ON_HOLD] = false;

                    $this->core->edit($transfer, $transferInput);
                }

                break;
            }

            case 'create_txns':
            {
                // To create debit/credit transactions for transfers
                // Data should be array of transfer IDs
                // Sample payload:
                // {"option": "create_txns", "data": ["NPBxWRRn778Om9", "X2xpdmU6d29hM1"]}

                $this->trace->info(
                    TraceCode::ROUTE_DEBUG_ENDPOINT_OPTION,
                    [
                        'option' => 'create_txns',
                        'input'  => $input,
                    ]
                );

                $transferIds = $input['data'];

                foreach ($transferIds as $transferId)
                {
                    $this->core->createTransactionForTransferViaCron([$transferId]);
                }

                break;
            }

            case 'fix_amount_transferred':
            {
                // To fix the amount_transferred in payment entity or transfer_payment entity
                // Data should be array of payment IDs
                // Sample payload:
                // {"option": "fix_amount_transferred", "data": ["pay_NPBxWRRn778Om9", "pay_X2xpdmU6d29hM1"]}

                $this->trace->info(
                    TraceCode::ROUTE_DEBUG_ENDPOINT_OPTION,
                    [
                        'option' => 'fix_amount_transferred',
                        'input'  => $input,
                    ]
                );

                $paymentIds = $input['data'];

                foreach ($paymentIds as $paymentId)
                {
                    (new Payment\Service())->fixTransferAmountTransferred($paymentId, []);
                }

                break;
            }

            case 'create_la_payment':
            {
                // To create the LA payment for transfer IDs
                // Data should be array of transfer IDs
                // Sample payload:
                // {"option": "create_la_payment", "data": ["NPBxWRRn778Om9", "X2xpdmU6d29hM1"]}

                $this->trace->info(
                    TraceCode::ROUTE_DEBUG_ENDPOINT_OPTION,
                    [
                        'input' => $input,
                        'option' => 'create_payment',
                    ]
                );

                $transferIds = $input['data'];

                foreach ($transferIds as $transferId)
                {
                    $transfer = $this->repo->transfer->findOrFail($transferId);

                    $sourcePayment = null;

                    $transferProcessor = null;

                    if ($transfer->getSourceType() === Constant::PAYMENT)
                    {
                        $sourcePayment = $transfer->source;

                        $transferProcessor = new OrderTransfer($sourcePayment);
                    }
                    else if ($transfer->getSourceType() === Constant::ORDER)
                    {
                        $sourceOrderId = $transfer->getSourceId();

                        $apiPayments = $this->repo->payment->fetchPaymentsForOrderId($sourceOrderId, $transfer->getMerchantId());

                        $rearchPayments = $this->app['pg_router']->fetchOrderPayments($sourceOrderId, $transfer->getMerchantId());

                        $allPayments = $apiPayments->merge($rearchPayments);

                        $sourcePayment = null;

                        foreach ($allPayments as $singlePayment)
                        {
                            if ($singlePayment->getStatus() === Payment\Status::CAPTURED || $singlePayment->getStatus === Payment\Status::REFUNDED)
                            {
                                $sourcePayment = $singlePayment;

                                break;
                            }
                        }

                        // fetching payment again to get from sources configured for archived entity
                        // As of now, archived payment fetch with findOrFail happens on fallback replica
                        // This will also prevent columns like _record_source from warm storage to be present in entity attributes
                        $sourcePayment = $this->repo->payment->findOrFail($sourcePayment->getId());

                        $transferProcessor = new PaymentTransfer($sourcePayment);
                    }

                    $transferPayment = $transferProcessor->createTransferredEntity($transfer, $sourcePayment);

                    $this->trace->info(
                        TraceCode::ROUTE_DEBUG_ENDPOINT_OPTION,
                        [
                            'status'              => 'success',
                            'transfer_payment_id' => $transferPayment->getId(),
                        ]
                    );
                }

                break;
            }

            case 'unlock_la_form':
            {
                // To unlock linked account activation form
                // Data should be array of linked account IDs
                // Sample payload:
                // {"option": "unlock_la_form", "data": ["NPBxWRRn778Om9", "X2xpdmU6d29hM1"]}

                $this->trace->info(
                    TraceCode::ROUTE_DEBUG_ENDPOINT_OPTION,
                    [
                        'option' => 'unlock_la_form',
                        'input'  => $input,
                    ]
                );

                $merchantIds = $input['data'];

                foreach ($merchantIds as $merchantId)
                {
                    $merchant = $this->repo->merchant->findOrFail($merchantId);

                    $merchantDetails = $merchant->merchantDetail;

                    if ($merchantDetails->isLocked() === false)
                    {
                        return;
                    }

                    if ($merchant->isLinkedAccount() === true)
                    {
                        $merchantDetailCore = new Merchant\Detail\Core();

                        $input = [
                            'locked'  =>  false,
                        ];

                        $merchantDetailCore->editMerchantDetailFields($merchant, $input);
                    }
                }

                break;
            }

            case 'trigger_update_merchant_context':
            {
                // To trigger the UpdateMerchantContext job
                // Data should be array of JSON of linked account ID and BVS validation ID
                // Sample payload:
                //  {
                //      "option": "trigger_update_merchant_context",
                //      "data": [
                //                  {"linked_account_id": "NPBxWRRn778Om9", "bvs_id": "X2xpdmU6d29hM1"}
                //              ]
                //  }

                $this->trace->info(
                    TraceCode::ROUTE_DEBUG_ENDPOINT_OPTION,
                    [
                        'option' => 'trigger_update_merchant_context',
                        'input'  => $input,
                    ]
                );

                $linkedAccountsData = $input['data'];

                foreach ($linkedAccountsData as $linkedAccountData)
                {
                    $linkedAccountId = $linkedAccountData['linked_account_id'];

                    $bvsId = $linkedAccountData['bvs_id'];

                    (new UpdateMerchantContext($this->mode, $linkedAccountId, $bvsId))->handle();
                }

                break;
            }

            case 'activate_la':
            {
                // To activate linked account. This should be used only when the linked account is created
                // via batch upload or beta account create API
                // Data should be array of linked account IDs
                // Sample payload:
                // {"option": "activate_la", "data": ["NPBxWRRn778Om9", "X2xpdmU6d29hM1"]}

                $this->trace->info(
                    TraceCode::ROUTE_DEBUG_ENDPOINT_OPTION,
                    [
                        'option' => 'activate_la',
                        'input'  => $input,
                    ]
                );

                $linkedAccountIds = $input['data'];

                foreach ($linkedAccountIds as $linkedAccountId)
                {
                    $this->repo->transaction(function () use ($linkedAccountId) {
                        $linkedAccount = $this->repo->merchant->findOrFail($linkedAccountId);

                        $merchantDetailCore = new Merchant\Detail\Core();

                        $merchantDetailCore->autoActivateMerchantIfApplicable($linkedAccount);
                    });
                }

                break;
            }

            default:
            {
                throw new LogicException('Option passed is invalid.');
            }
        }

        $this->trace->info(
            TraceCode::ROUTE_DEBUG_ENDPOINT_OPTION,
            [
                'option' => $option,
                'status' => 'success',
            ]
        );
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
            $linkedAccountIds = $this->repo->merchant->fetchLinkedAccountIdsForParentMerchant($this->merchant->getId());

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

    public function getPlatformFeeDetailsForMerchant(string $merchantId, int $month, int $year, int $beginTimestamp, int $endTimestamp): ?array
    {
        try
        {
            $reqStartTime = microtime(true);

            // fetch partners for which rzp can invoice platform fee to its sub-merchants
            $partners =  (new Merchant\Service())->getSubmerchantPartnersWithfeatureEnabled(Feature\Constants::PARTNER_PLAT_FEE_INVOICE, $merchantId);

            if ($partners->isEmpty() === true)
            {
                return null;
            }

            $partnerIds = $partners->getIds();

            $merchantLinkedAccounts = $this->repo->merchant->fetchLinkedAccountIdsForParentMerchantIds($partnerIds);

            $platformFeeTransferDetails = $this->repo->transfer->fetchPlatformFeeTransferDetailsForMerchant($merchantId, $merchantLinkedAccounts, $beginTimestamp, $endTimestamp)->getAttributes();

            if (empty($platformFeeTransferDetails) === true or empty($platformFeeTransferDetails['amount']) === true)
            {
                $platformFeeTransferDetails['amount'] = 0;
                $platformFeeTransferDetails['fee'] = 0;
                $platformFeeTransferDetails['tax'] = 0;
            }

            if (empty($platformFeeTransferDetails['fee']) === true)
            {
                $platformFeeTransferDetails['fee'] = 0;
            }

            if (empty($platformFeeTransferDetails['tax']) === true)
            {
                $platformFeeTransferDetails['tax'] = 0;
            }

            // fetch transfer reversals for the given month, year and merchant
            $platformFeeReversalDetails = $this->repo->reversal->fetchPlatformFeeReversalDetailsForMerchant($merchantId, $merchantLinkedAccounts, $beginTimestamp, $endTimestamp)->getAttributes();

            if (empty($platformFeeReversalDetails) === true or empty($platformFeeReversalDetails['amount']) === true)
            {
                $platformFeeReversalDetails['amount'] = 0;
            }

            // for reversals, we are deducting amount as follows:
            // amount = -(reversal_amount/1.18)
            $partnerFee = round((($platformFeeTransferDetails['amount'] - $platformFeeReversalDetails['amount']) * 1.0)/1.18);
            $rzpFee = $platformFeeTransferDetails['fee'] - $platformFeeTransferDetails['tax'];

            $platformFee = $partnerFee + $rzpFee;
            $nettTax    = round($partnerFee * Merchant\Invoice\Constants::GST_PERCENTAGE) + $platformFeeTransferDetails['tax'];

            $platformFeeDetails = [
                'amount'            => $platformFee,
                'tax'               => $nettTax,
                'transfer_details'  => $platformFeeTransferDetails,
                'reversal_details'  => $platformFeeReversalDetails
            ];

            $this->trace->info(
                TraceCode::MERCHANT_PLATFORM_FEE_FETCH,
                [
                    'merchant_id'           => $merchantId,
                    'month'                 => $month,
                    'year'                  => $year,
                    'begin_timestamp'       => $beginTimestamp,
                    'end_timestamp'         => $endTimestamp,
                    'platform_fee_details'  => $platformFeeDetails,
                    'req_time_taken'        => get_diff_in_millisecond($reqStartTime)
                ]
            );

            $this->trace->count(Metric::MERCHANT_PLATFORM_FEE_FETCH_REQUEST);

            $this->trace->histogram(Metric::MERCHANT_PLATFORM_FEE_FETCH_TIME_IN_MS, get_diff_in_millisecond($reqStartTime));

            return $platformFeeDetails;
        }
        catch (\Exception $exception)
        {
            $this->trace->traceException(
                $exception,
                Logger::ERROR,
                TraceCode::MERCHANT_PLATFORM_FEE_FETCH_ERROR,
                [
                    'merchant_id'       => $merchantId,
                    'month'             => $month,
                    'year'              => $year,
                    'begin_timestamp'   => $beginTimestamp,
                    'end_timestamp'     => $endTimestamp,
                ]
            );

            $this->trace->count(Metric::MERCHANT_PLATFORM_FEE_FETCH_FAILURE);
        }

        return null;
    }

    public function createTransactionForTransfer(array $input)
    {
        return $this->core->createInternalTransactionForTransfer($input);
    }

    public function createTransferReversalTransactions(array $input)
    {
        return $this->core->createTransferReversalTransactions($input);
    }

    public function fetchPendingTransfersCount()
    {
        $category1_order_transfers_count = $this->repo->transfer->fetchPendingOrderTransfersCount(Constant::CATEGORY_1_MCC);
        $category1_payment_transfers_count = $this->repo->transfer->fetchPendingPaymentTransfersCount(Constant::CATEGORY_1_MCC);

        $category2_order_transfers_count = $this->repo->transfer->fetchPendingOrderTransfersCount(Constant::CATEGORY_2_MCC);
        $category2_payment_transfers_count = $this->repo->transfer->fetchPendingPaymentTransfersCount(Constant::CATEGORY_2_MCC);

        $category3_order_transfers_count = $this->repo->transfer->fetchPendingOrderTransfersCount();
        $category3_payment_transfers_count = $this->repo->transfer->fetchPendingPaymentTransfersCount();

        (new Metric())->pushPendingTransfersCount($category1_payment_transfers_count, $category1_order_transfers_count, Constant::CATEGORY_1);
        (new Metric())->pushPendingTransfersCount($category2_payment_transfers_count, $category2_order_transfers_count, Constant::CATEGORY_2);
        (new Metric())->pushPendingTransfersCount($category3_payment_transfers_count, $category3_order_transfers_count, Constant::CATEGORY_3);

        $data = [
            'payment_transfers_count' => [
                'category1' => $category1_payment_transfers_count,
                'category2' => $category2_payment_transfers_count,
                'category3' => $category3_payment_transfers_count,
            ],
            'order_transfers_count' => [
                'category1' => $category1_order_transfers_count,
                'category2' => $category2_order_transfers_count,
                'category3' => $category3_order_transfers_count,
            ]
        ];

        $this->trace->info(
            TraceCode::PENDING_TRANSFERS_COUNT,
            $data
        );

        return $data;
    }

}
