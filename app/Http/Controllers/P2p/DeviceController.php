<?php

namespace RZP\Http\Controllers\P2p;

use RZP\Models\P2p;

/**
 * @property  P2p\Device\Service $service
*/
class DeviceController extends Controller
{
    public function create()
    {
        $input = $this->request()->all();

        $response = $this->service->create($input);

        return $this->response($response);
    }

    public function fetch()
    {
        $input['id'] = 'device_charpanchkhoye';

        $response = $this->service->fetch($input);

        return $this->response($response);
    }

    public function refreshClToken()
    {
        $input['id'] = 'device_charpanchkhoye';

        $response = $this->service->refreshClToken($input);

        return $this->response($response);
    }

    public function delete()
    {
        $input['id'] = 'device_charpanchkhoye';

        $response = $this->service->delete($input);

        return $this->response($response);
    }
}
