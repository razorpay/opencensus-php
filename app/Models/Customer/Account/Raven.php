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

    public function sendOtp($input)
    {
        $input['receiver'] = $input['contact'];
        unset($input['contact']);

        $response = $this->raven->sendOtp($input);

        if (isset($response['sms_id']))
        {
            return ['success' => true];
        }

        return ['success' => false];
    }

    public function verifyOtp($input)
    {
        $input['receiver'] = $input['contact'];
        unset($input['contact']);

        $response = $this->raven->verifyOtp($input);

        return $response;
    }

    public function updateSmsStatus($id, $input)
    {
        $result = $this->raven->smsCallback($id, $input);

        return $result;

    }
}

