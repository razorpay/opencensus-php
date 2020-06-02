<?php

namespace RZP\Models\Customer;

use App;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;

class Raven extends Base\Core
{
    protected $raven = null;

    protected $sns = null;

    public function __construct()
    {
        parent::__construct();

        $this->raven = $this->app['raven'];

        $this->sns = $this->app['sns'];
    }

    public function sendOtp($input, $merchant)
    {
        $success = true;

        $request = $this->getRavenSendOtpRequestInput($input, $merchant);

        try
        {
            $this->sns->publish(json_encode($request));
        }
        catch (\Throwable $e)
        {
            $traceData = array(
                'error' => $e->getMessage(),
                'request' => $request,
            );

            $this->trace->error(TraceCode::RAVEN_ASYNC_REQUEST_FAILED, $traceData);

            $success = false;
            $response = $this->raven->sendOtp($request);

            if (isset($response['sms_id']) === false)
            {
                $success = false;
            }
        }

        return ['success' => $success];
    }

    public function verifyOtp($input, $merchant)
    {
        $request = $this->getRavenVerifyOtpRequestInput($input, $merchant);

        $response = $this->raven->verifyOtp($request);

        return $response;
    }

    public function updateSmsStatus($gateway, $input)
    {
        $this->trace->info(TraceCode::RAVEN_CALLBACK_REQUEST,
            [
                'gateway' => $gateway,
                'input'   => $input,
            ]);

        $result = $this->raven->smsCallback($gateway, $input);

        return $result;
    }

    protected function getRavenSendOtpRequestInput($input, $merchant)
    {
        $request = array(
            'context' => $merchant->getId(),
            'receiver' => $input['contact'],
            'source' => 'api',
            'params' => [
                'merchant_name' => $merchant->getBillingLabel()
            ]
        );

        if (isset($input['sms_hash']) === true)
        {
            $request['params']['sms_hash'] = $input['sms_hash'];
        }

        if (empty($input['template']) === false)
        {
            $request['template'] = $input['template'];
        }

        if ((isset($input['method'])) and
            (($input['method'] === Method::CARDLESS_EMI) or
            ($input['method'] === Method::PAYLATER)))
        {

            $request['template'] = 'sms.otp_cardless';

            $request['params']['provider'] = $input['provider'] === Gateway::GETSIMPL ? 'Simpl' : $input['provider'];
        }

        return $request;
    }

    protected function getRavenVerifyOtpRequestInput($input, $merchant)
    {
        $request = array(
            'context' => $merchant->getId(),
            'receiver' => $input['contact'],
            'source' => 'api',
            'otp' => $input['otp']
        );

        return $request;
    }
}
