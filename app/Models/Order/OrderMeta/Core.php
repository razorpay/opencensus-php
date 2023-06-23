<?php

namespace RZP\Models\Order\OrderMeta;

use RZP\Services\Mutex;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Trace\TraceCode;
use RZP\Models\Order\OrderMeta\TaxInvoice\TaxInvoiceTransformer;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Merchant\OneClickCheckout\Core as OneClickCheckoutCore;
use RZP\Models\Merchant\OneClickCheckout\Utils\CommonUtils as OneClickCheckoutUtils;

/**
 * Class Core
 * @package RZP\Models\Order\OrderMeta
 */
class Core extends Base\Core
{
    const MUTEX_PREFIX_1CC = "1cc_order_m:";

    protected $mutex_expiry_ttl;
    protected $mutex_1cc_retries;
    protected $pg_router_ttl;
    /**
     * @var Mutex
     */
    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
        $this->mutex_1cc_retries = $this->app['config']->get('app.magic_checkout.magic_pg_order_mutex_retries');
        $this->mutex_expiry_ttl  = $this->app['config']->get('app.magic_checkout.magic_pg_order_mutex_ttl');
        $this->pg_router_ttl     = $this->app['config']->get('app.magic_checkout.magic_pg_order_call_ttl');
    }

    /**
     * @param Order\Entity $order
     * @param array        $input
     *
     * @return Entity|null
     */
    public function createAndSaveOrderMeta(Order\Entity $order, array $input)
    {
        $this->createAndSave1CCOrderMetaData($order, $input);
        $this->createAndSaveOfflineConfigMetaData($order,$input);
        return $this->createAndSaveTaxInvoice($order, $input);
    }

    protected function createAndSave1CCOrderMetaData(Order\Entity $order, array $input)
    {
        if ($this->merchant === null or $this->merchant->isFeatureEnabled(FeatureConstants::ONE_CLICK_CHECKOUT) === false)
        {
            return null;
        }

        [$order1ccData, $orderInput] = $this->extract1ccFields($input);
        if (empty($order1ccData))
        {
            // In case 1CC enabled merchant is creating non-1cc order.
            return null;
        }
        (new Order1cc\Validator())->validateInput('create1CCOrder', $order1ccData);

        $order1ccData['line_items_total'] = (int) $order1ccData['line_items_total'];

        $orderMetaInput = [
            Entity::ORDER_ID => $order->getId(),
            Entity::TYPE     => Order\OrderMeta\Type::ONE_CLICK_CHECKOUT,
            Entity::VALUE    => $order1ccData,
        ];

        return $this->saveOrderMeta($orderMetaInput);
    }

    protected function acquireAndRelease1ccOrderMutex($orderId, callable $callback)
    {
        $mutexKey = $this->get1ccOrderMutex($orderId);
        return $this->mutex->acquireAndReleaseStrict(
            $mutexKey,
            $callback,
            $this->mutex_expiry_ttl,
            ErrorCode::SERVER_ERROR_MUTEX_RESOURCE_NOT_ACQUIRED,
            $this->mutex_1cc_retries
        );
    }

    public function extract1ccFields(array $input): array
    {
        $order1ccInput = [];
        foreach (Order\OrderMeta\Order1cc\Fields::$dataFields as $key)
        {
            if (isset($input[$key]) === false)
            {
                continue;
            }

            $order1ccInput[$key] = $input[$key];
            unset($input[$key]);
        }
        return [$order1ccInput, $input];
    }


    protected function createAndSaveOfflineConfigMetaData(Order\Entity $order, array $input)
    {
        if ($this->merchant === null or
            isset($input[Type::CUSTOMER_ADDITIONAL_INFO]) === false)
        {
            return null;
        }

        if ($this->merchant->isFeatureEnabled(FeatureConstants::OFFLINE_PAYMENT_ON_CHECKOUT) === false)
        {
            if(isset($input[Type::CUSTOMER_ADDITIONAL_INFO]) === true)
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_FEATURE_NOT_ALLOWED_FOR_MERCHANT);
            }

        }

        $orderOfflineInput = $input[Type::CUSTOMER_ADDITIONAL_INFO];

        if (empty($orderOfflineInput))
        {
            return null;
        }

        $this->validateOfflineAdditionalInfo($orderOfflineInput);

        $orderMetaInput = [
            Entity::ORDER_ID => $order->getId(),
            Entity::TYPE     => Type::CUSTOMER_ADDITIONAL_INFO,
            Entity::VALUE    => $orderOfflineInput,
        ];

        return $this->saveOrderMeta($orderMetaInput);
    }


    /**
     * @param Order\Entity $order
     * @param array        $input
     *
     * @return Entity|null
     */
    protected function createAndSaveTaxInvoice(Order\Entity $order, array & $input)
    {
        if (isset($input[Type::TAX_INVOICE]) === false)
        {
            return;
        }

        $taxInvoiceTransformer = new TaxInvoiceTransformer($order, $input[Type::TAX_INVOICE]);

        if ($taxInvoiceTransformer->preProcess() === false)
        {
            $this->trace->info(
                TraceCode::ORDER_META_TAX_INVOICE_NON_GST_FLOW,
                [
                    'order_id' => $order->getId(),
                    'input'    => $input[Type::TAX_INVOICE],
                ]);

            return;
        }

        $taxInvoice = $taxInvoiceTransformer->transform();

        $orderMetaInput = [
            Entity::ORDER_ID    => $order->getId(),
            Entity::TYPE        => Order\Entity::TAX_INVOICE,
            Entity::VALUE       => $taxInvoice,
        ];

        $orderMeta = $this->saveOrderMeta($orderMetaInput);

        $input[Type::TAX_INVOICE] = $taxInvoice;

        return $orderMeta;
    }

    /**
     * @param array $orderMetaInput
     *
     * @return Entity
     */
    protected function saveOrderMeta(array $orderMetaInput): Entity
    {
        if ($orderMetaInput[Entity::TYPE] === Type::ONE_CLICK_CHECKOUT)
        {
            foreach ($orderMetaInput[Entity::VALUE] as $key => $value)
            {
                if ($key === Order1cc\Fields::CUSTOMER_DETAILS)
                {
                    $orderMetaInput[Entity::VALUE][$key] = $this->app['encrypter']->encrypt($value);
                }
            }
        }

        $orderMeta = (new Entity)->build($orderMetaInput);

        $this->repo->order_meta->saveOrFail($orderMeta);

        $this->trace->info(
            TraceCode::ORDER_META_CREATED,
            [
                'order_meta' => $orderMeta->toArrayTrace(),
            ]);

        return $orderMeta;
    }

    /**
     * Validates if the order is in an active 1CC order, i.e., Not in PAID state.
     * @param string $orderId
     * @throws BadRequestException
     */
    public function validateActive1CCOrderId(string $orderId)
    {
        $order = $this->repo
            ->order
            ->findByPublicIdAndMerchant($orderId, $this->merchant);

        /**
         * For payment_store product, by defauly we want magic checkout to be used
         * explicitly setting feature flag for each merchant is not scalable,
         * hence we added additional checks for payment_store product specifically.
         */
        if ($this->merchant->isFeatureEnabled(FeatureConstants::ONE_CLICK_CHECKOUT) === false && ! Order\ProductType::IsForNocodeApps($order->product_type) )
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_NON_1CC_MERCHANT);
        }

        if ($order->isPaid() === true)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_ORDER_ALREADY_PAID);
        }

        if($order->hasOrderMeta() === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_1CC_ORDER);
        }

        $orderMetas1cc = array_filter(
            $order->orderMetas->all(),
            function ($orderMeta) {
                return $orderMeta->getType() === Type::ONE_CLICK_CHECKOUT;
            }
        );

        if(empty($orderMetas1cc))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_1CC_ORDER);
        }

        $orderMeta1cc = $orderMetas1cc[0];
        if($order->getStatus() === 'placed' && (
                $orderMeta1cc[Order1cc\Fields::MAGIC_PAYMENT_LINK] === null ||
                $orderMeta1cc[Order1cc\Fields::MAGIC_PAYMENT_LINK][Order1cc\Fields::MAGIC_PAYMENT_LINK_STATUS] !== 'pl_created'))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_ORDER_ALREADY_PAID);
        }
    }

    /**
     * @param string $orderId
     * @param array $orderMetaInput
     * @return array
     * @throws ServerErrorException
     */
    public function update1CCOrder(string $orderId, array $orderMetaInput): array
    {
        (new Order1cc\Validator())->validateInput('edit1CCOrder', $orderMetaInput);

        $orderId = Order\Entity::verifyIdAndSilentlyStripSign($orderId);

        // Transaction is not required while reading $order,$orderMeta since we acquired mutex.
        // 1cc orders will not get updated in flows outside 1cc other than payment flow.
        // This code only executes before a payment begins.
        // $order, $orderMeta are updated as required here and saved later.
        // Total Timeout = read [2s] + write [2s] = 4s
        // Mutex TTL = 5s
        $action = function () use ($orderId, $orderMetaInput)
        {
            [$order, $orderMeta] = (function () use ($orderId, $orderMetaInput)
            {
                $order = $this->repo->order->findByIdAndMerchant($orderId, $this->merchant);
                $orderMeta = array_first($order->orderMetas, function ($orderMeta)
                {
                    return $orderMeta->getType() === Type::ONE_CLICK_CHECKOUT;
                });

                $value = $orderMeta->getValue();

                foreach ($orderMetaInput as $key => $val)
                {
                    if ($key === Order\OrderMeta\Order1cc\Fields::CUSTOMER_DETAILS)
                    {
                        foreach ($val as $k => $v)
                        {
                            $value[$key][$k] = $v;
                        }
                        continue;
                    }
                    $value[$key] = $val;
                }

                $value = $this->calculateAndUpdateNetPrice($value);
                $orderMeta->setValue($value);
                $order->setAmount($value[Order1cc\Fields::NET_PRICE]);
                return [$order, $orderMeta];
            })();

            if ($order->isExternal() === true)
            {
                $pgRouterInput = [
                    "amount"      => $order->getAmount(),
                    "order_metas" => [
                        $orderMeta->toArrayPublic(),
                    ],
                ];
                // throws exception on failure. 4 secs timeout
                $this->app['pg_router']->updateInternalOrder($pgRouterInput, $orderId, $this->merchant->getId(), true, $this->pg_router_ttl);
            }
            else
            {
                $this->repo->transaction(function () use ($order, $orderMeta)
                {
                    $this->repo->order_meta->saveOrFail($orderMeta);
                    $this->repo->saveOrFail($order);
                });
            }

            return [$order, $orderMeta];
        };

        /*
         * Acquiring and releasing mutex with
         * 1. Mutex TTL = 5s
         * 2. Retry = 10 tries
         * 3. Retry Min delay = 100ms
         * 4. Retry Max delay = 200ms
         * Max Request Timeout delay = 200 * 10 ms = 2s
         * PG-Router internal order update latecy: https://vajra.razorpay.com/d/ncy_6U5Mz/pg-router-metrics?viewPanel=76&orgId=1&refresh=30s
         * Metric Snapshot https://vajra.razorpay.com/dashboard/snapshot/LH2WV8rspObPRfmF2dWu1G5N5TGIHZSW
         * */
        [$order, $orderMeta] = $this->acquireAndRelease1ccOrderMutex($orderId, $action);

        return array_merge(
            $orderMeta->getValue(),
            [Order\Entity::AMOUNT => $order->getAmount()]);
    }

    /**
     * @param string $orderId
     * @param array $orderMetaInput
     * @return array
     * @throws ServerErrorException
     */
    public function update1CCOrderByMerchantId(string $orderId, array $orderMetaInput,string $merchantId): array
    {
        $this->merchant = $this->repo->merchant->find($merchantId);

        return $this->update1CCOrder($orderId,$orderMetaInput);
    }

    /**
     * @throws ServerErrorException
     */
    public function updateCODIntelligence(string $orderId, array $codIntelligenceInput): array
    {
        $input = [Order1cc\Fields::COD_INTELLIGENCE => $codIntelligenceInput];
        return (new OneClickCheckoutCore)->update1CcOrder($orderId, $input);
    }

    public function validateOfflineAdditionalInfo(array $offlineInfo)
    {
        foreach (Order\OrderMeta\OfflineAdditionalInfo\Fields::$dataFields as $key)
        {

            if ((isset($offlineInfo[$key]) === true) and
                (empty($offlineInfo[$key]) === false)) {
                return true;
            }
        }

        throw new BadRequestException(ErrorCode::BAD_REQUEST_CUSTOMER_ADDITIONAL_INFO_MISSING_KEY_FIELD);

    }

    /**
     * @throws ServerErrorException
     */
    protected function calculateAndUpdateNetPrice(array $value): array
    {
        $shippingFee = $value[Order1cc\Fields::SHIPPING_FEE] ?? 0;
        $lineItemsTotal = $value[Order1cc\Fields::LINE_ITEMS_TOTAL];

        $discount = 0;
        // Only supports 1 promotion
        if (isset($value[Order1cc\Fields::PROMOTIONS]) === true
            and count($value[Order1cc\Fields::PROMOTIONS]) > 0) {

            $promotions = $value[Order1cc\Fields::PROMOTIONS];

            $couponsApplied = (new OneClickCheckoutUtils())->removeGiftCardsFromPromotions($promotions);

            if (count($couponsApplied) > 0) {
                $discount = $couponsApplied[0][Order1cc\Fields::PROMOTIONS_VALUE] ?? 0;
            }
        }

        $minimumCartAmountAllowed = 100;
        $subTotal = $lineItemsTotal + $shippingFee;
        $afterDiscountCartAmount = max(0,$lineItemsTotal-$discount);
        $netPrice = max($minimumCartAmountAllowed, $afterDiscountCartAmount + $shippingFee);

        if (isset($value[Order1cc\Fields::PROMOTIONS]) === true
            and count($value[Order1cc\Fields::PROMOTIONS]) > 0) {

            $appliedGiftCards = (new OneClickCheckoutUtils())->removeCouponsFromPromotions($value[Order1cc\Fields::PROMOTIONS]);

            foreach ($appliedGiftCards as $card) {
              $amountUsedFromGiftCard = $card[Order1cc\Fields::PROMOTIONS_VALUE];
              $netPrice = max($minimumCartAmountAllowed, $netPrice - $amountUsedFromGiftCard);
            }
        }

        $value[Order1cc\Fields::NET_PRICE] = $netPrice;
        $value[Order1cc\Fields::SUB_TOTAL] = $subTotal;

        return $value;
    }

    protected function get1ccOrderMutex(string $orderId) : string
    {
        return self::MUTEX_PREFIX_1CC . $orderId;
    }

}
