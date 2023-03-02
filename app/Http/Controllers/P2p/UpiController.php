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
        $input['payload'] = $this->request()->getContent();
        $input['content'] = $this->request()->input();
        $input['headers'] = $this->request()->header();
        $input['gateway'] = $this->request()->route('gateway');

        $traceInput = $input;

        // unsetting jwt token from traces in request
        unset($traceInput['headers']['x-passport-jwt-v1']);
        unset($traceInput['content']['payeeVpa'], $traceInput['content']['payerVpa']);

        $this->app['trace']->info(TraceCode::GATEWAY_PAYMENT_S2S_CALLBACK, [
            $input['gateway'] => $traceInput,
        ]);

        $response = $this->service->gatewayCallback($input);

        return $this->response($response);
    }

    public function sendReminder()
    {
        $input['handle'] = $this->request()->route('handle');
        $input['entity'] = $this->request()->route('entity');
        $input['id']     = $this->request()->route('id');
        $input['action'] = $this->request()->route('action');

        $this->app['trace']->info(TraceCode::P2P_REMINDER_CALLBACK, [
           'input' => $input
        ]);

        return $this->service->reminderCallback($input);
    }
}
