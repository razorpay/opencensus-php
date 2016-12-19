<?php

namespace RZP\Gateway\Upi\Idfc;

use SoapClient as BaseSoapClient;

class SoapClient extends BaseSoapClient
{
    protected $hmacActions = [

    ];

    public function __doRequest($request, $location, $action, $version, $one_way = null)
    {
        $location = 'http://idfcupitest.fssnet.co.in/UPIUATService';

        if (in_array($action, $this->hmacActions) === true)
        {
            $request = $this->addHmac($request);
        }

        return parent::__doRequest($request, $location, $action, $version, $one_way);
    }

    protected function addHmac($request)
    {
        return $request;
    }
}