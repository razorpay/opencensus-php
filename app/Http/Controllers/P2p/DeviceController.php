<?php

namespace RZP\Http\Controllers\P2p;

use RZP\Models\P2p;

/**
 * @property  P2p\Device\Service $service
*/
class DeviceController extends Controller
{
    public function startVerification()
    {
        $input = $this->request()->all();

        $response = $this->service->startVerification($input);

        return $this->response($response);
    }

    public function getVerificationStatus()
    {
        $input['token'] = $this->request()->route('token');

        $response = $this->service->getVerificationStatus($input);

        return $this->response($response);
    }

    public function refreshClToken()
    {
        $input = $this->request()->all();

        $response = $this->service->refreshClToken($input);

        return $this->response($response);
    }

    public function deregister()
    {
        $input = $this->request()->all();

        $response = $this->service->deregister($input);

        return $this->response($response);
    }
}
