<?php

namespace RZP\Http\Controllers;

use RZP\Exception;
use RZP\Http\ApiResponse;
use RZP\Http\Route;
use RZP\Models\Payment;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use Request;
use Redirect;

class GatewayController extends Controller
{
    public function callbackAxis()
    {
        $this->callbackGateway('axis');
    }

    protected function processS2SCallback($input, $gateway)
    {
        $gateway = $this->app['gateway']->gateway($gateway);

        $paymentId = $gateway->getPaymentIdFromServerCallback($input);

        $mode = $this->app['repo']->determineLiveOrTestModeForEntity($paymentId, 'payment');

        \Database\DefaultConnection::set($mode);

        if ($mode === null)
        {
            throw new Exception\LogicException(
                'Payment id not found in either database: ' . $paymentId);
        }

        $this->app['basicauth']->setMode($mode);

        $paymentId = Payment\Entity::getSignedId($paymentId);

        return (new Payment\Service)->s2sCallback($paymentId, $input);
    }

    protected function callbackEbs($input)
    {
        $msg = $input['msg'];

        $gateway = $this->app['gateway']->gateway('ebs');

        //TODO validate callback

        $paymentId = $gateway->getPaymentIdFromServerCallback($input);

        $mode = $this->app['repo']->determineLiveOrTestModeForEntity($paymentId, 'payment');

        \Database\DefaultConnection::set($mode);

        if ($mode === null)
        {
            throw new Exception\LogicException(
                'Payment id not found in either database: ' . $paymentId);
        }

        $this->app['basicauth']->setMode($mode);

        $paymentId = Payment\Entity::getSignedId($paymentId);

        return (new Payment\Service)->s2sCallback($paymentId, $input);
    }

    public function callbackGateway($gateway)
    {
        $input = Request::all();

        $data = [];

        switch ($gateway)
        {
            case 'billdesk':
            case 'wallet_olamoney':
                $data = $this->processS2SCallback($input, $gateway);
                break;

            case 'upi':
                $trace = $this->app['trace'];

                // check mode before search
                $trace->info(
                    TraceCode::GATEWAY_PAYMENT_CALLBACK,
                    [
                        'input'     => $input,
                        'gateway'   => 'upi_icici',
                    ]);

                break;
        }

        // $input['gateway'] = $gateway;

        // $app['slack']->send($input, 'transactions', '#tech_logs');

        return ApiResponse::json($data);
    }

    public function callbackKotakCancel()
    {
        return $this->callbackKotak();
    }

    public function callbackKotak()
    {
        $inputMsg = Request::get('msg');
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
                'input_all' => Request::all(),
                'input_msg' => Request::input('msg'),
                'input_arr' => $input
            ]);

        if ($nb === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Failed to find requisite trace id: ' . $input[3]);
        }

        $paymentId = $nb->getPaymentId();
        $publicPaymentId = $nb->getPublicPaymentId();


        $payment = $this->repo->payment->findOrFailPublic($paymentId);

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

        $repo = new \RZP\Gateway\Netbanking\Base\Repository;

        $mode = 'test';

        $app['config']->set('database.default', $mode);

        $nb = $repo->findByTraceIdAndAction($traceId, \RZP\Gateway\Base\Action::AUTHORIZE);

        if ($nb === null)
        {
            $mode = 'live';

            $app['config']->set('database.default', $mode);

            $nb = $repo->findByTraceIdAndAction($traceId, \RZP\Gateway\Base\Action::AUTHORIZE);
        }

        return ['nb' => $nb, 'mode' => $mode];
    }
}
