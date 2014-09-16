<?php

namespace RZP;

use Razorpay;

class Api extends Razorpay\Api\Api
{
    /**
     * @param string $api_key
     */
    function __construct($key, $secret)
    {
        self::$baseUrl = $_ENV['API_URL'];
        parent::__construct($key, $secret);
    }

    
    /**
     * @param string $name
     */
    function __get($name)
    {
        $className = __NAMESPACE__.'\\'.ucwords($name);

        if(class_exists($className) === true)
        {
            $entity = new $className();
        }
        else
        {
            $entity = parent::__get($name);
        }

        return $entity;
    }
}