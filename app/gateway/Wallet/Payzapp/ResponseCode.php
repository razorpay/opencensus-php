<?php

namespace \Gateway\Wallet\Payzapp;

class ResponseCode
{
    public static $codes = array(
        50010 => 'Init',
        50011 => 'Capture Aborted',
        50016 => 'Switch Start',
        50017 => 'Switch Timeout',
        50018 => 'Switch Aborted',
        50020 => 'Success',
        50021 => 'Failed',
        50097 => 'Test Transaction',
        );
}
