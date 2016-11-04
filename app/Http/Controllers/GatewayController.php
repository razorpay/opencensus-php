<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use RZP\Exception;
use RZP\Http\Route;
use RZP\Models\Payment;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use Request;
use Redirect;
use RZP\Models\GatewayStatus\Absence;

class GatewayController extends Controller
{
    public function callbackAxis()
    {
        $this->callbackGateway('axis');
    }

    protected function processS2SCallback($input, $gateway)
    {
        $gateway = $this->app['gateway']->gateway($gateway);

        // Some gateways may need some preprocessing on the input
        // to be able to call the next few methods.
        //
        // Eg: gateway request needs to be decrypted
        $input = $gateway->preProcessS2SResponse($input);

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
                $data = $this->processS2SCallback($input, $gateway);
                break;

            case 'wallet_olamoney':
            case 'upi_hdfc':
                $trace = $this->app['trace'];

                $trace->info(
                    TraceCode::GATEWAY_PAYMENT_CALLBACK,
                    [
                        'input'     => $input,
                        'body'      => Request::getContent(),
                        'headers'   => Request::header(),
                        'gateway'   => $gateway,
                    ]);

                break;

            case 'wallet_freecharge':
                $data = $this->processS2SCallback($input, $gateway);
                break;

            case 'upi':
            case 'upi_icici':
                $input = Request::getContent();
                $gateway = 'upi_icici';

                $data = $this->processS2SCallback($input, $gateway);

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

        $keys = $this->repo->key->getKeysForMerchant($payment->getMerchantId());
        $publicKey = $keys->first()->getPublicKey($mode);

        $url = $this->route->getPublicCallbackUrlWithHash($publicPaymentId, $publicKey);

        $url = $url . '?msg=' . $inputMsg;

        return Redirect::to($url);
    }

    protected function getGatewayEntityAndModeByTraceId($traceId)
    {
        $app = $this->app;

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

    /**
     * Method to create a gateway absence entity
     * @return \Symfony\Component\HttpFoundation\Response
     * @internal param string $gateway
     */
    public function postCreateGatewayAbsence()
    {
        $input = Request::all();

        $data = (new Absence\Service)->create($input);

        return ApiResponse::json($data);
    }

    /**
     * Method to update gateway absence entity
     * @param integer $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putUpdateGatewayAbsence($id)
    {
        $input = Request::all();

        $data = (new Absence\Service)->edit($id, $input);

        return ApiResponse::json($data);
    }

    /**
     * Method to delete gateway absence entity
     * @param integer $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteGatewayAbsence($id)
    {
        $data = (new Absence\Service)->delete($id);

        return ApiResponse::json($data);
    }


    /**
     * Method to get absent gateways across multiple search params
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAbsentGateways()
    {
        $input = Request::all();

        $data = (new Absence\Service)->findAbsentGateways($input);

        return ApiResponse::json($data);
    }
}
