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

    public static function setApiCredentials($key = 'd9c6bf091a1a64cb5678d8c1d5e7360f', $secret = 'thisissupersecret')
    {
        static::$api = new Api($key, $secret);
    }
}