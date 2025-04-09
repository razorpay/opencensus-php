<?php

namespace RZP\Services\UpiPayment;

use RZP\Base;

class Validator extends Base\Validator
{
    const UNEXPECTED_PREPROCESS = 'unexpected_preprocess';
    protected static $unexpectedPreprocessRules = [
        'payment'                  => 'required|array',
        'upi'                      => 'required|array',
        'upi.npci_reference_id'    => 'required',
        'gateway'                  => 'required',
        'terminal'                 => 'required|array',
        'source'                   => 'required'
    ];
}
