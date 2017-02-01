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

    public function postCallbackUrlWithParams()
    {
        $allParams = Request::all();

        $data = [
            // merchant site url, to create "go back to merchant website" link
            // this is required because otherwise there is no escape for
            // customer until payment is successful.
            // This is automatically picked from previous page.
            'back' => $allParams['back'],

            // parameters to be converted into POST.
            // Reason we're going through this maneuver
            'params' => json_decode($allParams['params']),

            // actual callback_url, picked from checkout page form action
            'url' => $allParams['url']
        ];

        if (isset($allParams['razorpay_payment_id']))
        {
            $data['payment_id'] = $allParams['razorpay_payment_id'];

            if (isset($allParams['razorpay_order_id']) === true)
            {
                $data['razorpay_order_id'] = $allParams['razorpay_order_id'];
                $data['razorpay_signature'] = $allParams['razorpay_signature'];
            }
        }
        else
        {
            // Fill basic error codes if it error parameter does not exist.
            if ((isset($allParams['error']) === false) or
                (isset($allParams['error']['description']) === false))
            {
                $allParams['error'] = ['description' => 'Something went wrong'];
            }

            // just pass in printable error.
            $data['error'] = $allParams['error']['description'];

            // Razorpay frontend initialization object as json string
            $data['options'] = $allParams['options'];
        }

        return View::make('public.callback_params', $data);
    }
}
