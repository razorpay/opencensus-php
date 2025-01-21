<?php

namespace Unit\Models\Merchant\Methods;
use RZP\Models\Merchant\Methods\Repository;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Methods\Core as MethodsCore;

class RepoTest extends TestCase
{

    public function testLiveTestMethodCreation()
    {
        $merchant = $this->fixtures->create('merchant', ['id' => '8vUslVi0uFOSoy']);

        $methods = (new MethodsCore())->setDefaultMethods($merchant);
        $this->assertNotEmpty($methods);
        $this->assertTrue($methods->isUpiEnabled());


    }
    public function testMethodsUpdate()
    {
        $merchant = $this->fixtures->create('merchant', ['id' => '8vUslVi0uFOSoy']);

        $methods = (new MethodsCore())->setDefaultMethods($merchant);
        $this->assertNotEmpty($methods);
        $this->assertTrue($methods->isUpiEnabled());

        $methods->setUpi(false);
        (new Repository())->saveOrFail($methods);

        $methods = (new MethodsCore())->getMethods($merchant);
        $this->assertFalse($methods->isUpiEnabled());
    }

    public function testMethodDbSync()
    {
        $merchant = $this->fixtures->create('merchant', ['id' => '8vUslVi0uFOSoy']);
        $methods = (new MethodsCore())->setDefaultMethods($merchant);
        $this->assertNotEmpty($methods);
        $this->assertTrue($methods->isUpiEnabled());

        $merchantId = '8vUslVi0uFOSoy';
        // Query the live database
        $liveMethods = \DB::connection('live')->select("select * from merchant_banks where merchant_id = ?", [$merchantId])[0];

        // Query the test database
        $testMethods = \DB::connection('test')->select("select * from merchant_banks where merchant_id = ?", [$merchantId])[0];
        $this->assertEquals($liveMethods, $testMethods);
    }


    public function testMethodsDbSyncFailure(){
        $merchant = $this->fixtures->create('merchant', ['id' => '8vUslVi0uFOSoy']);
        $methods = (new MethodsCore())->setDefaultMethods($merchant);
        $this->assertNotEmpty($methods);
        $this->assertTrue($methods->isUpiEnabled());

        $merchantId = '8vUslVi0uFOSoy';
        // Query the live database
        \DB::connection('live')->table('merchant_banks')->update(['upi' => 0]);
        $liveMethods = \DB::connection('live')->select("select * from merchant_banks where merchant_id = ?", [$merchantId])[0];

        // Query the test database
        $testMethods = \DB::connection('test')->select("select * from merchant_banks where merchant_id = ?", [$merchantId])[0];

        $this->assertFalse($liveMethods, $testMethods);
    }
}
