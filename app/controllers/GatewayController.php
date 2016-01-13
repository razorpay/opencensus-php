<?php

use Trace\Trace;
use Trace\TraceCode;

class GatewayController extends BaseController
{
    public function callbackAxis()
    {
        $this->callbackGateway('axis');
    }

    public function callbackGateway($gateway)
    {
        $input = Input::all();
        $input['gateway'] = $gateway;

        $app = \App::getFacadeRoot();
        $app['slack']->send($input, 'transactions', '#tech_logs');
    }

    public function callbackKotak()
    {
        $input_msg = Input::get('msg');
        $input = explode('|', $input_msg);

        // check mode before search
        $this->trace->info(
            TraceCode::NETBANKING_PAYMENT_CALLBACK,
            [
                'input_all' => Input::all(),
                'input_msg' => Input::get('msg'),
                'input_arr' => $input
            ]);

        $nb = (new \Gateway\Netbanking\Base\Repository)->findByTraceIdAndAction(
            $input[3], \Gateway\Base\Action::AUTHORIZE);

        if ($nb === false)
        {
            return;
        }

        $publicPaymentId = 'pay_' . $nb->payment_id;

        $secret = \App::make('config')->get('app.key');

        $hash = hash_hmac('sha1', $publicPaymentId, $secret);

        $url = \Http\Route::getUrlWithPublicCallbackAuth(
                        ['id' => $publicPaymentId, 'hash' => $hash]);

        $url = $url . '?msg=' . $input_msg;

        return Redirect::to($url);
    }
}
