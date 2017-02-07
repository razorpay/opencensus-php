<?php

namespace RZP\Http\Controllers;

use View, Request;

class PublicController extends Controller
{
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
        $data = json_decode(base64_decode($getParams), true);

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
            throw new Exception\ServerErrorException('Should not have reached here');
        }

        $checkout = $this->getCheckoutCommon();
        $data['checkout'] = $checkout['checkout'] . '/v1/checkout.js';

        return View::make('public.callback_params', $data);
    }
}
