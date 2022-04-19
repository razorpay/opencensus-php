<?php

namespace RZP\Models\Order\OrderMeta;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Trace\TraceCode;
use RZP\Models\Order\OrderMeta\TaxInvoice\TaxInvoiceTransformer;
use RZP\Models\Feature\Constants as FeatureConstants;

/**
 * Class Core
 * @package RZP\Models\Order\OrderMeta
 */
class Core extends Base\Core
{
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

        list($order1ccData, $orderInput) = $this->extract1ccFields($input);
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
        if ($this->merchant->isFeatureEnabled(FeatureConstants::ONE_CLICK_CHECKOUT) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_NON_1CC_MERCHANT);
        }

        $order = $this->repo
            ->order
            ->findByPublicIdAndMerchant($orderId, $this->merchant);

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
    }

    /**
     * @param string $orderId
     * @param array $orderMetaInput
     * @return array
     */
    public function update1CCOrder(string $orderId, array $orderMetaInput): array
    {
        (new Order1cc\Validator())->validateInput('edit1CCOrder', $orderMetaInput);

        $orderMeta = $this->repo->transaction(function () use ($orderId, $orderMetaInput)
        {
            $orderId = Order\Entity::verifyIdAndSilentlyStripSign($orderId);
            $orderMeta = $this->repo->order_meta->findByOrderIdAndType($orderId, Type::ONE_CLICK_CHECKOUT);
            $value = $orderMeta->getValue();

            foreach ($orderMetaInput as $key => $val)
            {
                $value[$key] = $val;
            }

            $value = $this->calculateAndUpdateNetPrice($value);
            $orderMeta->setValue($value);
            $this->repo->order_meta->saveOrFail($orderMeta);

            $order = $this->repo->order->findByIdAndMerchant($orderId, $this->merchant);
            $order->setAmount($value[Order1cc\Fields::NET_PRICE]);
            $this->repo->saveOrFail($order);

            return $orderMeta;
        });

        return array_merge(
            $orderMeta->getValue(),
            [Order\Entity::AMOUNT => $orderMeta->getValue()[Order1cc\Fields::NET_PRICE]]);
    }

    public function updateCODIntelligence(string $orderId, array $codIntelligenceInput): array
    {
        $orderMeta = $this->repo->transaction(function () use ($orderId, $codIntelligenceInput)
        {
            $orderId = Order\Entity::verifyIdAndSilentlyStripSign($orderId);
            $orderMeta = $this->repo->order_meta->findByOrderIdAndType($orderId, Type::ONE_CLICK_CHECKOUT);
            if ($orderMeta === null)
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_1CC_ORDER);
            }
            $value = $orderMeta->getValue();
            $value[Order\OrderMeta\Order1cc\Fields::COD_INTELLIGENCE] = $codIntelligenceInput;
            $orderMeta->setValue($value);
            $this->repo->order_meta->saveOrFail($orderMeta);

            return $orderMeta;
        });

        return $orderMeta->getValue();
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

    protected function calculateAndUpdateNetPrice(array $value): array
    {
        $shippingFee = $value[Order1cc\Fields::SHIPPING_FEE] ?? 0;
        $lineItemsTotal = $value[Order1cc\Fields::LINE_ITEMS_TOTAL];

        $discount = 0;
        // Only supports 1 promotion
        if (isset($value[Order1cc\Fields::PROMOTIONS]) === true
            and count($value[Order1cc\Fields::PROMOTIONS]) > 0)
        {
            $discount = $value[Order1cc\Fields::PROMOTIONS][0][Order1cc\Fields::PROMOTIONS_VALUE];
        }

        $subTotal = $lineItemsTotal + $shippingFee;
        $netPrice = max($subTotal - $discount, 1);

        $value[Order1cc\Fields::NET_PRICE] = $netPrice;
        $value[Order1cc\Fields::SUB_TOTAL] = $subTotal;

        return $value;
    }
}
