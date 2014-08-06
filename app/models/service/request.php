<?php

namespace Models\Service;

use Requests;

class Request extends Service
{
    private $ID, $PASSWORD;

    public function setCredentials($merchant_id = NULL)
    {
        $this->ID = $merchant_id;
        $this->PASSWORD = \Config::get('api.auth_pass');

        return $this;
    }

    public function POST($url, $data = [])
    {
        $options = ['auth' => [$this->ID,$this->PASSWORD]];
        $response = \Requests::post(\Config::get('api.url') . $url, array(), $data, $options);
        
        $array = json_decode($response->body, true);
        if($array === NULL) $array['error']['description'] = 'API Exception';

        return $array;
    }

    public function PUT($url, $data = [])
    {
        $options = ['auth' => [$this->ID,$this->PASSWORD]];
        $response = \Requests::put(\Config::get('api.url') . $url, array(), $data, $options);
        
        $array = json_decode($response->body, true);
        if($array === NULL) $array['error']['description'] = 'API Exception';

        return $array;
    }

    public function GET($url, $data = [])
    {
        $qs = http_build_query($data);
        $options = ['auth' => [$this->ID,$this->PASSWORD]];
        $response = \Requests::get(\Config::get('api.url') . $url . '?' . $qs, array(), $options);
        
        $array = json_decode($response->body, true);
        if($array === NULL) $array['error']['description'] = 'API Exception';

        return $array;
    }
}
