<?php

namespace Models\Payment;

class Status
{
    const CREATED       = 'created';
    const AUTHORIZED    = 'authorized';
    const CAPTURED      = 'captured';
    const FAILED        = 'failed';
}
