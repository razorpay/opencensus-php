<?php

namespace RZP\Http\Controllers\P2p;

use RZP\Models\P2p;
use RZP\Trace\TraceCode;

/**
 * @property  P2p\Turbo\Service $service
 */
class TurboController extends Controller
{
    public function turboGatewayCallback()
    {
        $input['payload'] = $this->request()->getContent();
        $input['content'] = $this->request()->input();
        $input['headers'] = $this->request()->header();
        $input['gateway'] = $this->request()->route('gateway');

        $traceInput = $input;

        $this->app['trace']->info(TraceCode::GATEWAY_PAYMENT_S2S_CALLBACK, [
            $input['gateway'] => $traceInput,
        ]);

        $response = $this->service->turboGatewayCallback($input);

        return $this->response($response);
    }
}
