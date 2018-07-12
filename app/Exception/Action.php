<?php

namespace RZP\Exception;

class Action
{
    const AUTHENTICATE  = 'authenticate';
    const AUTHORIZATION = 'authorization';
    const ENROLL        = 'enroll';

    public static $nonVerifiableActions = [
        self::AUTHENTICATE,
        self::ENROLL,
    ];
}
