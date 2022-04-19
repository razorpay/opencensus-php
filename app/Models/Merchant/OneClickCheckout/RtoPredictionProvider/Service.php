<?php

namespace RZP\Models\Merchant\OneClickCheckout\RtoPredictionProvider;

use Illuminate\Support\Str;
use RZP\Http\Request\Requests;
use RZP\Models\Order;

class Service
{
    protected $app;

    const COD_ELIGIBILITY_EVALUATE             = 'cod_eligibility_api';
    const PATH                                 = 'path';

    const PARAMS = [
        self::COD_ELIGIBILITY_EVALUATE  =>   [
            self::PATH   => 'twirp/rzp.rto_prediction.cod_eligibility.v1.CODEligibilityAPI/Evaluate',
        ],
    ];

    public function __construct($app = null)
    {
        if ($app === null)
        {
            $app = App::getFacadeRoot();
        }

        $this->app = $app;
    }

    public function evaluate($input)
    {
        $input = $this->addOrderDetails($input);

        $params = self::PARAMS[self::COD_ELIGIBILITY_EVALUATE];

        return $this->app['rto_prediction_service_client']->sendRequest($params[self::PATH], $input, Requests::POST);
    }

    private function addOrderDetails(array $input) : array
    {
        $orderId = $input['order_id'];

        $address = $input['address'];

        $orderDetails = (new Order\Service())->fetchByIdInternal($orderId);

        $uniqueId = $orderId . ':' . Str::uuid();

        $rtoServiceRequestContent['id'] = $uniqueId;

        $rtoServiceRequestContent['merchant_id'] = app('basicauth')->getMerchantId();

        $order['id'] = $orderId;

        $order['checkout_id'] = $uniqueId;

        $order['amount'] = $orderDetails['amount'] ?? 0;

        $order['currency'] = $orderDetails['currency'] ?? "";

        $order['created_at'] = $orderDetails['created_at'] ?? 0;

        $order['shipping_address'] = $orderDetails['customer_details']['shipping_address'] ?? [];

        $order['billing_address'] = $orderDetails['customer_details']['billing_address'] ?? [];

        $order['customer']['id'] = $address['contact'] ?? "";

        $order['customer']['phone'] = $address['contact'] ?? "";

        $order['customer']['email'] = $orderDetails['customer_details']['email'] ?? "";

        $order['device'] = $input['device'] ?? [];

        $order['device']['pathname'] = $_SERVER['REQUEST_URI'] ?? "";

        $order['device']['search'] = $_SERVER['QUERY_STRING'] ?? "";

        if (strcmp($address['type'], 'shipping_address') == 0 )
        {
            $order['shipping_address']['id'] = $address['id'] ?? "";

            $order['shipping_address']['line1'] = $address['line1'] ?? "";

            $order['shipping_address']['line2'] = $address['line2'] ?? "";

            $order['shipping_address']['zipcode'] = $address['zipcode'] ?? "";

            $order['shipping_address']['city'] = $address['city'] ?? "";

            $order['shipping_address']['state'] = $address['state'] ?? "";

            $order['shipping_address']['tag'] = $address['tag'] ?? "";

            $order['shipping_address']['country'] = $address['country'] ?? "";

            $order['shipping_address']['name'] = $address['name'] ?? "";
        }

        unset($order['shipping_address']['type']);

        unset($order['shipping_address']['landmark']);

        unset($order['shipping_address']['contact']);

        unset($order['billing_address']['type']);

        unset($order['billing_address']['landmark']);

        unset($order['billing_address']['contact']);

        $rtoServiceRequestContent['input']['order'] = $order;

        return $rtoServiceRequestContent;
    }
}
