<?php

namespace Models\Customer;

use Models\Base;
use Models\Customer;
use Models\Merchant\Account;

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

        $result = array(
            'success' => $response['success']
        );

        return $result;
    }

    public function verifyOtp($input)
    {
        $input['receiver'] = $input['contact'];
        unset($input['contact']);

        $response = $this->raven->verifyOtp($input);

        $result = array(
            'success' => $response['success']
        );

        return $result;
    }

    public function updateSmsStatus($id, $input)
    {
        $result = $this->raven->smsCallback($id, $input);

        return $result;

    }
}

