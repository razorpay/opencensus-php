<?php

namespace Rzp\Http\Controllers\P2p;

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
        $input = $this->request()->all();

        $response = $this->service->fetch($input);

        return $this->response($response);
    }

    public function refreshClToken()
    {
        $input = $this->request()->all();

        $response = $this->service->refreshClToken($input);

        return $this->response($response);
    }

    public function delete()
    {
        $input = $this->request()->all();

        $response = $this->service->delete($input);

        return $this->response($response);
    }
}
