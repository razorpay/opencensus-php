<?php

namespace RZP\Models\Transfer;

class Origin
{
    const API              = 'api';
    const DASHBOARD        = 'dashboard';
    const ORDER_AUTOMATION = 'order_automation';
    const ORDER            = 'order';

    public static function isOriginValid($origin)
    {
        return (defined(__CLASS__ . '::' . strtolower($origin)));
    }
}
