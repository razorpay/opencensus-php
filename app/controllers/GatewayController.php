<?php

use Http\Route;

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
        $inputMsg = Input::get('msg');
        $input = explode('|', $inputMsg);

        // check mode before search
        $nb = (new \Gateway\Netbanking\Base\Repository)->findByTraceIdAndAction(
                                        $input[3], \Gateway\Base\Action::AUTHORIZE);

        if ($nb === null)
        {
            return;
        }

        $publicPaymentId = $nb->getPublicPaymentId();

        $secret = \App::make('config')->get('app.key');

        $hash = hash_hmac('sha1', $publicPaymentId, $secret);

        $url = Route::getUrlWithPublicCallbackAuth(
                        ['id' => $publicPaymentId, 'hash' => $hash]);

        $url = $url . '?msg=' . $inputMsg;

        return Redirect::to($url);
    }
}
