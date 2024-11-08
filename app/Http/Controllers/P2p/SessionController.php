<?php

namespace RZP\Http\Controllers\P2p;

use RZP\Models\P2p;

/**
 * @property  P2p\Session\Service $service
 * SessionController will be used to create the session using the NACL algorithm
 */
class SessionController extends Controller
{
    public function create()
    {
        $input = $this->request()->all();

        $response = $this->service->create($input);

        return $this->response($response);
    }
}
