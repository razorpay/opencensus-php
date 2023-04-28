<?php

namespace RZP\Models\Merchant\ShippingInfo;

use RZP\Models\Base;
use RZP\Models\Merchant\Metric;
use RZP\Models\Merchant\OneClickCheckout\ShippingMethodProvider\Constants;
use RZP\Models\Merchant\OneClickCheckout\ShippingMethodProvider\Type;

class Providers extends Base\Core
{
    public function shippingResponseFromShippingProviderConfig(
        $orderId,
        $order,
        $orderMeta,
        $address,
        $shippingMethodProviderConfig,
        ?string $shippingVariant
    ): array
    {
        $shippingMethodProviderConfigJson = $shippingMethodProviderConfig->getValueJson();
        $shippingProviderType = $shippingMethodProviderConfigJson[Constants::PROVIDER_TYPE] ?? Type::SHIPROCKET;
        $this->trace->count(Metric::SHIPPING_SERVICE_CALL_COUNT, ['mode' => $this->mode ]);
        $notes = $order->toArrayPublic()['notes'];
        $updatedNotes = empty($notes) === false ? $notes : (object)[];
        switch ($shippingProviderType)
        {
            case Type::DEMO:
            case Type::RAZORPAY:
                $decodedResponse = $this->getShippingMethods(
                    $shippingMethodProviderConfigJson,
                    $address,
                    $orderId,
                    $orderMeta->getValue()['line_items_total'],
                    $updatedNotes,
                    $shippingVariant
                );
                break;
            default:
                $decodedResponse = $this->getShippingInfoForShippingMethodProvider($shippingMethodProviderConfig,
                    $address,
                    $orderId);
                break;
        }
        return $decodedResponse;
    }


    protected function getShippingMethods(
        $shippingMethodProviderConfig,
        $address,
        $orderId,
        $lineItemsTotal,
        $notes,
        ?string $shippingVariant
    ): array
    {
        $address['country_code'] = $address['country'];
        unset($address['country']);

        $address['zip_code'] = $address['zipcode'];
        unset($address['zipcode']);

        $shippingInfo = $this->app['shipping_methods_service']->evaluate(
            $shippingMethodProviderConfig['shipping_provider_id'],
            $address,
            $lineItemsTotal,
            $orderId,
            $this->merchant->getId(),
            $notes,
            $shippingVariant
        );
        return $this->parseShippingServiceResponse($address, $shippingInfo);
    }

    /**
     * This method calls new API of 1cc-shipping-service
     * It will get shipping info for all the merchants except shopify merchants.
     */
    public function shippingProviderMigrationFlow(
        $shippingMethodProviderConfig,
        $address,
        $orderId,
        $lineItemsTotal,
        $notes,
        $merchantOrderId,
        ?string $shippingVariant
    ): array
    {
        $address['country_code'] = $address['country'];
        unset($address['country']);

        $address['zip_code'] = $address['zipcode'];
        unset($address['zipcode']);

        $pickupLocation = [];

        if ($shippingMethodProviderConfig !== null)
        {
            $shippingMethodProvider = $shippingMethodProviderConfig->getValueJson();
            if (empty($shippingMethodProvider) === false &&
                empty($shippingMethodProvider[Constants::WAREHOUSE_PINCODE]) === false) {
                $pickupLocation = [
                    'zip_code' => $shippingMethodProvider[Constants::WAREHOUSE_PINCODE],
                    'country_code' => 'IN'
                ];
            }
        }
        $updatedNotes = empty($notes) === false ? $notes : (object)[];
        $this->trace->count(Metric::SHIPPING_SERVICE_CALL_COUNT, ['mode' => $this->mode ]);
        $shippingInfo = $this->app['shipping_methods_service']->get(
            $address,
            $pickupLocation,
            $lineItemsTotal,
            $orderId,
            $this->merchant->getId(),
            $updatedNotes,
            $merchantOrderId,
            $shippingVariant
        );

        return $this->parseShippingServiceResponse($address, $shippingInfo);
    }

    /**
     * @param $address
     * @param array $shippingInfo
     * @return mixed
     */
    protected function parseShippingServiceResponse($address, array $shippingInfo)
    {
        $address['country'] = $address['country_code'];
        unset($address['country_code']);

        $address['zipcode'] = $address['zip_code'];
        unset($address['zip_code']);

        $keys = [
            'shipping_fee',
            'serviceable',
            'cod',
            'cod_fee',
            'name',
            'description',
            'id',
        ];

        if (empty($shippingInfo['shipping_methods']) === false)
        {
        // Filling empty values as protobuf omits empty fields and converting strings to ints due to protobuf serialization
        for ($i = 0; $i < count($shippingInfo['shipping_methods']); $i++)
        {
            foreach ($keys as $key)
            {
                if (isset($shippingInfo['shipping_methods'][$i][$key]) === false)
                {
                    switch ($key)
                    {
                        case 'id':
                        case 'name':
                            $shippingInfo['shipping_methods'][$i][$key] = 'default';
                            break;
                        case 'description':
                            $shippingInfo['shipping_methods'][$i][$key] = '';
                            break;
                        case 'cod_fee':
                        case 'shipping_fee':
                            $shippingInfo['shipping_methods'][$i][$key] = 0;
                            break;
                        case 'cod':
                        case 'serviceable':
                            $shippingInfo['shipping_methods'][$i][$key] = false;
                            break;
                    }
                }
                elseif ($key === 'shipping_fee' or $key === 'cod_fee')
                {
                    $shippingInfo['shipping_methods'][$i][$key] = (int)$shippingInfo['shipping_methods'][$i][$key];
                }
            }
        }
        }
        return array_merge($address, $shippingInfo);
    }

    protected function getShippingInfoForShippingMethodProvider($shippingMethodProviderEntity, $address, $orderId): array
    {

        $shippingMethodProvider = $shippingMethodProviderEntity->getValueJson();
        $merchantId = $this->merchant->getId();
        $input = [
            'order_id' => $orderId,
            'address' => $address
        ];
        $shippingInfo = $this->app['shipping_method_provider_service']
            ->getShippingInfoForAddress($shippingMethodProvider, $input, $merchantId);

        return array_merge($input['address'], $shippingInfo);
    }
}
