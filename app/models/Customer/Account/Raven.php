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
        //$this->raven = \App::____
    }

    public function sendOtp($input)
    {
        return array('result' => 1);
    }

    public function verifyOtp($input)
    {
        return array('result' => 1);
    }

    public function updateSmsStatus($service, $input)
    {
        return array('result' => 1);
    }
}

