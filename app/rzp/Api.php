<?php

namespace RZP;

use Razorpay;

class Api extends Razorpay\Api\Api
{
    public static $mock = false;
    /**
     * @param string $api_key
     */
    function __construct($key, $secret)
    {
        self::$baseUrl = $_ENV['API_URL'];
        self::$mock = isset($_ENV['API_MOCK']) ? $_ENV['API_MOCK'] : false;
        parent::__construct($key, $secret);
    }

    
    /**
     * @param string $name
     */
    function __get($name)
    {
        if(self::$mock === true)
        {
            //Delay the response by 0.5 secs (avoids issue with non loading of js before calls)
            usleep(500000);
            $className = __NAMESPACE__.'\\Mock\\'.ucwords($name);
        }
        else
        {
            $className = __NAMESPACE__.'\\'.ucwords($name);
        }

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