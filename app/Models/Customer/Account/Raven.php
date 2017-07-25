<?php

namespace RZP\Models\Customer;

use App;
use Exception;
use RZP\Models\Base;
use RZP\Models\Customer;

class Raven
{
    protected $raven = null;

    protected $sns = null;

    protected $env = null;

    public function __construct()
    {
         $app = App::getFacadeRoot();

         $this->raven = $app['raven'];

         $this->sns = $app['sns'];

         $this->env = $app['env'];
    }

    public function sendOtp($input, $merchant)
    {
        $success = true;

        $request = $this->getRavenSendOtpRequestInput($input, $merchant);

        if (($merchant->getId() === '2aTeFCKTYWwfrF') or ($this->env !== 'production'))
        {
            try
            {
                $this->sns->publish(json_encode($request));
            }
            catch (Exception $e)
            {
                $success = false;
            }
        }
        else
        {
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

