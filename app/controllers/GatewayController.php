<?php

use Trace\Trace;
use Trace\TraceCode;
use Http\Route;
use EE\Exception;
use Gateway\Billdesk\AuthStatus;

class GatewayController extends BaseController
{
    public function callbackAxis()
    {
        $this->callbackGateway('axis');
    }

    protected function callbackBillDesk($input)
    {
        $app = \App::getFacadeRoot();

        $msg = $input['msg'];

        $gateway = new \Gateway\Billdesk\Gateway();

        $fields = $gateway->getFieldsForAction('callback');

        $content = explode('|', $msg);

        $content = array_combine($fields, $content);

        $payment_id = "pay_" . $content['CustomerID'];

        $result = $this->getBillDeskModeAndPaymentById($payment_id);

        $payment = $result['payment'];

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

        if ($content['AuthStatus'] !== AuthStatus::SUCCESS)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                $content['AuthStatus'], '');

        }

        if ($payment->isAuthorized() === false)
        {
            // ideally we could have gone through the \Models\Payment\Service::callback
            // however, when doing so, throws up class rzp.mode not found. Hence, to
            // avoid this, we go through the regular URL post callback flow
            // just like how its implemented in kotak's case. However, we do
            // not do any redirection here since there is already a redirection
            // that happens via the browser. We just simply post and be done.
            $publicKey = $payment->merchant->keys()->first()->getPublicKey($mode);

            $secret = \App::make('config')->get('app.key');

            $hash = hash_hmac('sha1', $payment_id, $secret);

            $params = ['id' => $payment_id, 'hash' => $hash];

            $url = Route::getUrlWithPublicCallbackAuth($params, $publicKey);

            $headers = array(
                'User-Agent' => 'Razorpay-Webhook/v1',
            );

            Requests::post(
                $url,
                $headers,
                ['msg' => $msg]);

            // at this point, payment should be authorized. Assert so...
            assert($payment->isAuthorized() === false);

        }
    }

    public function callbackGateway($gateway)
    {
        $input = Input::all();

        if($gateway === 'billdesk')
        {
            $this->callbackBillDesk($input);

        }

        // $input['gateway'] = $gateway;

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

    protected function getBillDeskModeAndPaymentById($payment_id)
    {
        $app = \App::getFacadeRoot();
        $core = new Core();
        $mode = 'test';
        $app['config']->set('database.default', $mode);
        $payment = null;
        try
        {
            $payment = $core->retrieveById($payment_id);
        }
        catch(\Exception $e)
        {

        }
        if($payment == null)
        {
            $mode = 'live';
            $app['config']->set('database.default', $mode);
            $payment = $core->retrievePaymentById($payment_id);
        }
        return ['payment' => $payment, 'mode' => $mode];

    }
}
