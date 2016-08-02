<?php

namespace App\RZP;

use Exception;

use Razorpay\Api\Request as BaseRequest;

/**
 * Request class to communicate to the request libarary
 */
class Request extends BaseRequest
{
    protected $options = [];

    public function setOption($key, $value)
    {
        $this->options[$key] = $value;
    }

    public function request($method, $url, $data = null)
    {
        $url = Api::$baseUrl . $url;

        if ($data === null)
            $data = array();

        $this->setOption('auth', [Api::$key, Api::$secret]);

        $response = \Requests::request($url, self::$headers, $data, $method, $this->options);

        $this->checkErrors($response);

        return json_decode($response->body, true);
    }
}
