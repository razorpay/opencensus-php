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
        $getParams = Request::query();

        // Relevant info for re-directing to merchant url.

        $data = [
            // actual callback_url, picked from checkout page form action
            'url'       => $getParams['url'],
            'method'    => $getParams['method'] ?? 'POST',
            'target'    => $getParams['target'] ?? '_self',
            'version'   => $getParams['version'] ?? 1,
            'options'   => $getParams['options'],

            // parameters to be converted into POST.
            // Reason we're going through this maneuver
            'params' => $getParams['params'],
        ];

        if (isset($postParams['razorpay_payment_id']))
        {
            //
            // It's successful payment so pass all post params directly to
            // the merchant url. Merge it with already existing POST params
            // that have been defined by the merchant
            //

            $data['params'] = array_merge($data['params'], $postParams);
            $data['retry'] = false;
        }
        else if (isset($postParams['error']))
        {
            assert (isset($postParams['action']) === false);

            // just pass in error.
            $data['error'] = $postParams['error'];

            // Relevant info for re-opening checkout because we have to give re-try.
            $data = [
                // merchant site url, to create "go back to merchant website" link
                // this is required because otherwise there is no escape for
                // customer until payment is successful.
                // This is automatically picked from previous page.
                'back'  => $getParams['back'] ?? null,

                'options' => $getParams['options'],
            ];
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
