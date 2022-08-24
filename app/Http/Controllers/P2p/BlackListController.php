<?php

namespace RZP\Http\Controllers\P2p;

use RZP\Exception\RuntimeException;

/**
 * @property  P2p\Beneficiary\Service $service
 */

class BlackListController extends Controller
{
    public function create()
    {
        $input = $this->request()->all();

        $response = $this->service->add($input);

        return $this->response($response);
    }

    public function remove()
    {
        $input = $this->request()->all();

        $response = $this->service->remove($input);

        return $this->response($response);
    }

    public function fetchAll()
    {
        $input = $this->request()->all();

        $response = $this->service->fetchAll($input);

        return $this->response($response);
    }
}
