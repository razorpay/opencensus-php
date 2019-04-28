<?php

namespace RZP\Http\Controllers\P2p;

use RZP\Models\P2p;
use RZP\Trace\TraceCode;

/**
 * @property  P2p\Upi\Service $service
 */
class UpiController extends Controller
{
    public function gatewayCallback()
    {
        $input['content'] = $this->request()->input();
        $input['gateway'] = $this->request()->route('gateway');

        $response = $this->service->gatewayCallback($input);

        return $this->response($response);
    }
}
