<?php

namespace RZP\Gateway\FirstData;

class Component
{
    const CONNECT = 'CONNECT';
    const API     = 'API';
    const BUS     = 'BUS';

    const ACTION_MAPPING = [
        Action::AUTHORIZE      => self::CONNECT,
        Action::CAPTURE        => self::API,
        Action::REFUND         => self::API,
        Action::VERIFY         => self::API,
        Action::REVERSE        => self::API,
        Action::PURCHASE       => self::API,
        Action::VERIFY_REFUND  => self::API,
        Action::VERIFY_REVERSE => self::API,
    ];
}
