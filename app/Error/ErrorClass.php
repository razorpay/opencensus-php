<?php

namespace RZP\Error;

class ErrorClass
{
    const GATEWAY = 'GATEWAY';

    const BAD_REQUEST = 'BAD_REQUEST';

    const SERVER = 'SERVER';

    // List of all critical classes
    protected static $criticalErrorClasses = [
        self::GATEWAY,
        self::SERVER
    ];

    public static function isCritical($errorClass)
    {
        return (in_array($errorClass, self::$criticalErrorClasses, true) === true);
    }
}
