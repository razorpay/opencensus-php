<?php

namespace RZP\Gateway\Base;

class Action
{
    const PURCHASE     = 'purchase';
    const AUTHENTICATE = 'authenticate';
    const AUTHORIZE    = 'authorize';
    const CAPTURE      = 'capture';
    const REFUND       = 'refund';
    const VOID         = 'void';
    const VERIFY       = 'verify';
    const CALLBACK     = 'callback';
    const REVERSE      = 'reverse';

    public static $nonVerifiableActions = [
        self::AUTHENTICATE
    ];
}
