<?php

namespace RZP\Models\Customer;

use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Models\Merchant\Account;

class Raven
{
    protected $raven = null;

    public function __construct()
    {
         $app = \App::getFacadeRoot();

         $this->raven = $app['raven'];
    }

    public function sendOtp($input, $merchant)
    {
        $request = $this->getRavenSendOtpRequestInput($input, $merchant);
        
        $response = $this->raven->sendOtp($request);

        if (isset($response['sms_id']))
        {
            return ['success' => true];
        }

        return ['success' => false];
    }

    public function verifyOtp($input, $merchant)
    {
        $request = $this->getRavenVerifyOtpRequestInput($input, $merchant);

        $response = $this->raven->verifyOtp($request);

        return $response;
    }

    public function updateSmsStatus($id, $input)
    {
        $result = $this->raven->smsCallback($id, $input);

        return $result;
    }

    protected function getRavenSendOtpRequestInput($input, $merchant)
    {
        $request = array(
            'context' => $merchant->getId(),
            'receiver' => $input['contact'],
            'source' => 'api',
            'params' => [
                'merchant_name' => $merchant->getBillingLabelElseName()
            ]
        );

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

