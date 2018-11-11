<?php

namespace RZP\Http\Controllers\P2p;

use RZP\Models\P2p;

/**
 * @property  P2p\Customer\Service $service
*/
class CustomerController extends Controller
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

    public function create()
    {
        $input = $this->request()->all();

        $response = $this->service->create($input);

        return $this->response($response);
    }

    public function delete()
    {
        $input['id'] = $this->request()->route('id');

        $response = $this->service->delete($input);

        return $this->response($response);
    }
}
