<?php

namespace RZP\Http\Controllers;

use View, Request, ApiResponse;
use RZP\Exception;
use RZP\Base\JitValidator;

class PublicController extends Controller
{
    public function getRoot()
    {
        $response['message'] = "Welcome to Razorpay API.";

        return ApiResponse::json($response);
    }

    public function getCatchAllRoute(string $uri = null)
    {
        return ApiResponse::routeNotFound();
    }

    public function getAccount()
    {
        $data = [
            'static'    => $this->config->get('url.cdn.production').'/account',
            'checkout'  => $this->config->get('app.checkout'),
            'api'       => $this->config->get('app.url'),
        ];

        return View::make('public.account', $data);
    }

    /**
     * Works on checkout onyx protocol.
     */
    public function postCallbackUrlWithParams()
    {
        $postParams = Request::instance()->request->all();
        $getParams = Request::query('data');

        // decode base64 string
        $getParams = str_replace(' ', '+', $getParams);
        $data = utf8_json_decode(base64_decode($getParams), true);

        // Relevant info for re-directing to merchant url.

        $data['version'] = $data['version'] ?? 1;

        if (isset($postParams['razorpay_payment_id']))
        {
            $data['request']['method'] = $data['request']['method'] ?? 'GET';
            $data['request']['target'] = $data['request']['target'] ?? '_self';

            //
            // It's successful payment merge all post params
            // with already existing POST params that have been
            // defined by the merchant
            //
            if (isset($data['request']['content']))
            {
                $data['request']['content'] = array_merge(
                    $data['request']['content'], $postParams);
            }
            else
            {
                $data['request']['content'] = $postParams;
            }

            $data['retry'] = false;
        }
        else if (isset($postParams['error']))
        {
            assert (isset($postParams['action']) === false);

            // just pass in error.
            $data['error'] = $postParams['error'];
            $data['retry'] = true;
        }
        else
        {
            throw new Exception\LogicException('Should not have reached here');
        }

        $checkout = $this->getCheckoutCommon();
        $data['checkout'] = $checkout['checkout'] . '/v1/checkout.js';

        return View::make('public.callback_params', $data);
    }

    public function renderCheckoutHosted()
    {
        $params = Request::all();

        $this->validateHostedPostParams($params);

        $options     = json_encode($params['checkout'], JSON_FORCE_OBJECT);
        $checkout    = $this->getCheckoutCommon();
        $checkoutUrl = $checkout['checkout'] . '/v1/checkout.js';
        $urls        = json_encode($params['url'], JSON_FORCE_OBJECT);

        $data = [
            'options'      => $options,
            'checkout'     => $checkoutUrl,
            'urls'         => $urls,                      // used directly in JS side
            'url_callback' => $params['url']['callback'], // Used in PHP
            'retry'        => true,
        ];

        return View::make('public.hosted', $data);
    }

    protected function validateHostedPostParams($params)
    {
        $rules = [
            'url'                   => 'required|array',
            'checkout'              => 'required|array',
            'url.cancel'            => 'sometimes|url',
            'url.callback'          => 'required|url',
            'checkout.key'          => 'required|string|size:23',
            'checkout.order_id'     => 'sometimes|string|size:20',
            'checkout.amount'       => 'required_without:checkout.order_id|integer',
            'checkout.image'        => 'sometimes|url',
            'retry'                 => 'sometimes',
        ];

        (new JitValidator)->rules($rules)->input($params)->validate();
    }
}
