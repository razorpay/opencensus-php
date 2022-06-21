<?php

namespace RZP\Http\Controllers\P2p;

use RZP\Models\P2p;
use RZP\Services\Pspx;

/**
 * @property  P2p\Device\Service $service
 */
class DeviceController extends Controller
{
    public function initiateVerification()
    {
        $input = $this->request()->all();

        $response = $this->service->initiateVerification($input);

        return $this->response($response);
    }

    public function verification()
    {
        $input = $this->request()->all();

        $input['token'] = $this->request()->route('token');

        $response = $this->service->verification($input);

        return $this->response($response);
    }

    public function initiateGetToken()
    {
        $input = $this->request()->all();

        $response = $this->service->initiateGetToken($input);

        return $this->response($response);
    }

    public function getToken()
    {
        $input = $this->request()->all();

        $response = $this->service->getToken($input);

        return $this->response($response);
    }

    public function deregister()
    {
        $input = $this->request()->all();

        $response = $this->service->deregister($input);

        return $this->response($response);
    }

    public function fetchAll()
    {
        $input = $this->request()->all();

        $response = $this->service->fetchAll($input);

        return $this->response($response);
    }

    public function updateWithAction()
    {
        $input['id'] = $this->request()->route('device_id');
        $input['action'] = $this->request()->route('action');
        $input['data'] = $this->request()->all();

        $response = $this->service->updateWithAction($input);

        return $this->response($response);
    }
}
