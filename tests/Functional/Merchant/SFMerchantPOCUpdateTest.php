<?php

namespace RZP\Tests\Functional\Merchant\Account;

use RZP\Models\Admin\Admin;
use RZP\Models\Admin\Group;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class SFMerchantPOCUpdateTest extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;

    const GROUP_ID = 'group_id';

    const ENTITY_ID = 'entity_id';

    const MERCHANT_ID = 'merchant_id';

    const GROUP_MAP = 'group_map';

    const MERCHANT_MAP = 'merchant_map';

    protected $admin1, $admin2, $admin3, $admin4;

    protected $merchant1, $merchant2, $merchant3, $merchant4;

    protected $otherGroup;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/SFMerchantPOCUpdateTestData.php';

        parent::setUp();

        $this->orgId = '100000razorpay';

        $this->createGroups();

        $this->createAdmins();

        $this->createMerchants();
    }

    protected function createGroups()
    {
        $this->fixtures->create('group:default_unclaimed_group');

        $this->fixtures->create('group:default_sme_claimed_group');

        $this->fixtures->create('group:default_claimed_merchants_group');
    }

    protected function createAdmins()
    {
        $this->admin1 = $this->fixtures->create('admin',
                                                [Admin\Entity::ID     => '19000000000011',
                                                 Admin\Entity::ORG_ID => $this->orgId,
                                                 Admin\Entity::EMAIL  => 'xyz@rzp.com'])
                                       ->getId();

        $this->admin2 = $this->fixtures->create('admin',
                                                [Admin\Entity::ID     => '19000000000012',
                                                 Admin\Entity::ORG_ID => $this->orgId,
                                                 Admin\Entity::EMAIL  => 'abc@rzp.com'])
                                       ->getId();

        $this->admin3 = $this->fixtures->create('admin',
                                                [Admin\Entity::ID     => '19000000000013',
                                                 Admin\Entity::ORG_ID => $this->orgId,
                                                 Admin\Entity::EMAIL  => 'rst@rzp.com'])
                                       ->getId();

        $this->admin4 = $this->fixtures->create('admin',
                                                [Admin\Entity::ID     => '19000000000099',
                                                 Admin\Entity::ORG_ID => $this->orgId,
                                                 Admin\Entity::EMAIL  => 'rstabd@rzp.com'])
                                       ->getId();
    }

    protected function createMerchants()
    {
        $this->merchant1 = $this->fixtures->create('merchant',
                                                   ['id'    => '10000000000002',
                                                    'email' => 'razorpay@razorpay.com'])
                                          ->getId();

        $this->merchant2 = $this->fixtures->create('merchant',
                                                   ['id'    => '10000000000003',
                                                    'email' => 'rzp2@razorpay.com'])
                                          ->getId();

        $this->merchant3 = $this->fixtures->create('merchant',
                                                   ['id'    => '10000000000004',
                                                    'email' => 'rzp3@razorpay.com'])
                                          ->getId();

        $this->merchant4 = $this->fixtures->create('merchant',
                                                   ['id'    => '10000000000005',
                                                    'email' => 'rzp4@razorpay.com'])
                                          ->getId();
        $this->fixtures->create('merchant', ['id'    => '10000000000028',
                                             'email' => 'razorpay@razorpay.com']);

        $this->fixtures->merchant->addFeatures(['marketplace'], '10000000000028');

        $account = $this->fixtures->create('merchant',
                                           ['id'    => '10000000000088',
                                            'email' => 'rzp3@razorpay.com', 'parent_id' => '10000000000028']);

        $this->fixtures->edit('merchant', $account->getId(), ['category' => '1100']);

        $this->fixtures->create('merchant', ['id'    => '10000000000448',
                                             'email' => 'razorpay1@razorpay.com']);

        $this->fixtures->merchant->addFeatures(['marketplace'], '10000000000448');

        $account1 = $this->fixtures->create('merchant',
                                            ['id'    => '10000000000888',
                                             'email' => 'rzp30@razorpay.com', 'parent_id' => '10000000000448']);

        $this->fixtures->edit('merchant', $account1->getId(), ['category' => '1100']);
    }

    protected function insertingMerchantsIntoGroups(string $merchantId, string $groupId)
    {
        \DB::table('merchant_map')->insert(array(
                                               'merchant_id' => $merchantId,
                                               'entity_id'   => $groupId,
                                               'entity_type' => 'group')
        );
    }

    protected function getPivotMap(string $tableName, string $columnName, string $columnValue)
    {
        $pivotMap = \DB::connection('test')->table($tableName)
                       ->where($columnName, $columnValue)
                       ->get()->toArray();

        return json_decode(json_encode($pivotMap), true);
    }

    protected function getLinkParentMerchantMap()
    {
        $merchantMap = \DB::connection('test')->table('merchant_map')
                          ->where('merchant_id', '10000000000028')
                          ->get()->toArray();

        return json_decode(json_encode($merchantMap), true);
    }

    protected function getLinkChildMerchantMap()
    {
        $merchantMap = \DB::connection('test')->table('merchant_map')
                          ->where('merchant_id', '10000000000088')
                          ->get()->toArray();

        return json_decode(json_encode($merchantMap), true);
    }

    // Merchant should be removed from default unclaimed group
    public function testRemovalFromUnclaimedGroup()
    {
        $this->insertingMerchantsIntoGroups('10000000000002',
                                            Group\Constant::SF_UNCLAIMED_GROUP_ID);

        $testData = &$this->testData[__FUNCTION__];

        $this->ba->adminAuth();

        $this->startTest();

        $merchantMap = $this->getPivotMap(self::MERCHANT_MAP,
                                          self::ENTITY_ID,
                                          Group\Constant::SF_UNCLAIMED_GROUP_ID);

        $this->assertEquals(0, count($merchantMap));
    }

    // Non-sme merchant should be assigned to admins
    public function testNonSmeMerchantsPoc()
    {
        $expectedMerchantMap = [['merchant_id' => '10000000000002',
                                 'entity_id'   => '19000000000011',
                                 'entity_type' => 'admin'],
                                ['merchant_id' => '10000000000002',
                                 'entity_id'   => '19000000000012',
                                 'entity_type' => 'admin'],
                                ['merchant_id' => '10000000000002',
                                 'entity_id'   => '19000000000013',
                                 'entity_type' => 'admin'],
                                ['merchant_id' => '10000000000002',
                                 'entity_id'   => Group\Constant::SF_CLAIMED_MERCHANTS_GROUP_ID,
                                 'entity_type' => 'group']];

        $testData = &$this->testData[__FUNCTION__];

        $this->ba->adminAuth();

        $this->startTest();

        $merchantMap = \DB::connection('test')->table('merchant_map')
                          ->where('merchant_id', '10000000000002')
                          ->get()->toArray();

        $merchantMap = json_decode(json_encode($merchantMap), true);

        $this->assertArraySelectiveEquals($expectedMerchantMap, $merchantMap);
    }

    // Non-sme Linked merchant should be assigned to same admins
    public function testLinkedNonSmeMerchantsPoc()
    {
        $expectedParentMerchantMap = [['merchant_id' => '10000000000028',
                                       'entity_id'   => '19000000000011',
                                       'entity_type' => 'admin'],
                                      ['merchant_id' => '10000000000028',
                                       'entity_id'   => '19000000000012',
                                       'entity_type' => 'admin'],
                                      ['merchant_id' => '10000000000028',
                                       'entity_id'   => '19000000000013',
                                       'entity_type' => 'admin'],
                                      ['merchant_id' => '10000000000028',
                                       'entity_id'   => Group\Constant::SF_CLAIMED_MERCHANTS_GROUP_ID,
                                       'entity_type' => 'group']];

        $expectedChildMerchantMap = [['merchant_id' => '10000000000088',
                                      'entity_id'   => '19000000000011',
                                      'entity_type' => 'admin'],
                                     ['merchant_id' => '10000000000088',
                                      'entity_id'   => '19000000000012',
                                      'entity_type' => 'admin'],
                                     ['merchant_id' => '10000000000088',
                                      'entity_id'   => '19000000000013',
                                      'entity_type' => 'admin'],
                                     ['merchant_id' => '10000000000088',
                                      'entity_id'   => Group\Constant::SF_CLAIMED_MERCHANTS_GROUP_ID,
                                      'entity_type' => 'group']];

        $testData = &$this->testData[__FUNCTION__];

        $this->ba->adminAuth();

        $this->startTest();

        $parentMerchantMap = $this->getLinkParentMerchantMap();

        $this->assertArraySelectiveEquals($expectedParentMerchantMap, $parentMerchantMap);

        $childMerchantMap = $this->getLinkChildMerchantMap();

        $this->assertArraySelectiveEquals($expectedChildMerchantMap, $childMerchantMap);
    }

    // Sme merchant should be assigned to SME groups
    public function testSmeMerchantsPoc()
    {
        $expectedSmeGroupMap = [['group_id'    => Group\Constant::SF_CLAIMED_SME_GROUP_ID,
                                 'entity_id'   => '19000000000011',
                                 'entity_type' => 'admin'],
                                ['group_id'    => Group\Constant::SF_CLAIMED_SME_GROUP_ID,
                                 'entity_id'   => '19000000000012',
                                 'entity_type' => 'admin'],
                                ['group_id'    => Group\Constant::SF_CLAIMED_SME_GROUP_ID,
                                 'entity_id'   => '19000000000013',
                                 'entity_type' => 'admin']];

        $expectedUnclaimedGroupMap = [['group_id'    => Group\Constant::SF_UNCLAIMED_GROUP_ID,
                                       'entity_id'   => '19000000000011',
                                       'entity_type' => 'admin'],
                                      ['group_id'    => Group\Constant::SF_UNCLAIMED_GROUP_ID,
                                       'entity_id'   => '19000000000012',
                                       'entity_type' => 'admin'],
                                      ['group_id'    => Group\Constant::SF_UNCLAIMED_GROUP_ID,
                                       'entity_id'   => '19000000000013',
                                       'entity_type' => 'admin']];

        $expectedMerchantMap = [['merchant_id' => '10000000000003',
                                 'entity_id'   => Group\Constant::SF_CLAIMED_MERCHANTS_GROUP_ID,
                                 'entity_type' => 'group'],
                                ['merchant_id' => '10000000000003',
                                 'entity_id'   => Group\Constant::SF_CLAIMED_SME_GROUP_ID,
                                 'entity_type' => 'group']];

        $testData = &$this->testData[__FUNCTION__];

        $this->ba->adminAuth();

        $this->startTest();

        $merchantMap = \DB::connection('test')->table('merchant_map')
                          ->where('merchant_id', '10000000000003')
                          ->get()->toArray();

        $merchantMap = json_decode(json_encode($merchantMap), true);

        $this->assertArraySelectiveEquals($expectedMerchantMap, $merchantMap);

        $smeGroupMap = $this->getPivotMap(self::GROUP_MAP,
                                          self::GROUP_ID,
                                          Group\Constant::SF_CLAIMED_SME_GROUP_ID);

        $this->assertArraySelectiveEquals($expectedSmeGroupMap, $smeGroupMap);

        $unclaimedGroupMap = $this->getPivotMap(self::GROUP_MAP,
                                                self::GROUP_ID,
                                                Group\Constant::SF_UNCLAIMED_GROUP_ID);

        $this->assertArraySelectiveEquals($expectedUnclaimedGroupMap, $unclaimedGroupMap);
    }

    // Sme linked merchant parent and child should be assigned to SME groups
    public function testLinkedSmeMerchantsPoc()
    {
        $expectedSmeGroupMap = [['group_id'    => Group\Constant::SF_CLAIMED_SME_GROUP_ID,
                                 'entity_id'   => '19000000000011',
                                 'entity_type' => 'admin'],
                                ['group_id'    => Group\Constant::SF_CLAIMED_SME_GROUP_ID,
                                 'entity_id'   => '19000000000012',
                                 'entity_type' => 'admin'],
                                ['group_id'    => Group\Constant::SF_CLAIMED_SME_GROUP_ID,
                                 'entity_id'   => '19000000000013',
                                 'entity_type' => 'admin']];

        $expectedUnclaimedGroupMap = [['group_id'    => Group\Constant::SF_UNCLAIMED_GROUP_ID,
                                       'entity_id'   => '19000000000011',
                                       'entity_type' => 'admin'],
                                      ['group_id'    => Group\Constant::SF_UNCLAIMED_GROUP_ID,
                                       'entity_id'   => '19000000000012',
                                       'entity_type' => 'admin'],
                                      ['group_id'    => Group\Constant::SF_UNCLAIMED_GROUP_ID,
                                       'entity_id'   => '19000000000013',
                                       'entity_type' => 'admin']];

        $expectedParentMerchantMap = [['merchant_id' => '10000000000028',
                                       'entity_id'   => Group\Constant::SF_CLAIMED_MERCHANTS_GROUP_ID,
                                       'entity_type' => 'group'],
                                      ['merchant_id' => '10000000000028',
                                       'entity_id'   => Group\Constant::SF_CLAIMED_SME_GROUP_ID,
                                       'entity_type' => 'group']];

        $expectedChildMerchantMap = [['merchant_id' => '10000000000088',
                                      'entity_id'   => Group\Constant::SF_CLAIMED_MERCHANTS_GROUP_ID,
                                      'entity_type' => 'group'],
                                     ['merchant_id' => '10000000000088',
                                      'entity_id'   => Group\Constant::SF_CLAIMED_SME_GROUP_ID,
                                      'entity_type' => 'group']];

        \DB::table('merchant_map')->insert(array(
                                               'merchant_id' => '10000000000028',
                                               'entity_id'   => Group\Constant::SF_UNCLAIMED_GROUP_ID,
                                               'entity_type' => 'group'
                                           )
        );

        \DB::table('merchant_map')->insert(array(
                                               'merchant_id' => '10000000000088',
                                               'entity_id'   => Group\Constant::SF_UNCLAIMED_GROUP_ID,
                                               'entity_type' => 'group'
                                           )
        );
        $testData = &$this->testData[__FUNCTION__];

        $this->ba->adminAuth();

        $this->startTest();

        $parentMerchantMap = $this->getLinkParentMerchantMap();

        $this->assertArraySelectiveEquals($expectedParentMerchantMap, $parentMerchantMap);

        $childMerchantMap = $this->getLinkChildMerchantMap();

        $this->assertArraySelectiveEquals($expectedChildMerchantMap, $childMerchantMap);

        $smeGroupMap = $this->getPivotMap(self::GROUP_MAP,
                                          self::GROUP_ID,
                                          Group\Constant::SF_CLAIMED_SME_GROUP_ID);

        $this->assertArraySelectiveEquals($expectedSmeGroupMap, $smeGroupMap);

        $unclaimedGroupMap = $this->getPivotMap(self::GROUP_MAP,
                                                self::GROUP_ID,
                                                Group\Constant::SF_UNCLAIMED_GROUP_ID);

        $this->assertArraySelectiveEquals($expectedUnclaimedGroupMap, $unclaimedGroupMap);
    }

    // Sme and Non-SME linked merchant parent and child should be assigned to SME groups and admins respectively
    public function testLinkedSmeAndNonSMEMerchantsPoc()
    {
        $testData = &$this->testData[__FUNCTION__];

        $this->ba->adminAuth();

        $this->startTest();

        $merchantMapForSMEGroup = $this->getPivotMap(self::MERCHANT_MAP,
                                                     self::ENTITY_ID,
                                                     Group\Constant::SF_CLAIMED_SME_GROUP_ID);

        $merchantMapForClaimedGroup = $this->getPivotMap(self::MERCHANT_MAP,
                                                         self::ENTITY_ID,
                                                         Group\Constant::SF_CLAIMED_MERCHANTS_GROUP_ID);

        $groupMapForSMEMerchant = $this->getPivotMap(self::GROUP_MAP,
                                                     self::GROUP_ID,
                                                     Group\Constant::SF_CLAIMED_SME_GROUP_ID);

        $groupMapUnclaimedGroup = $this->getPivotMap(self::GROUP_MAP,
                                                     self::GROUP_ID,
                                                     Group\Constant::SF_UNCLAIMED_GROUP_ID);

        //One individual account one linked account with one subordinate
        $this->assertEquals(3, count($merchantMapForSMEGroup));

        //SME group assigned to three admin
        $this->assertEquals(3, count($groupMapForSMEMerchant));

        //Three Merchant for SME and three Non-SME merchants
        $this->assertEquals(6, count($merchantMapForClaimedGroup));

        //Unclaimed group assigned to three admin
        $this->assertEquals(3, count($groupMapUnclaimedGroup));
    }

    // Sme linked merchant parent and child belongs to other group before assigned to SME,
    // it should be assigned to that group after assigned to SME claimed group
    public function testLinkedSmeBelongsToOtherGroup()
    {
        $expectedOtherGroupMap = [['group_id'    => '99000000000009',
                                   'entity_id'   => '19000000000099',
                                   'entity_type' => 'admin'],
        ];

        $this->otherGroup = $this->fixtures->create('group', ['id'     => '99000000000009',
                                                              'org_id' => $this->orgId]);

        \DB::table('group_map')->insert(array(
                                            'group_id'    => '99000000000009',
                                            'entity_id'   => '19000000000099',
                                            'entity_type' => 'admin'
                                        )
        );

        // It's handle when non sme merchants already have other admin
        \DB::table('merchant_map')->insert(array(
                                               'merchant_id' => '10000000000028',
                                               'entity_id'   => '99000000000009',
                                               'entity_type' => 'group'
                                           )
        );

        // It's handle when  sme merchants already assigned to other admin and next salesforce admin update happen
        \DB::table('merchant_map')->insert(array(
                                               'merchant_id' => '10000000000088',
                                               'entity_id'   => '99000000000009',
                                               'entity_type' => 'group'
                                           )
        );

        $testData = &$this->testData[__FUNCTION__];

        $this->ba->adminAuth();

        $this->startTest();

        $merchantMapForSMEMerchant = $this->getLinkParentMerchantMap();

        // Two for Claimed Merchant and SME Merchant Group Respectively
        // one for previously assigned group for SME merchant
        $this->assertEquals(3, count($merchantMapForSMEMerchant));

        $otherGroupMap = \DB::connection('test')->table('group_map')
                            ->where('entity_id', '19000000000099')
                            ->get()->toArray();

        $otherGroupMap = json_decode(json_encode($otherGroupMap), true);

        $this->assertArraySelectiveEquals($expectedOtherGroupMap, $otherGroupMap);

    }

    // Sme linked merchant parent and child belongs to other admin before assigned to SME claimed group,
    // it should be removed from previous admins after assigned to SME claimed group
    public function testLinkedSmeBelongsToOtherAdmin()
    {
        // It's handle when non sme merchants already have other admin
        \DB::table('merchant_map')->insert(array(
                                               'merchant_id' => '10000000000028',
                                               'entity_id'   => '19000000000099',
                                               'entity_type' => 'admin'
                                           )
        );

        // It's handle when  sme merchants already assigned to other admin and next salesforce admin update happen
        \DB::table('merchant_map')->insert(array(
                                               'merchant_id' => '10000000000088',
                                               'entity_id'   => '19000000000099',
                                               'entity_type' => 'admin'
                                           )
        );

        $testData = &$this->testData[__FUNCTION__];

        $this->ba->adminAuth();

        $this->startTest();

        $otherMerchantMap = \DB::connection('test')->table('merchant_map')
                               ->where('entity_id', '19000000000099')
                               ->get()->toArray();

        //The Previous Admin Should be detached from merchant
        $this->assertEquals(0, count($otherMerchantMap));
    }

    // Non-sme linked merchant parent and child belongs to other group before assigned to new admins,
    // it should be assigned to that group after assigned to admins
    public function testLinkedNonSmeBelongsToOtherGroup()
    {
        $expectedOtherGroupMap = [['group_id'    => '99000000000009',
                                   'entity_id'   => '19000000000099',
                                   'entity_type' => 'admin'],
        ];

        $this->otherGroup = $this->fixtures->create('group', ['id'     => '99000000000009',
                                                              'org_id' => $this->orgId]);

        \DB::table('group_map')->insert(array(
                                            'group_id'    => '99000000000009',
                                            'entity_id'   => '19000000000099',
                                            'entity_type' => 'admin'
                                        )
        );

        // It's handle when non sme merchants already have other admin
        \DB::table('merchant_map')->insert(array(
                                               'merchant_id' => '10000000000028',
                                               'entity_id'   => '99000000000009',
                                               'entity_type' => 'group'
                                           )
        );

        // It's handle when  sme merchants already assigned to other admin and next salesforce admin update happen
        \DB::table('merchant_map')->insert(array(
                                               'merchant_id' => '10000000000088',
                                               'entity_id'   => '99000000000009',
                                               'entity_type' => 'group'
                                           )
        );

        $testData = &$this->testData[__FUNCTION__];

        $this->ba->adminAuth();

        $this->startTest();

        $merchantMapForNonSMEMerchant = \DB::connection('test')->table('merchant_map')
                                           ->where('entity_id', '99000000000009')
                                           ->get()->toArray();

        //Two for Parent and Child Merchant
        $this->assertEquals(2, count($merchantMapForNonSMEMerchant));

        $otherGroupMap = \DB::connection('test')->table('group_map')
                            ->where('entity_id', '19000000000099')
                            ->get()->toArray();

        $otherGroupMap = json_decode(json_encode($otherGroupMap), true);

        $this->assertArraySelectiveEquals($expectedOtherGroupMap, $otherGroupMap);
    }

    // Non-sme linked merchant parent and child belongs to other admin before assigned to new admins,
    // it should be removed from previous admins after assigned to new admins
    public function testLinkedNonSmeBelongsToOtherAdmin()
    {
        // It's handle when non sme merchants already have other admin
        \DB::table('merchant_map')->insert(array(
                                               'merchant_id' => '10000000000028',
                                               'entity_id'   => '19000000000099',
                                               'entity_type' => 'admin'
                                           )
        );

        // It's handle when  sme merchants already assigned to other admin and next salesforce admin update happen
        \DB::table('merchant_map')->insert(array(
                                               'merchant_id' => '10000000000088',
                                               'entity_id'   => '19000000000099',
                                               'entity_type' => 'admin'
                                           )
        );

        $testData = &$this->testData[__FUNCTION__];

        $this->ba->adminAuth();

        $this->startTest();

        $merchantMapForSMEMerchant = \DB::connection('test')->table('merchant_map')
                                        ->where('entity_id', '19000000000099')
                                        ->get()->toArray();

        //The Previous Admin Should be detached from merchant
        $this->assertEquals(0, count($merchantMapForSMEMerchant));
    }

    // Previous iteration if some linked non-sme merchants belongs to some admins,
    // in new data if those merchants not present,
    // so the previous merchants admins should be removed and from claimed group
    // and moved to unclaimed group
    public function testLinkedNonSmeClaimedToUnclaimed()
    {
        $expectedMerchantMapForClaimedGroup = [['merchant_id' => '10000000000003',
                                                'entity_id'   => Group\Constant::SF_CLAIMED_MERCHANTS_GROUP_ID,
                                                'entity_type' => 'group'],
                                               ['merchant_id' => '10000000000004',
                                                'entity_id'   => Group\Constant::SF_CLAIMED_MERCHANTS_GROUP_ID,
                                                'entity_type' => 'group'],
                                               ['merchant_id' => '10000000000448',
                                                'entity_id'   => Group\Constant::SF_CLAIMED_MERCHANTS_GROUP_ID,
                                                'entity_type' => 'group'],
                                               ['merchant_id' => '10000000000888',
                                                'entity_id'   => Group\Constant::SF_CLAIMED_MERCHANTS_GROUP_ID,
                                                'entity_type' => 'group']];

        $expectedMerchantMapForUnclaimedGroup = [['merchant_id' => '10000000000028',
                                                  'entity_id'   => Group\Constant::SF_UNCLAIMED_GROUP_ID,
                                                  'entity_type' => 'group'],
                                                 ['merchant_id' => '10000000000088',
                                                  'entity_id'   => Group\Constant::SF_UNCLAIMED_GROUP_ID,
                                                  'entity_type' => 'group']];

        // It's handle when non sme merchants already have other admin
        \DB::table('merchant_map')->insert(array(
                                               'merchant_id' => '10000000000028',
                                               'entity_id'   => '19000000000099',
                                               'entity_type' => 'admin'
                                           )
        );

        \DB::table('merchant_map')->insert(array(
                                               'merchant_id' => '10000000000088',
                                               'entity_id'   => '19000000000099',
                                               'entity_type' => 'admin'
                                           )
        );

        $this->insertingMerchantsIntoGroups('10000000000028',
                                            Group\Constant::SF_CLAIMED_MERCHANTS_GROUP_ID);

        $this->insertingMerchantsIntoGroups('10000000000088',
                                            Group\Constant::SF_CLAIMED_MERCHANTS_GROUP_ID);

        $testData = &$this->testData[__FUNCTION__];

        $this->ba->adminAuth();

        $this->startTest();

        $merchantMapForClaimedGroup = $this->getPivotMap(self::MERCHANT_MAP,
                                                         self::ENTITY_ID,
                                                         Group\Constant::SF_CLAIMED_MERCHANTS_GROUP_ID);

        $this->assertArraySelectiveEquals($expectedMerchantMapForClaimedGroup, $merchantMapForClaimedGroup);

        $merchantMapForUnclaimedGroup = $this->getPivotMap(self::MERCHANT_MAP,
                                                           self::ENTITY_ID,
                                                           Group\Constant::SF_UNCLAIMED_GROUP_ID);

        $this->assertArraySelectiveEquals($expectedMerchantMapForUnclaimedGroup, $merchantMapForUnclaimedGroup);
    }

    // Previous iteration if some linked sme merchants belongs to some admins,
    // in new data if those merchants not present,
    // so the previous merchants should be removed from claimed and sme group
    // and moved to unclaimed group
    public function testLinkedSmeClaimedToUnclaimed()
    {
        $expectedMerchantMapForClaimedGroup = [['merchant_id' => '10000000000003',
                                                'entity_id'   => Group\Constant::SF_CLAIMED_MERCHANTS_GROUP_ID,
                                                'entity_type' => 'group'],
                                               ['merchant_id' => '10000000000004',
                                                'entity_id'   => Group\Constant::SF_CLAIMED_MERCHANTS_GROUP_ID,
                                                'entity_type' => 'group'],
                                               ['merchant_id' => '10000000000448',
                                                'entity_id'   => Group\Constant::SF_CLAIMED_MERCHANTS_GROUP_ID,
                                                'entity_type' => 'group'],
                                               ['merchant_id' => '10000000000888',
                                                'entity_id'   => Group\Constant::SF_CLAIMED_MERCHANTS_GROUP_ID,
                                                'entity_type' => 'group']];

        $expectedMerchantMapForClaimedSmeGroup = [['merchant_id' => '10000000000004',
                                                   'entity_id'   => Group\Constant::SF_CLAIMED_SME_GROUP_ID,
                                                   'entity_type' => 'group'],
                                                  ['merchant_id' => '10000000000448',
                                                   'entity_id'   => Group\Constant::SF_CLAIMED_SME_GROUP_ID,
                                                   'entity_type' => 'group'],
                                                  ['merchant_id' => '10000000000888',
                                                   'entity_id'   => Group\Constant::SF_CLAIMED_SME_GROUP_ID,
                                                   'entity_type' => 'group']];

        $expectedMerchantMapForUnclaimedGroup = [['merchant_id' => '10000000000028',
                                                  'entity_id'   => Group\Constant::SF_UNCLAIMED_GROUP_ID,
                                                  'entity_type' => 'group'],
                                                 ['merchant_id' => '10000000000088',
                                                  'entity_id'   => Group\Constant::SF_UNCLAIMED_GROUP_ID,
                                                  'entity_type' => 'group']];

        $expectedGroupMapForClaimedSmeGroup = [['group_id'    => Group\Constant::SF_CLAIMED_SME_GROUP_ID,
                                                'entity_id'   => '19000000000011',
                                                'entity_type' => 'admin'],
                                               ['group_id'    => Group\Constant::SF_CLAIMED_SME_GROUP_ID,
                                                'entity_id'   => '19000000000012',
                                                'entity_type' => 'admin'],
                                               ['group_id'    => Group\Constant::SF_CLAIMED_SME_GROUP_ID,
                                                'entity_id'   => '19000000000013',
                                                'entity_type' => 'admin']];

        // It's handle when non sme merchants already have other admin
        \DB::table('merchant_map')->insert(array(
                                               'merchant_id' => '10000000000028',
                                               'entity_id'   => '19000000000099',
                                               'entity_type' => 'admin'
                                           )
        );

        \DB::table('merchant_map')->insert(array(
                                               'merchant_id' => '10000000000088',
                                               'entity_id'   => '19000000000099',
                                               'entity_type' => 'admin'
                                           )
        );

        $this->insertingMerchantsIntoGroups('10000000000028',
                                            Group\Constant::SF_CLAIMED_MERCHANTS_GROUP_ID);

        $this->insertingMerchantsIntoGroups('10000000000088',
                                            Group\Constant::SF_CLAIMED_MERCHANTS_GROUP_ID);

        $testData = &$this->testData[__FUNCTION__];

        $this->ba->adminAuth();

        $this->startTest();

        $merchantMapForClaimedGroup = $this->getPivotMap(self::MERCHANT_MAP,
                                                         self::ENTITY_ID,
                                                         Group\Constant::SF_CLAIMED_MERCHANTS_GROUP_ID);

        $this->assertArraySelectiveEquals($expectedMerchantMapForClaimedGroup, $merchantMapForClaimedGroup);

        $merchantMapForUnclaimedGroup = $this->getPivotMap(self::MERCHANT_MAP,
                                                           self::ENTITY_ID,
                                                           Group\Constant::SF_UNCLAIMED_GROUP_ID);

        $this->assertArraySelectiveEquals($expectedMerchantMapForUnclaimedGroup, $merchantMapForUnclaimedGroup);

        $merchantMapForClaimedSmeGroup = $this->getPivotMap(self::MERCHANT_MAP,
                                                            self::ENTITY_ID,
                                                            Group\Constant::SF_CLAIMED_SME_GROUP_ID);

        $this->assertArraySelectiveEquals($expectedMerchantMapForClaimedSmeGroup, $merchantMapForClaimedSmeGroup);

        $groupMapForClaimedSmeGroup = $this->getPivotMap(self::GROUP_MAP,
                                                         self::GROUP_ID,
                                                         Group\Constant::SF_CLAIMED_SME_GROUP_ID);

        $this->assertArraySelectiveEquals($expectedGroupMapForClaimedSmeGroup, $groupMapForClaimedSmeGroup);
    }

    // Special case Linked SME merchants
    // M1 assigned admin A1
    // M2 assigned admins A1
    // so both assigned to SME and SME assigned to A1
    // now in Next iteration  M1, M2 and admins are A1 and A2 respectively,
    // so A1 should be synced in M1 case and A1 should be removed in M2 case,
    // but ultimately SME should be assigned to both A1 and A2
    public function testLinkedSmeAdminChanged()
    {
        $expectedMerchantMapForClaimedGroup = [['merchant_id' => '10000000000002',
                                                'entity_id'   => Group\Constant::SF_CLAIMED_MERCHANTS_GROUP_ID,
                                                'entity_type' => 'group'],
                                               ['merchant_id' => '10000000000028',
                                                'entity_id'   => Group\Constant::SF_CLAIMED_MERCHANTS_GROUP_ID,
                                                'entity_type' => 'group'],
                                               ['merchant_id' => '10000000000088',
                                                'entity_id'   => Group\Constant::SF_CLAIMED_MERCHANTS_GROUP_ID,
                                                'entity_type' => 'group']];

        $expectedMerchantMapForClaimedSmeGroup = [['merchant_id' => '10000000000002',
                                                   'entity_id'   => Group\Constant::SF_CLAIMED_SME_GROUP_ID,
                                                   'entity_type' => 'group'],
                                                  ['merchant_id' => '10000000000028',
                                                   'entity_id'   => Group\Constant::SF_CLAIMED_SME_GROUP_ID,
                                                   'entity_type' => 'group'],
                                                  ['merchant_id' => '10000000000088',
                                                   'entity_id'   => Group\Constant::SF_CLAIMED_SME_GROUP_ID,
                                                   'entity_type' => 'group']];

        $expectedGroupMapForClaimedSmeGroup = [['group_id'    => Group\Constant::SF_CLAIMED_SME_GROUP_ID,
                                                'entity_id'   => '19000000000011',
                                                'entity_type' => 'admin'],
                                               ['group_id'    => Group\Constant::SF_CLAIMED_SME_GROUP_ID,
                                                'entity_id'   => '19000000000012',
                                                'entity_type' => 'admin']];

        $this->insertingMerchantsIntoGroups('10000000000028',
                                            Group\Constant::SF_CLAIMED_MERCHANTS_GROUP_ID);

        $this->insertingMerchantsIntoGroups('10000000000088',
                                            Group\Constant::SF_CLAIMED_MERCHANTS_GROUP_ID);

        $this->insertingMerchantsIntoGroups('10000000000002',
                                            Group\Constant::SF_CLAIMED_MERCHANTS_GROUP_ID);

        \DB::table('merchant_map')->insert(array(
                                               'merchant_id' => '10000000000028',
                                               'entity_id'   => '19000000000099',
                                               'entity_type' => 'admin'
                                           )
        );

        \DB::table('merchant_map')->insert(array(
                                               'merchant_id' => '10000000000088',
                                               'entity_id'   => '19000000000099',
                                               'entity_type' => 'admin'
                                           )
        );

        \DB::table('group_map')->insert(array(
                                            'group_id'    => Group\Constant::SF_CLAIMED_SME_GROUP_ID,
                                            'entity_id'   => '19000000000011',
                                            'entity_type' => 'admin'
                                        )
        );

        $testData = &$this->testData[__FUNCTION__];

        $this->ba->adminAuth();

        $this->startTest();

        $merchantMapForClaimedGroup = $this->getPivotMap(self::MERCHANT_MAP,
                                                         self::ENTITY_ID,
                                                         Group\Constant::SF_CLAIMED_MERCHANTS_GROUP_ID);

        $this->assertArraySelectiveEquals($expectedMerchantMapForClaimedGroup, $merchantMapForClaimedGroup);

        $merchantMapForClaimedSmeGroup = $this->getPivotMap(self::MERCHANT_MAP,
                                                            self::ENTITY_ID,
                                                            Group\Constant::SF_CLAIMED_SME_GROUP_ID);

        $this->assertArraySelectiveEquals($expectedMerchantMapForClaimedSmeGroup, $merchantMapForClaimedSmeGroup);

        $groupMapForClaimedSmeGroup = $this->getPivotMap(self::GROUP_MAP,
                                                         self::GROUP_ID,
                                                         Group\Constant::SF_CLAIMED_SME_GROUP_ID);

        $this->assertArraySelectiveEquals($expectedGroupMapForClaimedSmeGroup, $groupMapForClaimedSmeGroup);
    }
}
