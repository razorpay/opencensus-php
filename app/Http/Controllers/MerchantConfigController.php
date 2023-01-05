<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class MerchantConfigController extends Controller
{

    protected function create()
    {
        $input = Request::all();
        $response = $this->app['shipping_service_merchant_config']->create($input);
        return ApiResponse::json($response);
    }

    protected function updateByType()
    {
        $input = Request::all();
        $response = $this->app['shipping_service_merchant_config']->updateByType($input);
        return ApiResponse::json($response);
    }

    protected function removeShippingProvider($merchantId)
    {
        $response = $this->app['shipping_service_merchant_config']->removeShippingProviders($merchantId);
        return ApiResponse::json($response);
    }

    protected function assignShopifyAsShippingProvider()
    {
        $input = Request::all();
        $response = $this->app['shipping_service_merchant_config']->assignShopifyAsShippingProvider($input);
        return ApiResponse::json($response);
    }

    protected function disableShopifyAsShippingProvider()
    {
        $input = Request::all();
        $response = $this->app['shipping_service_merchant_config']->disableShopifyAsShippingProvider($input);
        return ApiResponse::json($response);
    }

}
