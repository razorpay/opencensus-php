<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class ShippingProviderController extends Controller
{

    protected function list()
    {
        $providerType = Request::all()['provider_type'] ?? '';
        $merchantId = $this->ba->getMerchant()->getId();
        $response = $this->app['shipping_provider_service']->list($providerType, $merchantId);
        return ApiResponse::json($response);
    }

    protected function create()
    {
        $input = Request::all();
        $merchantId = $this->ba->getMerchant()->getId();
        $response = $this->app['shipping_provider_service']->create($input, $merchantId);
        return ApiResponse::json($response);
    }

    protected function update($id)
    {
        $input = Request::all();
        $input['id'] = $id;
        $merchantId = $this->ba->getMerchant()->getId();
        $response = $this->app['shipping_provider_service']->update($input, $merchantId);
        return ApiResponse::json($response);
    }

    protected function delete($id)
    {
        //TODO fix this, event from shipping service should do this ideally.
        $response = $this->app['shipping_method_provider_service']->list($id);
        if ($response['count'] == 1)
        {
            $this->app['shipping_method_provider_service']->delete($response['items'][0]['id']);
        }


        $merchantId = $this->ba->getMerchant()->getId();
        $this->app['shipping_provider_service']->delete($id, $merchantId);
        return ApiResponse::json([]);
    }

    protected function connect()
    {
        $input = Request::all();
        $response = $this->app['shipping_provider_service']->connect($input);
        return ApiResponse::json($response);
    }

}
