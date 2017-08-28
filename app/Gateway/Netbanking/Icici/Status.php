<?php

namespace RZP\Gateway\Netbanking\Icici;

class Status
{
    const SUCCESS    = 'SUCCESS';
    const FAILED     = 'FAILED';
    // TODO: Get exact meanings and leave comments on
    // how we get these statuses
    const REVERSED   = 'Reversed';
    const IN_PROCESS = 'IN PROCESS';
    const ERROR      = 'Error';

    const Y = 'Y';
}
