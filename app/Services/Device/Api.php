<?php

namespace RZP\Services\Device;

use RZP\Http\Request\Requests;

class Api extends Base
{
    const FETCH_STORE_URL = '/api/3.0/rzp_app/devices/%s/info';

    /**
     * To fetch a store by device_id from POS Device microservice
     * @param string $deviceId
     * @return array
     */
    public function fetchDevice(string $deviceId) : array
    {
        $config = config('applications.ezetap-api');
        $baseUrl = $config['url'];

        $url = sprintf($baseUrl.self::FETCH_STORE_URL, $deviceId);

        return $this->sendRequest(Requests::GET, $url);
    }
}
