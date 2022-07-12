<?php

namespace RZP\Models\Merchant\OneClickCheckout\FulfillmentOrder;


use DateTime;
use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;
use RZP\Models\Batch\Header;
use RZP\Exception\BadRequestValidationFailureException;

class Service
{
    protected $app;

    const UPDATE_FULFILLMENT_ORDER             = 'update_fulfillment_order';
    const PATH                                 = 'path';

    const PARAMS = [
        self::UPDATE_FULFILLMENT_ORDER  =>   [
            self::PATH   => 'twirp/rzp.shipping.fulfillment_order.v1.FulfillmentOrderAPI/UpdateByMerchantOrderID',
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

    public function updateOrder($input, $merchantId)
    {

        $input = $this->addMerchantDetails($input, $merchantId);

        if (isset($input[Header::FULFILLMENT_ORDER_STATUS]) == true)
        {
            $input[Header::FULFILLMENT_ORDER_STATUS] = strtolower($input[Header::FULFILLMENT_ORDER_STATUS]);
        }

        $input = $this->addShippingDetails($input);

        $params = self::PARAMS[self::UPDATE_FULFILLMENT_ORDER];

        return $this->app['shipping_service_client']->sendRequest($params[self::PATH], $input, Requests::POST);
    }

    protected function addMerchantDetails($input, $merchantId)
    {

        $input['merchant_id'] = $merchantId;
        $input['source']['origin'] = "batch";

        return $input;
    }

    protected function addShippingDetails($input)
    {
        $input['shipping_provider']['provider_type'] = $input[Header::FULFILLMENT_ORDER_SHIPPING_PROVIDER_NAME];
        $input['shipping_provider']['awb_number'] = $input[Header::FULFILLMENT_ORDER_AWB_NUMBER];

        if (!empty($input[Header::FULFILLMENT_ORDER_SHIPPING_CHARGES]))
        {

            if (!is_numeric($input[Header::FULFILLMENT_ORDER_SHIPPING_CHARGES]))
            {
                throw new BadRequestValidationFailureException('shipping_charges: should be numeric');
            }

            $input['shipping_provider'][Header::FULFILLMENT_ORDER_SHIPPING_CHARGES]
                = $input[Header::FULFILLMENT_ORDER_SHIPPING_CHARGES];
        }

        return $input;
    }
}
