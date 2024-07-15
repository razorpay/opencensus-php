<?php

namespace RZP\Models\Settlement\Bucket;

use Cache;
use Config;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Jobs\Settlement\Bucket;
use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Models\Settlement\SettlementServiceMigration;
use RZP\Models\Settlement\SlackNotification;
use RZP\Trace\TraceCode;
use RZP\Models\Transaction;
use RZP\Models\Payment;
use RZP\Constants\Timezone;
use RZP\Base\RuntimeManager;
use RZP\Models\Merchant as ME;
use RZP\Models\Merchant\Balance;
use RZP\Models\Currency\Currency;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Preferences;
use RZP\Models\Payment\Refund;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Jobs\Settlement\TransactionMigrationPublish;
use RZP\Models\Transfer\Constant as TransferConstant;
use RZP\Constants\Country;
use RZP\Models\Merchant\InternationalIntegration\Service as MIIService;

class Core extends Base\Core
{
    const SETTLEMENT_TRANSACTION = 'settlement_transaction';

    const JPMC_IMPORT_FLOW_TRANSACTION_TYPES = [
        'payment',
    ];

    protected $preference;

    public function __construct()
    {
        $this->preference = new Preference;

        parent::__construct();
    }

    /**
     * give next settlement time based on bucketing entry
     *
     * @param MerchantEntity $merchant
     * @param Balance\Entity $balance
     * @return int
     */
    public function getNextSettlementTime(MerchantEntity $merchant, Balance\Entity $balance): int
    {
        $bucket = $this->repo
                       ->settlement_bucket
                       ->getNextSettlementTime($merchant->getId(), $balance->getType());

        if ($bucket === null)
        {
            return 0;
        }

        return $bucket->getBucketTimestamp();
    }

    public function deleteCompletedBucketEntries(array $input): array
    {
        $this->trace->info(
            TraceCode::DELETING_COMPLETED_BUCKET_ENTRIES,
            $input);

        $timestamp = Carbon::now(Timezone::IST)->subDay();

        if (isset($input['timestamp']) === true)
        {
            $timestamp = $input['timestamp'];
        }

        $recordsDeletedCount = $this->repo
                                    ->settlement_bucket
                                    ->removeCompletedEntriesBeforeTimestamp($timestamp);

        $result = [
            'count' => $recordsDeletedCount,
        ];

        $this->trace->info(
            TraceCode::COMPLETED_BUCKET_ENTRIES_DELETED,
            $result);

        return $result;
    }

    public function backfillSettlementBucket(array $input)
    {
        // Time limit of 10 mins
        RuntimeManager::setTimeLimit(600);

        $currentTime = Carbon::now(Timezone::IST);

        $startTime = $currentTime->subMinutes($currentTime->minute)
                                 ->subSecond($currentTime->second)
                                 ->getTimestamp();
        $endTime = null;

        if (empty($input['start']) === false)
        {
            $startTime = $input['start'];
        }

        if (empty($input['end']) === false)
        {
            $endTime = $input['end'];
        }

        $this->trace->info(
            TraceCode::BUCKETING_INITIATE,
            [
                'start' => $startTime,
                'end'   => $endTime,
            ]);

        $featuredMids = $this->repo->feature->findMerchantsHavingFeatures([
            Feature\Constants::ES_AUTOMATIC,
            Feature\Constants::DAILY_SETTLEMENT,
            Feature\Constants::BLOCK_SETTLEMENTS,
        ])->pluck('entity_id')
          ->toArray();

        $featuredMids = array_merge($featuredMids, Preferences::NO_SETTLEMENT_MIDS);

        $result = $this->repo->transaction->getMerchantSettledAtTime($featuredMids, $startTime, $endTime);

        foreach ($result->toArray() as $record)
        {
            $this->addMerchantToSettlementBucket('', $record['merchant_id'], $record['settled_at']);
        }

        $this->trace->info(
            TraceCode::BUCKETING_DONE,
            [
                'count' => $result->count(),
                'start' => $startTime,
                'end'   => $endTime,
            ]
        );

        return [
            'count' => $result->count(),
        ];
    }

    /**
     * will return all the merchant ids who's settlement has to go in given bucket
     *
     * @param string $balanceType
     * @param null   $bucketTimestamp
     *
     * @return array
     */
    public function getMerchantIdsFromBucket(string $balanceType, $bucketTimestamp = null): array
    {
        // if the bucket timestamp is not given then derive the same for current timestamp
        if (empty($bucketTimestamp) === true)
        {
            $bucketTimestamp = Carbon::now(Timezone::IST)->getTimestamp();
        }

        $merchantIDs = $this->repo
                            ->settlement_bucket
                            ->getMerchantIdsFromBucket($balanceType, $bucketTimestamp)
                            ->pluck(Entity::MERCHANT_ID)
                            ->toArray();

        return [$bucketTimestamp, $merchantIDs];
    }

    /**
     * will add the merchant to settlement bucket which will be derive based on settlement time provided
     * As a part of RSR-3104; apart from inter-nodal MIDs no merchants will be allowed to be added to
     * the settlement bucket
     * @param string $transactionId
     * @param string $merchantId
     * @param        $settlementTime
     * @return bool
     */
    public function addMerchantToSettlementBucket(string $transactionId, string $merchantId, $settlementTime): bool
    {
        $balanceType = $this->repo->transaction->getTransactionBalanceType($transactionId);

        if (Balance\Type::isSettleableBalanceType($balanceType) === false)
        {
            return false;
        }

        $balanceType = $balanceType ?? Balance\Type::PRIMARY;

        // check merchant specific conditions
        $status = $this->preference
                       ->skipMerchantSettlement($merchantId);

        if ($status === true)
        {
            return false;
        }

        $balance = $this->repo->balance->getMerchantBalanceByType($merchantId, $balanceType);

        $status = $this->shouldProcessViaNewService($merchantId, $balance);

        if ($status === true)
        {
            return false;
        }

        if (in_array($merchantId, SettlementServiceMigration::INTER_NODAL_API_MIDS) === false)
        {
            $this->trace->info(
                TraceCode::SETTLEMENT_BUCKETING_NOT_ALLOWED,
                [
                    'merchant_id' => $merchantId,
                    'message'     => 'Cannot push this merchant to bucket as settlement not allowed for it from API.',
                ]);
            return false;
        }

        // check early settlement preferences
        list($status, $timestamp) = $this->preference
                                         ->getEarlySettlementBucketIfApplicable($merchantId, $settlementTime);

        if ($status === true)
        {
            return $this->addToBucket($merchantId, $timestamp, $balanceType, $settlementTime);
        }

        // check merchant preference
        list($status, $timestamp) = $this->preference
                                         ->getMerchantSpecificBucket($merchantId, $settlementTime);

        if ($status === true)
        {
            return $this->addToBucket($merchantId, $timestamp, $balanceType, $settlementTime);
        }

        $currentTimestamp = Carbon::now(Timezone::IST);

        $settlementTime = Carbon::createFromTimestamp($settlementTime, Timezone::IST);

        $settlementTime = Preference::getCeilTimestamp($settlementTime);

        $bucketTimestamp = ($settlementTime->getTimestamp() < $currentTimestamp->getTimestamp()) ?
            Preference::getNextBucket($currentTimestamp->getTimestamp()) :
            Preference::getNextBucket($settlementTime->getTimestamp());

        return $this->addToBucket($merchantId, $bucketTimestamp, $balanceType, $settlementTime);
    }

    /**
     * It will fetch the relevant data from transaction and publish it for settlement processing
     *
     * @param Transaction\Entity $txn
     * @param Balance\Entity|null $balance
     * @param bool $initialRamp
     */
    public function publishForSettlement(Transaction\Entity $txn, Balance\Entity $balance = null, $initialRamp = false)
    {
        $meta         = null;
        $balanceType  = ($balance === null) ? Balance\Type::PRIMARY : $balance->getType();

        // Only primary and commission balance are eligible for settlement
        if (Balance\Type::isSettleableBalanceType($balanceType) === false)
        {
            return;
        }

        //$settledBy by should be passed mandatory by the txn pushing service.
        $settledBy = 'Razorpay';

        // currently meta details present only for payment type
        if ($txn->isTypePayment() === true)
        {
            $payment = $txn->source;

            if ($payment->getSettledBy() !== 'Razorpay')
            {
                $settledBy = $payment->getSettledBy();
            }

            $meta = [
                'method'        => $payment->getMethod(),
                'international' => $payment->isInternational(),
            ];

            if (($payment->merchant->isLinkedAccount() === true) and
                ($payment->getMethod() === Payment\Method::TRANSFER))
            {
                try
                {
                    $this->addOriginMethodForLinkedAccount($payment, $meta);
                }
                catch(\Throwable $e)
                {
                    $this->trace->traceException(
                        $e,
                        Trace::ERROR,
                        TraceCode::SETTLEMENT_SERVICE_TRANSACTION_MIGRATION_ORIGIN_METHOD_FETCH_FAILED,
                        [
                            'transaction_id' => $txn->getId(),
                            'source_id' => $txn->getEntityId()
                        ]);

                    throw new Exception\LogicException('Either transfer not found or transfer source not found');
                }
            }

            if($payment->isInternational() === true)
            {
                $commissionAmount = null;
                $commissionConversionAmount = null;

                if ($payment->isB2BExportCurrencyCloudPayment() or $payment->isBankTransfer())
                {
                    $commissionAmount = $payment->getMccMarkDownCommisionAmount() + $txn->getFee();
                    $commissionConversionAmount = (new \RZP\Models\Currency\Core())->convertAmount($commissionAmount, Currency::INR, Currency::USD);
                }

                $meta += [
                    'gateway'       => $payment->getGateway(),
                    'remitter_info' => [
                        "remitter_name"    => $this->getRemitterName($payment),
                        "remitter_address" => $this->getRemitterAddress($payment),
                        "remitter_country" => $this->getRemitterCountry($payment)
                    ],
                    'amount_meta'   => [
                        "conversion_amount"             => $this->getConversionAmount($payment, Currency::USD),
                        "conversion_currency"           => Currency::USD,
                        "settlement_currency"           => $this->getSettlementCurrencyOfPayment($payment)
                    ],
                ];
                if ( $commissionAmount != null)
                {
                    $meta['amount_meta'] +=[
                        "intl_commission_amount"             => $commissionAmount,
                        "intl_commission_conversion_amount"  => $commissionConversionAmount,
                    ];
                }

                if (Payment\Gateway::isOPGSPSettlementGateway($payment->getGateway()) === true &&
                    (empty($meta['remitter_info']['remitter_name']) or empty($meta['remitter_info']['remitter_address'])))
                {
                    $this->trace->info(
                        TraceCode::REMITTER_DETAILS_MISSING_FOR_INTL_PAYMENT_SETTLEMENT,
                        [
                            'payment_id'    => $payment->getId(),
                            'merchant_id'   => $payment->getMerchantId(),
                            'gateway'       => $payment->getGateway()
                        ]);

                    throw new Exception\LogicException('Remitter Name or Address not found for OPGSP Settlement Gateway');
                }
            }
        }

        // If this feature flag is enabled on a merchant, we do settlements on currency level
        // Transactions like payments, refunds, adjustments on disputes / payments can be handled
        // on basis of currencies. In case of reversals or adjustments without payments linkage
        // we use INR as default with assumptions INR balance will always be greater than any other
        // currencies balance.
        // Handling Payment, Adjustments and any other type of transactions here expect refunds.
        // which is currently handled in below getMetaForSource function to avoid multiple DB Fetch
        // for external entities.

        if($txn->merchant->isSettlementByCurrencyEnabled() === true)
        {
            $payment = null;

            if ($txn->isTypePayment() === true)
            {
                $payment = $txn->source;
            }

            if ($txn->isTypeAdjustment() === true)
            {
                $adjustment = $txn->source;

                if(isset($adjustment) === true)
                {
                    if ($adjustment->getEntityType() === Transaction\Type::DISPUTE)
                    {
                        $payment = $adjustment->entity->payment;
                    }

                    if($adjustment->getEntityType() === Transaction\Type::PAYMENT)
                    {
                        $payment = $adjustment->entity;
                    }
                }
            }

            if(empty($meta) === true || isset($meta) === false)
            {
                $meta = [];
            }

            $meta += [
                "settlement_by_currency" => true,
                "payment_currency" => $payment ? $payment->getCurrency() : Currency::INR
            ];
        }

        // For merchants who have omni feature enabled, settlements will be done separately for online
        // and offline source_channel. To enable this, we will be passing omni meta details
        // along with the transaction entity.
        if($txn->merchant->isOmniEnabled() === true)
        {
            $payment = null;

            if ($txn->isTypePayment() === true)
            {
                $payment = $txn->source;
            }

            if ($txn->isTypeAdjustment() === true)
            {
                $adjustment = $txn->source;

                if(isset($adjustment) === true)
                {
                    if ($adjustment->getEntityType() === Transaction\Type::DISPUTE)
                    {
                        $payment = $adjustment->entity->payment;
                    }

                    if($adjustment->getEntityType() === Transaction\Type::PAYMENT)
                    {
                        $payment = $adjustment->entity;
                    }
                }
            }

            if(empty($meta) === true || isset($meta) === false)
            {
                $meta = [];
            }

            $meta += [
                "omni_details" => [
                    "enabled" => true,
                    "source_channel" => $payment ? $payment->getSourceChannel(): "online"
                ]
            ];
        }

        // Add meta details for refund type txn
        if (($txn->isTypeRefund() === true) || ($txn->isTypeTransfer() === true))
        {
            $meta = $this->getMetaForSource($txn);
        }

        if($txn->merchant->isJpmcImportFlowEnabled() === true)
        {
            if(empty($meta) === true || isset($meta) === false)
            {
                $meta = [];
            }

            $meta = $this->getMetaforJpmcImportFlow($txn, $meta);
        }

        $onHoldReason = ($txn->getOnHold() === true) ? 'created with transaction on hold' : '';

        $payload = [
            'id'                => $txn->getId(),
            'merchant_id'       => $txn->getMerchantId(),
            'source_id'         => $txn->getEntityId(),
            'source_type'       => $txn->getType(),
            'balance_type'      => strtoupper($balanceType),
            'currency'          => $txn->getCurrency(),
            'credit'            => $txn->getCredit(),
            'debit'             => $txn->getDebit(),
            'fee'               => $txn->getFee(),
            'tax'               => $txn->getTax(),
            'settled_by'        => $settledBy,
            'on_hold'           => $txn->getOnHold(),
            'on_hold_reason'    => $onHoldReason,
            'meta'              => (object) $meta,
        ];

        if ($initialRamp === true)
        {
            $payload['created_at'] = $txn->getCreatedAt();
        }

        try
        {
            $this->app['sns']->publish(json_encode($payload), self::SETTLEMENT_TRANSACTION);

            $this->trace->info(
                TraceCode::SETTLEMENT_SERVICE_TRANSACTION_PUSH_SUCCESSFUL,
                [
                    'transaction_id' => $txn->getId(),
                    'merchant_id'    => $txn->getMerchantId(),
                    'payload'        => $payload,
                ]);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SETTLEMENT_TRANSACTION_STREAMING_FAILED,
                $payload);

            throw $e;
        }
    }

    private function getMetaforJpmcImportFlow(Transaction\Entity $txn, array $meta)
    {
        if (in_array($txn->getType(), self::JPMC_IMPORT_FLOW_TRANSACTION_TYPES) === false)
        {
            return $meta;
        }

        $paymentEntity = null;
        $orderEntity = null;
        $refundEntity = null;
        $merchantEntity = null;
        $notes = [];
        $cartInfo = [];
        $hsCodeData = (new MIIService())->getMerchantHsCode($txn->getMerchantId());

        if ($txn->isTypePayment() === true)
        {
            $paymentEntity = $txn->source;
            $orderEntity = $paymentEntity->order;
            $merchantEntity = $paymentEntity->merchant;
            $cartInfo = $paymentEntity->order->getCartInfoOrderMeta();
            $notes = $paymentEntity->getNotes()->toArray();
        }

        if ((isset($merchantEntity) === false) ||
            (isset($paymentEntity) === false) ||
            (isset($orderEntity) === false) ||
            (empty($cartInfo) === true) ||
            (empty($notes) === true))
        {
             $this->trace->info(
                TraceCode::JPMC_TRANSACTION_META_DETAILS_MISSING,
                [
                    'payment_available'         => isset($paymentEntity),
                    'order_available'           => isset($orderEntity),
                    'merchant_available'        => isset($merchantEntity),
                    'cart_info_empty'           => empty($cartInfo),
                    'payment_notes_empty'       => empty($notes),
                    'transaction_id'            => $txn->getId(),
                    'merchant_id'               => $txn->getMerchantId(),
                ]);

            throw new Exception\LogicException('Transaction meta details not found for JPMC');
        }

        $paymentDetails = [
            'id'                => $paymentEntity->getId(),
            'invoice_number'    => $notes['invoice_number'] ?? '',
            'goods_description' => $notes['goods_description'] ?? '',
            'created_at'        => $paymentEntity->getCreatedAt(),
            'amount'            => $paymentEntity->getAmount(),
            'currency'          => $paymentEntity->getCurrency(),
            'base_amount'       => $paymentEntity->getBaseAmount(),
            'customer_id'       => $paymentEntity->getAttribute('customer_id'),
        ];

        $orderDetails = [
            'id'                => $orderEntity->getId(),
            'amount'            => $orderEntity->getAmount(),
            'currency'          => $orderEntity->getCurrency(),
            'customer_id'       => $orderEntity->getCustomerId(),
            'shipping_details'  => $cartInfo['customer_details'] ?? '',
        ];

        $merchantDetails = [
            'id'                => $merchantEntity->getId(),
            'purpose_code'      => $merchantEntity->getPurposeCode(),
            'hs_code'           => $hsCodeData['hs_code'] ?? '',
        ];

        $combinedDetails = [
            'payment_details'   => $paymentDetails,
            'order_details'     => $orderDetails,
            'merchant_details'  => $merchantDetails,
        ];

        $jpmcDetails = [
            'jpmc_details'      => $combinedDetails,
        ];

        $meta += $jpmcDetails;

        $this->trace->info(
                TraceCode::JPMC_TRANSACTION_META,
                [
                    'transaction_id' => $txn->getId(),
                    'merchant_id'    => $txn->getMerchantId(),
                    'source_id'      => $txn->getEntityId(),
                    'source_type'    => $txn->getType(),
                    'meta'           => $meta,
                ]);

        return $meta;
    }

    private function getRemitterName(Payment\Entity $payment) {

        $remitterName = null;

        //If card payment get remitter name from card entity.
        if (($payment->isCard() === true) and ($payment->card !== null))
        {
            $remitterName = $payment->card->getName();
        }

        // If Payment is not a card Payment and If gateway is under ADDRESS_NAME_REQUIRED_GATEWAYS array
        // we will fetch remitter name from addresses table.
        else if (Payment\Gateway::isAddressAndNameRequiredGateway($payment->getGateway()) === true)
        {
            $billingAddress = $payment->fetchBillingAddress();

            if(empty($billingAddress) === false)
            {
                $remitterName = $billingAddress->getName();
            }
        }

        //if empty, try fetching from customer
        if ((empty($remitterName) === true) and ($payment->customer !== null)){
            $remitterName = $payment->customer->getName();
        }

        return $remitterName;
    }

    private function getRemitterAddress(Payment\Entity $payment)
    {
        $address = $payment->fetchBillingAddress();

        return (empty($address)===false)?$address->formatAsText():null;
    }

    /**

     * Returns Country Name from address saved in
     * addresses table linked with payment entity.
     *
     * @param Payment\Entity
     * @return String | null
     */

    private function getRemitterCountry(Payment\Entity $payment)
    {
        $address = $payment->fetchBillingAddress();

        if(empty($address) === true){
            return null;
        }

        return $address->getCountryNameFormatted();
    }

    private function getConversionAmount(Payment\Entity $payment, string $currency) {
        if ($payment->getGatewayCurrency() === $currency)
        {
            return $payment->getGatewayAmount();
        }

        return (new \RZP\Models\Currency\Core())->convertAmount($payment->getGatewayAmount(), $payment->getGatewayCurrency(), $currency);
    }

    protected function addOriginMethodForLinkedAccount(Payment\Entity $payment, array &$meta)
    {
        $transfer = $payment->transfer;

        $sourceType = $transfer->getSourceType();

        $sourcePayment = null;

        if ($sourceType === TransferConstant::PAYMENT)
        {
            $sourcePayment = $transfer->source;
        }
        else if ($sourceType === TransferConstant::ORDER)
        {
            $orderId = $transfer->getSourceId();

            $apiPayments = $this->repo->payment->fetchPaymentsForOrderId($orderId, $transfer->merchant->getId());

            $rearchPayments = $this->app['pg_router']->fetchOrderPayments($orderId, $transfer->merchant->getId());

            $allPayments = $apiPayments->merge($rearchPayments);

            foreach ($allPayments as $payment)
            {
                if (($payment->getStatus() === Payment\Status::CAPTURED) || ($payment->getStatus() === Payment\Status::REFUNDED))
                {
                    $sourcePayment = $payment;

                    break;
                }
            }
        }
        else
        {
            //
            // $sourceType is `merchant` here, meaning this is a direct
            // transfer which does not have an associated source payment.
            //
            return ;
        }

        $meta += [
            'origin_method' => $sourcePayment->getMethod(),
        ];
    }

     /**
     * Returns Settlement Currency of Payment in Case of OPGSP
     * Settlements. Returns NULL in case of gateways not on
     * OGPSP Based Settlements.
     *
     * @param Payment\Entity
     * @return String | null
     */

    private function getSettlementCurrencyOfPayment(Payment\Entity $payment)
    {
        return Payment\Gateway::getSettlementCurrencyOfPaymentByGateway($payment);
    }


    protected function getMetaForSource(Transaction\Entity $txn)
    {
        $type = $txn->getType();

        try
        {
            $txnSource = $txn->source;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SETTLEMENT_SERVICE_TRANSACTION_SOURCE_FETCH_ERROR,
                [
                    'transaction_id' => $txn->getId(),
                    'source_id' => $txn->getEntityId()
                ]);

            if ($type === Transaction\Type::REFUND)
            {
                $txnSource = (new Refund\Repository())->fetchExternalRefundById($txn->getEntityId(), '', [], true);
            }
            else {
                throw $e;
            }
        }

        $international = false;

        switch ($type)
        {
            case Transaction\Type::REFUND:
                $metaSource     = $txnSource->payment;
                $international  = $metaSource->isInternational();
                break;

            case Transaction\Type::TRANSFER:
                if ($txnSource->getSourceType() === Transaction\Type::PAYMENT)
                {
                    $metaSource = $txnSource->source;
                    $international = $metaSource->isInternational();
                }
                else
                {
                    return null;
                }
                break;

            default:
                $metaSource = $txnSource;
        }

        $meta = [
            'source_type'       => $metaSource->getEntity(),
            'source_id'         => $metaSource->getId(),
            'source_method'     => $metaSource->getMethod(),
            'source_settled'    => $metaSource->transaction->isSettled(),
            'international'     => $international
        ];

        if($txn->merchant->isSettlementByCurrencyEnabled() === true && $metaSource->getEntity() === Transaction\Type::PAYMENT)
        {
            $meta += [
                "settlement_by_currency" => true,
                "payment_currency" => $metaSource->getCurrency()
            ];
        }

        if($txn->merchant->isOmniEnabled() === true and $metaSource->getEntity() === Transaction\Type::PAYMENT)
        {
            $meta += [
                "omni_details" => [
                    "enabled" => true,
                    "source_channel" => $metaSource->getSourceChannel()
                ]
            ];
        }

        return $meta;
    }

    /**
     * marks merchant settlement before give time as completed
     * if timestamp is not provided then timestamp is set to current time
     *
     * @param ME\Entity $merchant
     * @param string    $balanceType
     * @param null      $timestamp
     */
    public function markMerchantSettlementAsComplete(ME\Entity $merchant, string $balanceType, $timestamp = null)
    {
        if (empty($timestamp) === true)
        {
            $timestamp = Carbon::now(Timezone::IST)->getTimestamp();
        }

        $this->repo->settlement_bucket->markAsComplete($merchant->getId(), $balanceType, $timestamp);
    }

    /**
     * creates entry in settlement bucket for the merchant id if its not already added to that bucket
     *
     * @param string $merchantId
     * @param int    $bucketTimestamp
     * @param string $balanceType
     * @param string $settlementTime
     *
     * @return bool
     */
    public function addToBucket(
        string $merchantId,
        int $bucketTimestamp,
        string $balanceType,
        $settlementTime = null): bool
    {
        $data = [
            Entity::MERCHANT_ID      => $merchantId,
            Entity::BALANCE_TYPE     => $balanceType,
            Entity::BUCKET_TIMESTAMP => $bucketTimestamp,
        ];

        $traceData = [
                'settled_at' => $settlementTime,
            ] + $data;

        try
        {
            $entity = new Entity;

            $entity->fill($data);

            $entity->save();

            $this->trace->info(
                TraceCode::MERCHANT_ADDED_TO_BUCKET,
                $traceData);

            return true;
        }
        catch (\Throwable $e)
        {
            // todo: use insert ignore or ignore this error
        }

        return false;
    }

    /**
     * check if the settlement should be skipped for the merchant because it is
     * being processed by the new service
     * @param string $merchantId
     * @param $balance
     * @return bool
     */
    public function shouldProcessViaNewService(string $merchantId, $balance = null)
    {
        $result = $this->repo->feature->getMerchantIdsHavingFeature(
            Feature\Constants::NEW_SETTLEMENT_SERVICE,
            [
                $merchantId
            ]);

        // TODO remove this when we migrate to the yes bank to new settlement service

        $balanceType = ($balance == null) ? Balance\Type::PRIMARY : $balance->getType();

        $status = (empty($result) === false);

        $this->trace->info(
            TraceCode::SETTLEMENT_SERVICE_RAMP,
            [
                'merchant_id'  => $merchantId,
                'status'       => $status,
                'balance_type' => $balanceType,
            ]);

        return $status;
    }

    public function fetchAndEnqueueSettlableTransactionsBatch(string $mode, string $merchantId, array $opt)
    {
        $startTime = microtime(true);

        $batch = 0;
        $batchSize = 1000;

        $balance = $this->repo->balance->getMerchantBalanceByType($merchantId, $opt['balance_type']);

        $transactions = $this->repo->transaction->getSettlableTransactions($merchantId, $opt, $balance, true);

        $transactionIds = $transactions->getIds();

        $txnCount = sizeof($transactionIds);

        $transactionIdBatches = array_chunk($transactionIds, $batchSize);

        $pushJobStart= microtime(true);

        $totalPushTimeTaken=0;

        $this->trace->info(
            TraceCode::SETTLEMENT_SERVICE_TRANSACTION_MIGRATION_DEBUG_LOG,
            [
                'merchant_id'            => $merchantId,
                'transactions_count'     => $txnCount,
                'push_job_start_time'    => $pushJobStart,
            ]);

        foreach ($transactionIdBatches as $transactionIdBatch)
        {
            $opt['transaction_ids'] = $transactionIdBatch;

            $currentPushStartTime = microtime(true);

            TransactionMigrationPublish::dispatch($mode, $merchantId, $opt);

            $timeforCurrentPush = microtime(true) - $currentPushStartTime;

            $totalPushTimeTaken=$totalPushTimeTaken+$timeforCurrentPush;

            $batch++;

            $this->trace->info(
                TraceCode::SETTLEMENT_SERVICE_TRANSACTION_MIGRATION_DEBUG_LOG,
                [
                    'merchant_id'            => $merchantId,
                    'batch_number'           => $batch,
                    'current_batch_push_time'=> $timeforCurrentPush,
                    'current_time_taken'     => $totalPushTimeTaken
                ]);
        }

        $this->trace->info(
            TraceCode::SETTLEMENT_SERVICE_TRANSACTION_MIGRATION_BATCH_ENQUEUE_TIME_TAKEN,
            [
                'merchant_id'            => $merchantId,
                'batch_count'            => $batch,
                'transactions_count'     => $txnCount,
                'time_taken'             => microtime(true) - $startTime,
                'total_push_time_taken'  => $totalPushTimeTaken,
            ]);

        return [
            'batch_count' => $batch,
            'transactions_count' => $txnCount,
        ];
    }

    public function migrateSettlableTransactionsBatch(string $merchantId, array $opt)
    {
        $stat = [
            'total_count' => 0,
        ];

        $balance = $this->repo->balance->getMerchantBalanceByType($merchantId, $opt['balance_type']);

        $transactions = $this->repo->transaction->getSettlableTransactions($merchantId, $opt, $balance);

        $startTime = microtime(true);

        foreach($transactions as $txn)
        {
            if (isset($stat[$txn->getType()]) === false) {
                $stat[$txn->getType()] = [
                    'count'  => 0,
                    'amount' => 0,
                ];
            }

            $stat['total_count']++;
            $stat[$txn->getType()]['count']++;
            $stat[$txn->getType()]['amount'] += $txn->getCredit() - $txn->getDebit();

            try
            {
                $this->publishForSettlement($txn, $balance, $opt['initial_ramp']);
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::SETTLEMENT_SERVICE_TRANSACTION_MIGRATION_FAILED,
                    [
                        'merchant_id'    => $merchantId,
                        'transaction_id' => $txn->getId(),
                    ]);
            }
        }

        $this->trace->info(
            TraceCode::SETTLEMENT_SERVICE_TRANSACTION_MIGRATION_BATCH_PUBLISH_TIME_TAKEN,
            [
                'merchant_id' => $merchantId,
                'txn_count'   => $transactions->count(),
                'time_taken'  => microtime(true) - $startTime,
            ]);

        return $stat;
    }

    /**
     * This is used to call the settlements api based on the transaction hold and release
     * @param array $txnIds
     * @param string $reason
     */
    public function settlementServiceToggleTransactionHold($txnIds = [], $reason = null)
    {
        try
        {
            if ($reason != null)
            {
                app('settlements_api')->transactionHold($txnIds, $reason);
            }
            else
            {
                app('settlements_api')->transactionRelease($txnIds);
            }

            return ['success' => true];
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SETTLEMENT_SERVICE_CALL_FOR_TXN_ON_HOLD_CLEAR_FAILED,
                [
                    'transaction_ids'    => $txnIds,
                    'reason_for_hold'    => $reason,
                ]);

            $operation = 'Transactions on hold toggle failed to update in new settlement service';

            (new SlackNotification)->send(
                $operation,
                $txnIds,
                $e,
                1,
                'settlement_alerts');
        }

        return ['success' => false];
    }
}
