<?php

namespace Models\Payment;

class Status
{
    const OPEN = 'open';
    const AUTHORIZED = 'authorized';
    const CAPTURED = 'captured';
    const FAILED = 'failed';
}
