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

    public function getCallbackUrlWithParams() {
        $allParams = Request::all();

        $data = [
                    'back' => $allParams['back'],
                    'params' => json_decode($allParams['params']),
                    'url' => $allParams['url']
                ];

        if (isset($allParams['razorpay_payment_id'])) {
            $data['payment_id'] = $allParams['razorpay_payment_id'];
        }
        else {
            // fill basic error codes if it does not exist.
            if (!isset($allParams['error']) || !isset($allParams['error']['description'])) {
                $allParams['error'] = [
                    'description' => 'Something went wrong'
                ];
            }
            $data['error'] = $allParams['error']['description'];

            // passing as json encoded string
            $data['options'] = $allParams['options'];
        }

        return View::make('public.callback_params', $data);
    }
}
