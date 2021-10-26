<?php

namespace RZP\Models\Order\OrderMeta;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Order\OrderMeta\Order1cc;

class Service extends \RZP\Models\Base\Service
{
    /**
     * Function to update customer details for 1CC Orders.
     * @param string $orderId
     * @param array $input
     * @return array
     * @throws \RZP\Exception\BadRequestException
     */
    public function updateCustomerDetailsFor1CCOrder(string $orderId, array $input): array
    {
        (new Order1cc\Validator())->validateInput('editCustomerDetails', $input);
        (new Core)->validateActive1CCOrderId($orderId);

        $customerInfo = $input[Order1cc\Fields::CUSTOMER_DETAILS];
        $shippingInfo = $this->app['cache']->get(
            $this->getShippingInfoCacheKey(
                $orderId,
                $customerInfo[Order1cc\Fields::CUSTOMER_DETAILS_SHIPPING_ADDRESS]
            )
        );

        if ($shippingInfo === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_SHIPPING_INFO_NOT_FOUND);
        }

        $orderMetaInput = [
            Order1cc\Fields::COD_FEE          => $shippingInfo[Order1cc\Fields::COD_FEE] ?? 0,
            Order1cc\Fields::SHIPPING_FEE     => $shippingInfo[Order1cc\Fields::SHIPPING_FEE] ?? 0,
            Order1cc\Fields::CUSTOMER_DETAILS => $customerInfo,
        ];

        return (new Core)->update1CCOrder($orderId, $orderMetaInput);
    }

    /**
     * Resets fee details and applied promotions for the order in case the orderId is re-used.
     * Applies only for 1CC Orders.
     * @param string $orderId
     * @return array
     * @throws \RZP\Exception\BadRequestException
     */
    public function reset1CCOrder(string $orderId)
    {
        $core = (new Core);
        $core->validateActive1CCOrderId($orderId);

        $core->update1CCOrder(
            $orderId,
            [
                Order1cc\Fields::COD_FEE      => 0,
                Order1cc\Fields::SHIPPING_FEE => 0,
                Order1cc\Fields::PROMOTIONS   => [],
            ]);
    }

    protected function getShippingInfoCacheKey($orderId, $address): string
    {
        $merchantId = $this->merchant !== null ? $this->merchant->getId() : "";
        return "SHIPPING_INFO_"
            . $merchantId
            . "_"
            . $orderId
            . "_"
            . $address['zipcode']
            . "_"
            . $address['country'];
    }
}
