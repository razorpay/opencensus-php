<?php

namespace RZP\Gateway\Upi\Hulk;

class Status
{
    const CREATED       = 'created';
    const INITIATED     = 'initiated';
    const COMPLETED     = 'completed';
    const FAILED        = 'failed';

    const MG_SUCCESS    = 'S';
    const MG_FAILURE    = 'F';
}
