<?php

namespace Unit\Models\Payment;

use RZP\Models\Payment\Gateway;
use RZP\Tests\TestCase;

class GatewayTest extends TestCase
{
    public function testDSSettledGatewayWithDSOrgName()
    {
        $allOrgName = array();

        $input = Gateway::DIRECT_SETTLEMENT_GATEWAYS;

        array_walk_recursive($input, function ($value, $key) use (&$allOrgName){
            $allOrgName[] = $value;
        }, $allOrgName);

        foreach (array_unique($allOrgName) as $orgName) {
            if(isset(Gateway::DIRECT_SETTLEMENT_ORG_NAME[$orgName]) === false) {
                $this->fail("Add " . $orgName . "in DIRECT_SETTLEMENT_ORG_NAME array");
                return;
            }
        }
    }

    public function testIsPowerWalletNotSupportedForGatewayPayu() {
        $result  = Gateway::isPowerWalletNotSupportedForGateway(Gateway::PAYU);
        assertTrue($result);
    }

    public function testIsPowerWalletNotSupportedForGatewayCcavenue() {
        $result  = Gateway::isPowerWalletNotSupportedForGateway(Gateway::CCAVENUE);
        assertTrue($result);
    }
}
