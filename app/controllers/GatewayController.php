<?php

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
        $app['slack']->send($input, 'transactions', '#transactions');
    }

    public function callbackKotak()
    {
        $input_msg = Input::get('msg');
        $input = explode('|', $input_msg);
        //check mode before search
        $nb = (new \Gateway\Netbanking\Base\Repository)->findByTraceIdAndAction(
            $input[3], \Gateway\Base\Action::AUTHORIZE);
        if(!$nb)
        {
            return;
        }
        $payment_id_public = 'pay_' . $nb->payment_id;
        $secret = \App::make('config')->get('app.key');

        $hash = hash_hmac('sha1', $payment_id_public, $secret);

        $url = \Http\Route::getUrlWithPublicCallbackAuth(['id' => $payment_id_public, 'hash' => $hash]);
        $url = $url . '?msg=' . $input_msg;

        return Redirect::to($url);
    }
}
