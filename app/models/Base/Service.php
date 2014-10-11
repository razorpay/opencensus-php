<?php

namespace Models\Base;

use Config;
use RZP\Api;

class Service
{
    public function setApiCredentials($merchant_id = null, $mode = 'live')
    {
        $merchantId = \Auth::merchant()->id();

        $id = 'rzp_'.$mode;

        if ($merchant_id)
        {
            $id = $id.'_'.$merchant_id;
        }

        $secret = Config::get('api.auth_pass');

        $this->api = new Api($id, $secret);
    }
}