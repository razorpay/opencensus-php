<?php

namespace RZP\Error\TerminalOnboarding;

class ErrorCode extends \RZP\Error\ErrorCode
{
    const SERVER_ERROR_TERMINAL_ONBOARDING_FAILED               = 'SERVER_ERROR_TERMINAL_ONBOARDING_FAILED';

    static function getConstants()
    {
        $oClass = new \ReflectionClass(__CLASS__);

        return $oClass->getConstants();
    }
}
