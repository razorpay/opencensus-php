<?php

namespace RZP\Services\Device\Mock;

use RZP\Services\Device\Api;


class ApiMock extends Api
{
    const FETCH_STORE_URL = '/api/3.0/rzp_app/devices/%s/info';

    public function __construct($app)
    {
        parent::__construct($app);
    }

    /**
     * To fetch a store by device_id from POS Device microservice
     * @param string $deviceId
     * @return array
     */
    public function fetchDevice(string $deviceId) : array
    {
        return [
            "store_id"=> "store_123",
            "device_id" => "device_123",
        ];
    }
}
