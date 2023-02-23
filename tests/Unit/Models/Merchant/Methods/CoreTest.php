<?php


namespace Unit\Models\Merchant\Methods;


use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Methods\Core as MethodsCore;

class CoreTest extends TestCase
{
    protected function getMerchantMethodsFixture($upiEnabled, $inAppUPIEnabled, $merchantId)
    {
        $methods = [
            'upi'           => $upiEnabled,
            'merchant_id'   => $merchantId,
            'disabled_banks'=> [],
            'banks'         => '[]',
            'addon_methods' => [
                'upi' => [
                    'in_app' => $inAppUPIEnabled
                ]
            ]
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
        $methods = $this->getMerchantMethodsFixture(true, 0,'8vUslVi0uFOSoy');

        $upiMethod = (new MethodsCore())->getUpiMethodForMerchant($methods->merchant);
        $this->assertEquals($upiMethod, $expected);
    }

    public function testUpiIsDisabled()
    {
        $expected = [
            'entity' => 'methods',
            'upi' => false,
        ];
        $methods = $this->getMerchantMethodsFixture(false, 0,'5ohNv7JkUtGrRx');

        $upiMethod = (new MethodsCore())->getUpiMethodForMerchant($methods->merchant);
        $this->assertEquals($upiMethod, $expected);
    }

    public function testInAppUpiIsEnabled()
    {
        $methods = $this->getMerchantMethodsFixture(true, 1,'8vUslVi0uFOSoy');

        $data = (new MethodsCore())->getFormattedMethods($methods->merchant);
        $this->assertTrue($data['in_app']);
    }
}
