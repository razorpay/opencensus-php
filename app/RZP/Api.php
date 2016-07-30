<?php

namespace App\RZP;

use Razorpay;
use Config;

class Api extends Razorpay\Api\Api
{
    public static $mock = false;

    /**
     * @param string $api_key
     */
    function __construct($key, $secret)
    {
        self::$baseUrl = Config::get('api.url');
        self::$mock = Config::get('api.mock');
        parent::__construct($key, $secret);
    }

    /**
     * @param string $name
     */
    function __get($name)
    {
        if (self::$mock === true)
        {
            // Delay the response by 0.5 secs (avoids issue with non loading of js before calls)
            usleep(1000000);
            $className = __NAMESPACE__ . '\\Mock\\' . ucwords($name);
        }
        else
        {
            $className =  'App\\RZP\\' . ucwords($name);
        }

        if (class_exists($className) === true)
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
