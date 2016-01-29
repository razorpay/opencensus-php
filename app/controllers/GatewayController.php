<?php

use Trace\Trace;
use Trace\TraceCode;
use Http\Route;
use EE\Exception;

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

        $app = \App::getFacadeRoot();
        $trace = $app['trace'];

        // check mode before search
        $trace->info(
            TraceCode::NETBANKING_PAYMENT_CALLBACK,
            [
                'input_all' => Input::all(),
                'input_msg' => Input::get('msg'),
                'input_arr' => $input
            ]);

        $mode = 'test';

        $repo = new \Gateway\Netbanking\Base\Repository;
        $repo->connection($mode);

        $nb = $repo->findByTraceIdAndAction($input[3], \Gateway\Base\Action::AUTHORIZE);

        if ($nb === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Failed to find requisite trace id: ' . $input[3]);
        }

        $paymentId = $nb->getPaymentId();
        $publicPaymentId = $nb->getPublicPaymentId();

        $payment = (new \Models\Payment\Repository)->connection($mode)->findOrFailPublic($paymentId);
        $publicKey = $payment->merchant->keys()->first();

        $secret = \App::make('config')->get('app.key');

        $hash = hash_hmac('sha1', $publicPaymentId, $secret);

        $params = ['id' => $publicPaymentId, 'hash' => $hash];

        $url = Route::getUrlWithPublicCallbackAuth($params, $publicKey);

        $url = $url . '?msg=' . $inputMsg;

        return Redirect::to($url);
    }
}
