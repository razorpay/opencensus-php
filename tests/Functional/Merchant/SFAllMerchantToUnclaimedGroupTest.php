<?php

namespace RZP\Tests\Functional\Merchant\Account;

use RZP\Models\Admin\Admin;
use RZP\Models\Admin\Group;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class SFAllMerchantToUnclaimedGroupTest extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;

    protected $unclaimedGroup;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/SFAllMerchantToUnclaimedGroupTestData.php';

        parent::setUp();

        $this->fixtures->create('group:default_unclaimed_group');
    }

    public function testAddMerchantsToUnclaimedGroup()
    {
        $testData = &$this->testData[__FUNCTION__];

        $this->ba->adminAuth();

        $this->startTest();

        $merchantMapTest = \DB::connection('test')->table('merchant_map')
                          ->where('entity_id', Group\Constant::SF_UNCLAIMED_GROUP_ID)
                          ->get();

        $merchantsTest = \DB::connection('test')->table('merchants')
                        ->get();

        $merchantsTest = json_decode(json_encode($merchantsTest), true);

        $merchantMapTest = json_decode(json_encode($merchantMapTest), true);

        $this->assertEquals(count($merchantsTest), count($merchantMapTest));


        $merchantMapLive = \DB::connection('live')->table('merchant_map')
                              ->where('entity_id', Group\Constant::SF_UNCLAIMED_GROUP_ID)
                              ->get();

        $merchantsLive = \DB::connection('live')->table('merchants')
                            ->get();

        $merchantsLive = json_decode(json_encode($merchantsLive), true);

        $merchantMapLive = json_decode(json_encode($merchantMapLive), true);

        $this->assertEquals(count($merchantsLive), count($merchantMapLive));
    }
}
