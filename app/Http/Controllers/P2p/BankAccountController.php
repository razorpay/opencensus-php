<?php

namespace RZP\Http\Controllers\P2p;

use RZP\Models\P2p;

/**
 * @property  P2p\BankAccount\Service $service
*/
class BankAccountController extends Controller
{
    public function fetchBanks()
    {
        $input = $this->request()->all();

        $response = $this->service->fetchBanks($input);

        return $this->response($response);
    }

    public function retrieve()
    {
        $input = $this->request()->all();

        $response = $this->service->retrieve($input);

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

    public function initiateSetUpiPin()
    {
        $input = $this->request()->all();

        $response = $this->service->initiateSetUpiPin($input);

        return $this->response($response);
    }

    public function setUpiPin()
    {
        $input = $this->request()->all();

        $response = $this->service->setUpiPin($input);

        return $this->response($response);
    }

    public function initiateFetchBalance()
    {
        $input = $this->request()->all();

        $response = $this->service->initiateFetchBalance($input);

        return $this->response($response);
    }

    public function fetchBalance()
    {
        $input = $this->request()->all();

        $response = $this->service->fetchBalance($input);

        return $this->response($response);
    }
}
