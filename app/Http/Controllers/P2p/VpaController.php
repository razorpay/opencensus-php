<?php

namespace RZP\Http\Controllers\P2p;

use RZP\Models\P2p;

/**
 * @property  P2p\Vpa\Service $service
*/
class VpaController extends Controller
{
    public function fetchHandles()
    {
        $input = $this->request()->all();

        $response = $this->service->fetchHandles($input);

        return $this->response($response);
    }

    public function create()
    {
        $input = $this->request()->all();

        $response = $this->service->add($input);

        return $this->response($response);
    }

    public function fetchAll()
    {
        $input = $this->request()->all();

        $response = $this->service->fetchAll($input);

        return $this->response($response);
    }

    public function fetch()
    {
        $input = $this->request()->all();

        $response = $this->service->fetch($input);

        return $this->response($response);
    }

    public function assignBankAccount()
    {
        $input = $this->request()->all();

        $response = $this->service->assignBankAccount($input);

        return $this->response($response);
    }

    public function checkAvailability()
    {
        $input = $this->request()->all();

        $response = $this->service->checkAvailability($input);

        return $this->response($response);
    }

    public function delete()
    {
        $input = $this->request()->all();

        $response = $this->service->delete($input);

        return $this->response($response);
    }
}
