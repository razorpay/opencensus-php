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

        if (isset($input[Header::FULFILLMENT_ORDER_UPDATED_AT]) === true && $input[Header::FULFILLMENT_ORDER_UPDATED_AT] !== '')
        {
            $input['source'][Header::FULFILLMENT_ORDER_UPDATED_AT] = $this->validateAndConvertDateFormat($input[Header::FULFILLMENT_ORDER_UPDATED_AT]);
            unset($input[Header::FULFILLMENT_ORDER_UPDATED_AT]);
        }

        $params = self::PARAMS[self::UPDATE_FULFILLMENT_ORDER];

        return $this->app['shipping_service_client']->sendRequest($params[self::PATH], $input, Requests::POST);
    }

    protected function addMerchantDetails($input, $merchantId)
    {

        $input['merchant_id'] = $merchantId;
        $input['source']['origin'] = "batch";

        return $input;
    }

    public function validateAndConvertDateFormat($value)
    {
        $expectedFormat = 'd/m/Y';

        $d = DateTime::createFromFormat($expectedFormat, $value);

        if (!$d || $d->format($expectedFormat) !== $value)
        {
            throw new BadRequestValidationFailureException('Invalid Date format, should be d/m/Y');
        }
        return strtotime($value.' Asia/Kolkata');
    }
}
