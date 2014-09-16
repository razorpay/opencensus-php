<?php

namespace Models\Service;

use RZP\Api;
use Config;

class Service
{
    protected $api;

    public function __construct()
    {
        ;
    }


    public function setApiCredentials($merchant_id = NULL, $mode = 'live')
    {
        $id = 'rzp_'.$mode;

        if($merchant_id)
            $id = $id.'_'.$merchant_id;

        $secret = Config::get('api.auth_pass');

        $this->api = new Api($id, $secret);
    }
}