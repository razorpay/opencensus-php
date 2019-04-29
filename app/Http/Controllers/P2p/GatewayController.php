<?php

namespace RZP\Http\Controllers\P2p;

use RZP\Trace\TraceCode;

class GatewayController extends Controller
{
    public function callback()
    {
        $input['content'] = $this->request()->input();
        $input['gateway'] = $this->request()->route('gateway');

        $this->app['trace']->info(TraceCode::GATEWAY_PAYMENT_S2S_CALLBACK, $input);

        return ['success' => true];
    }
}
