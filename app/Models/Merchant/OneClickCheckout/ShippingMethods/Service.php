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

    public function evaluate(string $shippingProviderId, array $location, int $lineItemsTotal, string $orderId, string $merchantId, $notes)
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
        ];
        return $this->app['shipping_service_client']->sendRequest(self::SHIPPING_SERVICE_METHODS_EVALUATE_PATH, $request, Requests::POST);
    }

    public function get(array $deliveryLocation, array $pickupLocation, int $lineItemsTotal, string $orderId, string $merchantId, $notes,string $merchantOrderId)
    {
        $cachedData = [
            'delivery_location' => $deliveryLocation,
            'pickup_location' => $pickupLocation,
            'order'             => [
                'id' => explode('_', $orderId)[1],
                'amount' => $lineItemsTotal,
                'weight' => 1,
                'notes' => $notes,
                'receipt' => $merchantOrderId,
            ],
        ];
        $request = [
            'cached_data' =>$cachedData,
            'merchant_id'       => $merchantId,
            'order_id' => explode('_', $orderId)[1],
        ];
        $this->trace->info(TraceCode::SHIPPING_MIGRATION_GET_API_CALL,
            [
                'shipping_request' => $request,
            ]);
        return $this->app['shipping_service_client']->sendRequest(self::GET_SHIPPING_SERVICE_METHODS_PATH, $request, Requests::POST);
    }
}
