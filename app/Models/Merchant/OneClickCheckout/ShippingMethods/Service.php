<?php

namespace RZP\Models\Merchant\OneClickCheckout\ShippingMethods;

use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Merchant;
use RZP\Http\Request\Requests;

class Service extends Base\Service
{
    const SHIPPING_SERVICE_METHODS_EVALUATE_PATH = 'twirp/rzp.shipping.shipping_info_api.v1.ShippingInfoAPI/Evaluate';

    public function evaluate(string $shippingProviderId, array $location, int $lineItemsTotal, string $orderId, string $merchantId)
    {
        $request = [
            'delivery_location' => $location,
            'order'             => [
                'id' => $orderId,
                'line_items_total' => $lineItemsTotal,
            ],
            'merchant_id'       => $merchantId,
            'shipping_provider_id' => $shippingProviderId,
        ];
        return $this->app['shipping_service_client']->sendRequest(self::SHIPPING_SERVICE_METHODS_EVALUATE_PATH, $request, Requests::POST);
    }
}
