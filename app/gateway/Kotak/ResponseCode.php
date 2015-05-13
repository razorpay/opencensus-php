<?php

namespace Gateway\Kotak;

class ResponseCode
{
    const SUC   = '00';
    const VER   = 'VER';
    const HNM   = 'HNM';
    const STO   = 'STO';
    const IER   = 'IER';
    const TO    = 'TO';
    const CAN   = 'CAN';

    protected $code = array(
        self::SUC   => 'Successful Transaction',
        self::VER   => 'Validation Error Occurs if field data is incorrect',
        self::HNM   => 'Hash Not Match Occurs if the data is tampered',
        self::STO   => 'Session Timeout If user session is timed out',
        self::IER   => 'Internal Error System Error',
        self::TO    => 'Timeout Time out while connecting to RuPay PaySecure',
        self::CAN   => 'Cancel User pressed Cancel Button',
    );
}