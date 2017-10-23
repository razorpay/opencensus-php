<?php

namespace RZP\Tests\Functional\Gateway\Upi\Sbi;

class Constants
{
    const TYPE                                = 'type';
    const ASYNC                               = 'async';
    const SUCCESS                             = 'success';
    const GATEWAY                             = 'gateway';
    const API_SUCCESS                         = 'apiSuccess';
    const GATEWAY_SUCCESS                     = 'gatewaySuccess';
    const PAYMENT_ID                          = 'payment_id';
    const SHARED_UPI_MIDGATE_TERMINAL         = 'terminal:shared_upi_mindgate_sbi_terminal';
    const MINDGATE_SBI_GATEWAY_TEST_DATA_FILE = __DIR__ . '/UpiMindgateSbiGatewayTestData.php';
}