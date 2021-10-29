<?php

namespace RZP\Http\Controllers\P2p;

use RZP\Models\P2p;

/**
 * @property  P2p\Mandates\Service $service
 */
class MandateController extends Controller
{
    public function fetchAll()
    {
        $input = $this->request()->all();

        $response = $this->service->fetchAll($input);

        return $this->response($response);
    }
}

