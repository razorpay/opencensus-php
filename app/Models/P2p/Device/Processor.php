<?php

namespace RZP\Models\P2p\Device;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Processor extends Base\Processor
{
    public function create(array $input): array
    {
        $this->initialize(Action::CREATE, $input);

        unset($input['token']);
        $input['created_at'] = time();
        $input['refreshed_at'] = time();

        return array_merge([
            'id'            => 'device_charpanchkhoye',
            'contact'       => '+919876543210',
            'handle'        => 'razorsharp',
            'cl.token'      => null,
            'cl.payload'    => null,
        ], $input);
    }

    public function fetch(array $input): array
    {
        $this->initialize(Action::FETCH, $input);

        $input['created_at'] = time();
        $input['refreshed_at'] = time();

        return array_merge([
            'id'               => $id,
            'contact'          => '+919876543210',
            'handle'           => 'razorsharp',
            'ip'               => '179.0.0.1',
            'os'               => 'android',
            'os_version'       => '5.0.1',
            'sim_id'           => '683729232343',
            'uuid'             => '5637293534543',
            'type'             => 'mobile',
            'geo_code'         => '12.971599,77.594566',
            'app_name'         => 'com.razorpay',
            'cl.token'         => '52000002000100040006',
            'cl.payload'       => 'AUnhIkGYnGBK==',
        ], $input);
    }

    public function refreshClToken(array $input): array
    {
        $this->initialize(Action::REFRESH_CL_TOKEN, $input);

        return [];
    }

    public function delete(array $input): array
    {
        $this->initialize(Action::DELETE, $input);

        return [
            'id'               => $id,
            'success'          => true,
        ];
    }
}
