<?php

namespace RZP\Tests\Functional\Gateway\Upi\Sbi;

class Constants
{
    const TYPE                                = 'type';
    const ASYNC                               = 'async';
    const STATUS                              = 'status';
    const SUCCESS                             = 'success';
    const GATEWAY                             = 'gateway';
    const API_SUCCESS                         = 'apiSuccess';
    const GATEWAY_SUCCESS                     = 'gatewaySuccess';
    const AMOUNT_MISMATCH                     = 'amountMismatch';
    const FREQUENCY_DAILY                     = 'daily';
    const COUNT                               = 'count';
    const FILE                                = 'file';
    const REJECTED_VPA                        = 'rejectedcollect@sbi';
    const CBS_DOWN_VPA                        = 'cbsdown@sbi';
    const FAILED_VPA                          = 'failedcollect@sbi';
    const VALIDATION_FAIL_VPA                 = 'failedvalidate@sbi';
    const PAYMENT_ID                          = 'payment_id';
    const SHARED_UPI_SBI_MIDGATE_TERMINAL     = 'terminal:shared_upi_mindgate_sbi_terminal';
    const SHARED_UPI_SBI_INTENT_TERMINAL      = 'terminal:shared_upi_mindgate_sbi_intent_terminal';
    const MINDGATE_SBI_GATEWAY_TEST_DATA_FILE = __DIR__ . '/UpiSbiGatewayTestData.php';
}
