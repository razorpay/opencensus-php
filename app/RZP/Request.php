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
        $url = Api::getBaseUrl() . $url;

        if ($data === null)
            $data = array();

        $this->setOption('auth', [Api::getKey(), Api::getSecret()]);

        $response = \Requests::request($url, self::$headers, $data, $method, $this->options);

        $this->checkErrors($response);

        return json_decode($response->body, true);
    }

    public function rawRequest($method, $url, $data = null)
    {
        $url = Api::getBaseUrl() . $url;

        if ($data === null)
            $data = array();

        $this->setOption('auth', [Api::getKey(), Api::getSecret()]);

        $this->setOption('follow_redirects', false);

        $response = \Requests::request($url, self::$headers, $data, $method, $this->options);

        return $response;
    }
}
