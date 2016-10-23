<?php

namespace RZP\Gateway\Cybersource;

class AuthenticationResult
{
    const SUCCESSFUL        = 0;

    const NOT_PARTICIPATING = 1;

    const UNABLE_TO_PERFORM = 6;

    const NOT_COMPLETED     = 9;

    const INVALID_PARES     = -1;
}