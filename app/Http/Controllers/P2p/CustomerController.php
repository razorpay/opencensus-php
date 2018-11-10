<?php

namespace Rzp\Http\Controllers\P2p;

use RZP\Models\P2p;

/**
 * @property $service P2p\Customer\Service
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
        $input = $this->request()->all();

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
        $input = $this->request()->all();

        $response = $this->service->delete($input);

        return $this->response($response);
    }
}
