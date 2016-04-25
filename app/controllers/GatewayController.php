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
        // $app['slack']->send($input, 'transactions', '#tech_logs');
    }

    public function callbackKotakCancel()
    {
        return $this->callbackKotak();
    }

    public function callbackKotak()
    {
        $inputMsg = Input::get('msg');
        $input = explode('|', $inputMsg);

        $app = \App::getFacadeRoot();

        $result = $this->getGatewayEntityAndModeByTraceId($input[3]);

        $nb = $result['nb'];

        $mode = $result['mode'];

        $trace = $app['trace'];

        // check mode before search
        $trace->info(
            TraceCode::NETBANKING_PAYMENT_CALLBACK,
            [
                'input_all' => Input::all(),
                'input_msg' => Input::get('msg'),
                'input_arr' => $input
            ]);

        if ($nb === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Failed to find requisite trace id: ' . $input[3]);
        }

        $paymentId = $nb->getPaymentId();
        $publicPaymentId = $nb->getPublicPaymentId();

        $payment = (new \Models\Payment\Repository)->findOrFailPublic($paymentId);
        $publicKey = $payment->merchant->keys()->first()->getPublicKey($mode);

        $secret = \App::make('config')->get('app.key');

        $hash = hash_hmac('sha1', $publicPaymentId, $secret);

        $params = ['id' => $publicPaymentId, 'hash' => $hash];

        $url = Route::getUrlWithPublicCallbackAuth($params, $publicKey);

        $url = $url . '?msg=' . $inputMsg;

        return Redirect::to($url);
    }

    protected function getGatewayEntityAndModeByTraceId($traceId)
    {
        $app = \App::getFacadeRoot();

        $repo = new \Gateway\Netbanking\Base\Repository;

        $mode = 'test';

        $app['config']->set('database.default', $mode);

        $nb = $repo->findByTraceIdAndAction($traceId, \Gateway\Base\Action::AUTHORIZE);

        if ($nb === null)
        {
            $mode = 'live';

            $app['config']->set('database.default', $mode);

            $nb = $repo->findByTraceIdAndAction($traceId, \Gateway\Base\Action::AUTHORIZE);
        }

        return ['nb' => $nb, 'mode' => $mode];
    }
}
