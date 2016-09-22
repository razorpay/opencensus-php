<?php

namespace RZP\Http\Controllers;

use View;

class PublicController extends Controller
{
    public function getAccount()
    {
        $data = [
            'static'    => 'https://cdn.razorpay.com/account',
            'checkout'  => 'https://checkout.razorpay.com',
            'api'       => $this->config->get('app.url'),
        ];

        return View::make('public.account', $data);
    }
}
