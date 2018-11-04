<?php

namespace RZP\Tests\P2p\Service\Base;

class DeviceHelper extends P2pHelper
{
    public function postCreateDevice(array $content = [])
    {
        $this->validationJsonSchemaPath = 'device/create';

        $request = $this->request('customers/%s/devices', ['cust_'.Constants::LOCAL_CUSTOMER]);

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
}
