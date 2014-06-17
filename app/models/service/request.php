<?php

namespace Models\Service;

use Requests;

class Request extends Service
{
    private static $ID, $PASSWORD;

    public static function setCredentials($merchant_id = NULL)
    {
        self::$ID = $merchant_id;
        self::$PASSWORD = \Config::get('api.auth_pass');
    }

    public static function POST($url, $data = [])
    {
        $options = ['auth' => [self::$ID,self::$PASSWORD]];
        $response = \Requests::post(\Config::get('api.url') . $url, array(), $data, $options);
        return json_decode($response->body);
    }

    public static function PUT($url, $data = [])
    {
        $options = ['auth' => [self::$ID,self::$PASSWORD]];
        $response = \Requests::put(\Config::get('api.url') . $url, array(), $data, $options);
        return json_decode($response->body);
    }

    public static function GET($url, $data = [])
    {
        $qs = http_build_query($data);
        $options = ['auth' => [self::$ID,self::$PASSWORD]];
        $response = \Requests::get(\Config::get('api.url') . $url . '?' . $qs, array(), $options);
        return json_decode($response->body);
    }
}
