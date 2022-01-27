<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Trace\TraceCode;
use RZP\Http\RequestHeader;

class FulfillmentOrderController extends Controller
{

    protected function updateOrder()
    {
        $input = Request::all();
        $merchantId = $this->app['request']->header(RequestHeader::X_ENTITY_ID) ?? null;
        $response = $this->app['fulfillment_order_service']->updateOrder($input, $merchantId);
        return ApiResponse::json($response);
    }
}
