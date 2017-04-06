<?php

namespace RZP\Gateway\FirstData;

use RZP\Gateway\Base\Action;

class Component
{
    const CONNECT = 'CONNECT';
    const API     = 'API';
    const BUS     = 'BUS';

    const ACTION_MAPPING = [
        Action::AUTHORIZE => self::CONNECT,
        Action::CAPTURE   => self::API,
        Action::REFUND    => self::API,
        Action::VERIFY    => self::API,
        Action::REVERSE   => self::API,
        Action::PURCHASE  => self::API,
    ];
}
