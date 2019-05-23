<?php

namespace RZP\Http\Controllers\P2p;

use RZP\Models\P2p;

/**
 * @property  P2p\Beneficiary\Service $service
*/
class BeneficiaryController extends Controller
{
    public function create()
    {
        $input = $this->request()->all();

        $response = $this->service->add($input);

        return $this->response($response);
    }

    public function validateBeneficiary()
    {
        $input = $this->request()->all();

        $response = $this->service->validate($input);

        return $this->response($response);
    }

    public function fetchAll()
    {
        $input = $this->request()->all();

        $response = $this->service->fetchAll($input);

        return $this->response($response);
    }

    public function handle()
    {
        $input = $this->request()->all();

        $response = $this->service->handle($input);

        return $this->response($response);
    }
}
