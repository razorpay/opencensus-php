<?php

namespace RZP\Models\Customer;

use App;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

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
            $this->trace->info(TraceCode::RAVEN_REQUEST, $request);

            $this->sns->publish(json_encode($request));
        }
        catch (\Throwable $e)
        {
            $this->trace->error(TraceCode::RAVEN_ASYNC_REQUEST_FAILED, $request);

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

        if (empty($input['template']) === false)
        {
            $request['template'] = $input['template'];
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

