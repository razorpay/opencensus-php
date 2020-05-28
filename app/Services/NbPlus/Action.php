<?php

namespace RZP\Services\NbPlus;

class Action
{
    const AUTHORIZE           = 'authorize';
    const CALLBACK            = 'callback';
    const VERIFY              = 'verify';
    const DEBIT               = 'debit';
    const AUTHORIZE_FAILED    = 'authorize_failed';
    const PREPROCESS_CALLBACK = 'preprocess_callback';


    const SUPPORTED_ACTIONS = [
        self::AUTHORIZE,
        self::CALLBACK,
        self::VERIFY,
        self::AUTHORIZE_FAILED,
        self::DEBIT,
        self::PREPROCESS_CALLBACK,
    ];
}
