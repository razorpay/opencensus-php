<?php

namespace Models\Service;

use Razorpay\Api\Api;

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

    public static function setApiCredentials($merchant_id = NULL, $secret = 'a128a3994372ccd2a63a8a64202a92e04eb83e54')
    {
        static::$api = new Api($merchant_id, $secret);
    }
}