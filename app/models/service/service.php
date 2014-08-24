<?php

namespace Models\Service;

use Razorpay\Api\Api;
use Config;

class Service
{
    protected $api;

    public function __construct()
    {
        ;
    }


    public function setApiCredentials($merchant_id = NULL, $mode)
    {
        $secret = Config::get('api.auth_pass');

        $this->api = new Api('rzp_'.$mode.'_'.$merchant_id, $secret);
    }
}