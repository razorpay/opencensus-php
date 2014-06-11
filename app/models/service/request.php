<?php

namespace Models\Service;

use Requests;

class Request extends Service
{
    private static $ID, $PASSWORD;

    const API_BASE = 'http://api.razorpay.dev/';

    public static function setCredentials($merchant_id = NULL, $password = 'a128a3994372ccd2a63a8a64202a92e04eb83e54')
    {
        self::$ID = $merchant_id;
        self::$PASSWORD = $password;
    }

    public static function POST($url, $data = [])
    {
        $options = ['auth' => [self::$ID,self::$PASSWORD]];
        $response = \Requests::post(self::API_BASE . $url, array(), $data, $options);
        return json_decode($response->body);
    }
}