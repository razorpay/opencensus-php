<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use Carbon\Carbon;
use RZP\Constants\Timezone;

return [

    'testKafkaNonRetryableValidationFailures' => [
        "validation_failure: validation_failure: BAD_REQUEST_VALIDATION_FAILURE" ,
        "ledger_builder_failure: No parameter 'commission' found." ,
    ],

    'testKafkaRecordAlreadyExistsFailure' => [
        "validation_failure: record_already_exist: BAD_REQUEST_RECORD_ALREADY_EXIST",
    ],

    'testKafkaRetryableFailures' =>[
        "insufficient_balance_failure: BAD_REQUEST_INSUFFICIENT_BALANCE",
        "account_discovery_failure: ACCOUNT_DISCOVERY_ACCOUNT_NOT_FOUND",
        "account_discovery_failure: ACCOUNT_DISCOVERY_MULTIPLE_ACCOUNTS_FOUND",
        "mutex_failure: resource already acquired",
    ],

    'testCronRetrySuccessForPaymentMerchantCaptureEvent' => [
        'request' => [
            'url' => '/ledger_outbox/retry',
            'method' => 'POST',
            'content' => [
                'limit'        =>  5,
            ]
        ],
        'response' => [
            'content' => [
                'successful entries count' => 2,
                'failed entries count' =>  0,
            ],
        ]
    ],

];

