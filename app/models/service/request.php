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

    public function process($verb = 'GET', $url, $data = [])
    {
        $options = ['auth' => [$this->ID,$this->PASSWORD]];

        $response = \Requests::request(\Config::get('api.url').$url, array(), $data, $verb, $options);
        
        $array = json_decode($response->body, true);
        
        if(isset($array['error']['message'])) 
        {
            echo $response->body; die();
        }

        return $array;
    }
}
