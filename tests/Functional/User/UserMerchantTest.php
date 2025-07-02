<?php

namespace RZP\Tests\Functional\User;

use Mockery;
use RZP\Constants\Product;
use RZP\Models\Admin\Role\Service;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Traits\MocksSplitz;

class UserMerchantTest extends TestCase
{
    use MocksSplitz;
    use RequestResponseFlowTrait;

    protected $coreMock;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/UserMerchantTestData.php';

        parent::setUp();

        $this->app['config']->set('applications.authzXPlatformAdmin.mock', true);

        $this->createAndFetchMocks();
    }

    /**
     * testFetchMerchantsOfAUserForLoginWithPG
     * selects merchant to be logged in with, for a user with primary/pg product
     *
     */
    public function testFetchMerchantsOfAUserForLoginWithPG()
    {

        $user = $this->fixtures->create('user', [
            'id' => '100000razorpay',
            'password' => 'hello123'
        ]);

        $merchant = $user->getMerchantEntity();

        $merchant2 = $this->fixtures->create('merchant', [
            'name'=>'merchant2',
            MerchantEntity::ORG_ID => '100000razorpay',
        ]);

        $mappingData2 = [
            'user_id'       => $user->getId(),
            'merchant_id'   => $merchant2->getId(),
            'role'          => 'owner',
            'product'       => 'primary',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData2);

        $this->ba->idpInternalAuth();

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/users/' . $user->getId() . '/merchants';

        $response = $this->runRequestResponseFlow($testData);

        $this->assertEquals($merchant->getId(), $response['merchants'][0]['id']);
        $this->assertEquals($merchant2->getId(), $response['merchants'][1]['id']);
        $this->assertEquals($user->getId(), $response['id']);
    }

    /**
     * testFetchMerchantsOfAUserForLoginWithPGDefaultMID
     * uses default mid provided in the request and selects merchant to be logged in with for a user with product as pg
     *
     */
    public function testFetchMerchantsOfAUserForLoginWithPGDefaultMID()
    {

        $user = $this->fixtures->create('user', ['password' => 'hello123']);

        $merchant1 = $this->fixtures->create('merchant', [
            MerchantEntity::ORG_ID => '100000razorpay',
        ]);

        $merchant2 = $this->fixtures->create('merchant', [
            MerchantEntity::ORG_ID => '100000razorpay',
        ]);

        $merchant3 = $this->fixtures->create('merchant', [
            MerchantEntity::ORG_ID => '100000razorpay',
        ]);

        $mappingData1 = [
            'user_id'       => $user->getId(),
            'merchant_id'   => $merchant1->getId(),
            'role'          => 'owner',
            'product'       => 'primary',
        ];

        $mappingData2 = [
            'user_id'       => $user->getId(),
            'merchant_id'   => $merchant2->getId(),
            'role'          => 'owner',
            'product'       => 'primary',
        ];

        $mappingData3 = [
            'user_id'       => $user->getId(),
            'merchant_id'   => $merchant3->getId(),
            'role'          => 'support',
            'product'       => 'primary',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData1);
        $this->fixtures->create('user:user_merchant_mapping', $mappingData2);
        $this->fixtures->create('user:user_merchant_mapping', $mappingData3);

        $this->ba->idpInternalAuth();

        $testData = $this->testData['testFetchMerchantsOfAUserForLoginWithPG'];
        $testData['request']['url'] = '/users/' . $user->getId() . '/merchants';
        $testData['request']['content']['default_merchant_id'] = $merchant1->getId();

        $response = $this->runRequestResponseFlow($testData);

        $this->assertEquals($merchant1->getId(), $response['merchants'][0]['id']);
        $this->assertEquals($user->getId(), $response['id']);
    }

    /**
     * testFetchMerchantsOfAUserForLoginFromAnyProduct
     * it should only select merchant for the asked product not to fallback to other product owner role.
     *
     */
    public function testFetchMerchantsOfAUserForLoginWithSpecifiedProduct()
    {

        $user = $this->fixtures->create('user', [
            'id' => '100000razorpay',
            'password' => 'hello123'
        ]);

        $merchant2 = $this->fixtures->create('merchant', [
            MerchantEntity::ORG_ID => '100000razorpay',
        ]);

        $mappingData2 = [
            'user_id'       => $user->getId(),
            'merchant_id'   => $merchant2->getId(),
            'role'          => 'support',
            'product'       => 'banking',
        ];

        $merchant3 = $this->fixtures->create('merchant', [
            MerchantEntity::ORG_ID => '100000razorpay',
        ]);

        $mappingData3 = [
            'user_id'       => $user->getId(),
            'merchant_id'   => $merchant3->getId(),
            'role'          => 'owner',
            'product'       => 'primary',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData2);
        $this->fixtures->create('user:user_merchant_mapping', $mappingData3);

        $this->ba->idpInternalAuth();

        $testData = $this->testData['testFetchMerchantsOfAUserForLoginWithPG'];
        $testData['request']['url'] = '/users/' . $user->getId() . '/merchants';
        $testData['request']['content']['product'] = Product::BANKING;

        $response = $this->runRequestResponseFlow($testData);

        $this->assertEquals($merchant2->getId(), $response['merchants'][0]['id']);
    }


    /**
     * testFetchMerchantsOfAUserForLoginFromAnyProduct
     * no merchant linked to a user for the asked product
     *
     */
    public function testFetchMerchantsOfAUserForLoginWithNonexistingMerchantOnSpecifiedProduct()
    {

        $user = $this->fixtures->create('user', [
            'id' => '100000razorpay',
            'password' => 'hello123'
        ]);

        $merchant3 = $this->fixtures->create('merchant', [
            MerchantEntity::ORG_ID => '100000razorpay',
        ]);

        $mappingData3 = [
            'user_id'       => $user->getId(),
            'merchant_id'   => $merchant3->getId(),
            'role'          => 'owner',
            'product'       => 'primary',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData3);

        $this->ba->idpInternalAuth();

        $testData = $this->testData['testFetchMerchantsOfAUserForLoginWithPG'];
        $testData['request']['url'] = '/users/' . $user->getId() . '/merchants';
        $testData['request']['content']['product'] = Product::BANKING;

        $response = $this->runRequestResponseFlow($testData);

        $this->assertEquals([], $response['merchants']);
    }


    protected function createAndFetchMocks()
    {
        $mockMC = $this->getMockBuilder(MerchantCore::class)
            ->onlyMethods(['isRazorxExperimentEnable'])
            ->getMock();

        $mockMC->expects($this->any())
            ->method('isRazorxExperimentEnable')
            ->willReturn(true);

        // Core Mocking Partial
        $this->coreMock = Mockery::mock('RZP\Models\User\Core', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $roleServiceMock = $this->getMockBuilder(\RZP\Models\Roles\Service::class)
        ->setConstructorArgs([$this->app])
        ->onlyMethods(['getRoleNamesUsingExperiment'])
        ->getMock();

        $roleServiceMock->expects($this->any())->method('getRoleNamesUsingExperiment')->willReturn([]);

        return [
            "merchantCoreMock"    => $mockMC
        ];
    }
}
