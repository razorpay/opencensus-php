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

    public function postCheckoutHosted()
    {
        $postParams = Request::instance()->request->all();

        $checkout = $this->getCheckoutCommon();

        $this->validateHostedPostParams($postParams);

        $data = [
            'options'       => json_encode($postParams['checkout'], JSON_FORCE_OBJECT),
            'checkout'      => $checkout['checkout'] . '/v1/checkout.js',
            // This is used directly in JS side
            'urls'          => json_encode([
                'callback'  => $postParams['url']['callback'],
                'cancel'    => $postParams['url']['cancel'] ?? null,
            ], JSON_FORCE_OBJECT),
            // This is used in PHP
            'url_callback'  => $postParams['url']['callback'],
            'retry'         => true //(bool) Request::get('retry', false) ,
        ];

        return View::make('public.hosted', $data);
    }

    protected function validateHostedPostParams($postParams)
    {
        $postParamRules = [
            'url'                   => 'required|array',
            'checkout'              => 'required|array',
            'url.cancel'            => 'sometimes|url',
            'url.callback'          => 'required|url',
            'checkout.key'          => 'required',
            'checkout.amount'       => 'required|integer',
            'checkout.image'        => 'sometimes|url',
            'retry'                 => 'sometimes'
        ];

        (new JitValidator)->rules($postParamRules)
                          ->input($postParams)
                          ->validate();
    }
}
