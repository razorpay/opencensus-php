<?php

namespace RZP\Models\Merchant\OneClickCheckout\ShippingMethods;

use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Merchant;
use RZP\Http\Request\Requests;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    const SHIPPING_SERVICE_METHODS_EVALUATE_PATH = 'twirp/rzp.shipping.shipping_info_api.v1.ShippingInfoAPI/Evaluate';
    const GET_SHIPPING_SERVICE_METHODS_PATH = 'twirp/rzp.shipping.shipping_info_api.v1.ShippingInfoAPI/Get';

    public function evaluate(
        string $shippingProviderId,
        array $location,
        int $lineItemsTotal,
        string $orderId,
        string $merchantId,
        $notes,
        ?string $shippingVariant
    )
    {
        $request = [
            'delivery_location' => $location,
            'order'             => [
                'id' => explode('_', $orderId)[1],
                'line_items_total' => $lineItemsTotal,
                'notes' => $notes
            ],
            'merchant_id'       => $merchantId,
            'shipping_provider_id' => $shippingProviderId,
            'shipping_variant'  => $shippingVariant,
        ];
        return $this->app['shipping_service_client']->sendRequest(self::SHIPPING_SERVICE_METHODS_EVALUATE_PATH, $request, Requests::POST);
    }

    public function get(
        array $deliveryLocation,
        array $pickupLocation,
        int $lineItemsTotal,
        string $orderId,
        string $merchantId,
        $notes,
        string $merchantOrderId,
        ?string $shippingVariant
    )
    {
        $cachedData = [
            'delivery_location' => $deliveryLocation,
            'order' => [
                'id' => explode('_', $orderId)[1],
                'line_items_total' => $lineItemsTotal,
                'weight' => 1,
                'notes' => $notes,
                'receipt' => $merchantOrderId,
            ],
        ];
        if (empty($pickupLocation) === false) {
            $cachedData['pickup_location'] = $pickupLocation;
        }
        $request = [
            'cached_data' => $cachedData,
            'merchant_id' => $merchantId,
            'order_id' => explode('_', $orderId)[1],
            'shipping_variant'  => $shippingVariant,
        ];
        $this->trace->info(TraceCode::SHIPPING_MIGRATION_GET_API_CALL,
            [
                'shipping_request' => $request,
            ]);
        return $this->app['shipping_service_client']->sendRequest(self::GET_SHIPPING_SERVICE_METHODS_PATH, $request, Requests::POST);
    }

    /**
     * @param array $slabs
     * @return array
     * This method converts api slabs values into shipping-service slabs format
     */
    public function covertToNewSlabFormat(array $slabs): array
    {
        //If slabs list has only 1 item - rule_type flat/free
        if (empty($slabs) === false && count($slabs) === 1) {
            if ($slabs[0]['fee'] === 0) {
                $feeRule = [
                    'rule_type' => 'free',
                    'fee' => 0,
                    'rules' => [],
                ];
            } else {
                $feeRule = [
                    'rule_type' => 'flat',
                    'fee' => $slabs[0]['fee'],
                    'rules' => [],
                ];
            }
        } else {
            $feeRule = [
                'rule_type' => 'slabs',
                'fee' => 0,
            ];
            $rules = [];
            for ($index = 0; $index < count($slabs); $index++) {
                $rules[$index]['fee'] = $slabs[$index]['fee'];
                $rules[$index]['order_amount']['gte'] = $slabs[$index]['amount'];
                if ($index + 1 < count($slabs)) {
                    $rules[$index]['order_amount']['lt'] = $slabs[$index + 1]['amount'] - 1;
                }
            }
            $feeRule['rules'] = $rules;
        }
        return $feeRule;
    }
}
