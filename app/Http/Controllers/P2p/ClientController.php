<?php

namespace RZP\Http\Controllers\P2p;

use RZP\Models\P2p;
use RZP\Services\Pspx;

/**
 * @property  P2p\Client\Service $service
 */
class ClientController extends Controller
{
    public function getGatewayConfig()
    {
        $input = $this->request()->all();

        $response = $this->service->getGatewayConfig($input);

        return $this->response($response);
    }
}
