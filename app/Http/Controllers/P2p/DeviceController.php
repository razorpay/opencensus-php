<?php

namespace RZP\Http\Controllers\P2p;

class DeviceController extends Controller
{
    public function create()
    {
        $input = $this->request()->all();

        unset($input['token']);
        $input['created_at'] = time();

        return $this->response(array_merge([
            'id'            => 'device_charpanchkhoye',
            'contact'       => '+919876543210',
            'handle'        => 'razorsharp'
        ], $input));
    }
}
