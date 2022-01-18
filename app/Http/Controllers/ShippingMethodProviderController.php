<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class ShippingMethodProviderController extends Controller
{

    protected function list()
    {
        $shippingProviderId = Request::all()['shipping_provider_id'] ?? '';

        $response = $this->app['shipping_method_provider_service']->list($shippingProviderId);
        return ApiResponse::json($response);
    }

    protected function create()
    {
        $input = Request::all();
        $response = $this->app['shipping_method_provider_service']->create($input);
        return ApiResponse::json($response);
    }

    protected function update($id)
    {
        $input = Request::all();
        $response = $this->app['shipping_method_provider_service']->update($input, $id);
        return ApiResponse::json($response);
    }

    protected function delete($id)
    {
        $this->app['shipping_method_provider_service']->delete($id);
        return ApiResponse::json([]);
    }

}
