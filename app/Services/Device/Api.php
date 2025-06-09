<?php

namespace RZP\Services\Device;

use RZP\Http\Request\Requests;

class Api extends Base
{
    const FETCH_STORE_URL = '/api/3.0/devices/%s/info';

    /**
     * To fetch a store by device_id from POS Device microservice
     * @param string $deviceId
     * @return array
     */
    public function fetchDevice(string $deviceId) : array
    {
        $baseUrl = config('applications.ezetap_device_gatway.url');

        $url = sprintf($baseUrl.self::FETCH_STORE_URL, $deviceId);

        //return $this->sendRequest(Requests::GET, $url);
        return $this->sendRequest(Requests::GET, $url);
    }
}
