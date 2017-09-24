<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testPaymentNetbankingEntity' => [
        'action'          => 'authorize',
        'amount'          => 20,
        'bank'            => 'UTIB',
        'received'        => true,
        'error_message'   => null,
        'schedule_status' => 'Y',
        'status'          => 'Y',
        'entity'          => 'netbanking',
    ],
];