<?php

namespace RZP\Tests\Functional\SmartCollect2;

use RZP\Models\Payment\Gateway;
use RZP\Tests\Functional\TestCase;

class DirectSettlementGatewaysTest extends TestCase
{
    public function testAllowedBankTransferDirectSettlementGateways()
    {
        $input = Gateway::DIRECT_SETTLEMENT_GATEWAYS;

        $allowedBankTransferGateways = ['bt_yesbank', 'bt_rbl', 'bt_axis'];

        assertTrue(empty(array_diff($allowedBankTransferGateways, array_keys($input))));
    }

    public function testAllowedUPITransferDirectSettlementGateways()
    {
        $input = Gateway::DIRECT_SETTLEMENT_GATEWAYS;

        $allowedUpiTransferGateways = ['upi_yesbank'];

        assertTrue(empty(array_diff($allowedUpiTransferGateways, array_keys($input))));
    }
}
