<?php

namespace RZP\Tests\P2p\Service\Base;

class DeviceHelper extends P2pHelper
{
    public function postCreateDevice(array $content = [])
    {
        $this->validationJsonSchemaPath = 'device/create';

        $request = $this->request('devices');

        $default = [
            'ip'               => '179.0.0.1',
            'os'               => 'android',
            'os_version'       => '5.0.1',
            'sim_id'           => '683729232343',
            'uuid'             => '5637293534543',
            'type'             => 'mobile',
            'geo_code'         => '12.971599,77.594566',
            'app_name'         => 'com.razorpay',
            'cl.capability'    => '52000002000100040006',
            'cl.challenge'     => 'AUnhIkGYnGBK==',
            'token'            => 'AikxOldnJmaUbdsmHdsnaudjeGHndshsjSildsmfyneHDBd'
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function fetchDevice()
    {
        $this->validationJsonSchemaPath = 'device/create';

        $request = $this->request('devices');

        return $this->get($request);
    }

    public function refreshClToken(array $content = [])
    {
        $this->validationJsonSchemaPath = 'device/create';

        $request = $this->request('devices/cl_token_refresh');

        $default = [
            'cl.challenge'  => 'AikxOldnJmaUbdsmHdsnaudjeGHndshsjSildsmfyneHDBd'
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function deleteDevice()
    {
        $this->validationJsonSchemaPath = 'device/delete';

        $request = $this->request('devices');

        return $this->delete($request);
    }
}
