<?php

namespace Models\Service;

use Razorpay\Api\Api;
use Config;

class Service
{
    protected static $api;

    public function __construct()
    {
        ;
    }

    public static function getInstance()
    {
        return new static;
    }

    public static function setApiCredentials($merchant_id = NULL)
    {
        $secret = Config::get('api.auth_pass');
        static::$api = new Api($merchant_id, $secret);
    }
}