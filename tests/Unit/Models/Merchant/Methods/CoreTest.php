<?php


namespace Unit\Models\Merchant\Methods;


use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Methods\Core as MethodsCore;

class CoreTest extends TestCase
{
    protected function getMerchantMethodsFixture($upiEnabled, $merchantId)
    {
        $methods = [
            'upi'     => $upiEnabled,
            'merchant_id'   => $merchantId,
            'disabled_banks' => [],
            'banks' => '[]',
        ];

        $this->fixtures->create('merchant', ['id' => $merchantId]);
        return $this->fixtures->create('methods', $methods);
    }

    public function testUpiIsEnabled()
    {
        $expected = [
            'entity' => 'methods',
            'upi' => true,
        ];
        $methods = $this->getMerchantMethodsFixture(true, '8vUslVi0uFOSoy');

        $upiMethod = (new MethodsCore())->getUpiMethodForMerchant($methods->merchant);
        $this->assertEquals($upiMethod, $expected);
    }

    public function testUpiIsDisabled()
    {
        $expected = [
            'entity' => 'methods',
            'upi' => false,
        ];
        $methods = $this->getMerchantMethodsFixture(false, '5ohNv7JkUtGrRx');

        $upiMethod = (new MethodsCore())->getUpiMethodForMerchant($methods->merchant);
        $this->assertEquals($upiMethod, $expected);
    }
}
