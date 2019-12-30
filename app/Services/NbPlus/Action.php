<?php

namespace RZP\Services\NbPlus;

class Action
{
    const AUTHORIZE        = 'authorize';
    const CALLBACK         = 'callback';
    const VERIFY           = 'verify';
    const AUTHORIZE_FAILED = 'authorize_failed';

    const SUPPORTED_ACTIONS = [
        self::AUTHORIZE,
        self::CALLBACK,
        self::VERIFY,
        self::AUTHORIZE_FAILED
    ];
}
