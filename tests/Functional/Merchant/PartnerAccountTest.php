<?php

namespace RZP\Tests\Functional\Merchant\Account;

use Illuminate\Database\Eloquent\Factory;

use RZP\Constants\Mode;
use RZP\Models\User\Role;
use RZP\Models\Merchant\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class PartnerAccountTest extends TestCase
{
    use RequestResponseFlowTrait;
    use PartnerTrait;

    const RZP_ORG   = '100000razorpay';

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/PartnerAccountTestData.php';

        parent::setUp();

        $factoryPath = base_path() . '/vendor/razorpay/oauth/database/factories';

        $this->app->make(Factory::class)->load($factoryPath);
    }

    public function testCreateAccountForCompletelyFilledRequest()
    {
        $this->setUpNonPurePlatformPartner();

        $this->startTest();
    }

    public function testCreateAccountForThinRequest()
    {
        $this->setUpNonPurePlatformPartner();

        $this->startTest();
    }

    public function testCreateAccountWithDuplicateEmail()
    {
        $this->setUpNonPurePlatformPartner();

        $testData = $this->testData[__FUNCTION__];

        $testData['request'] = $this->testData['testCreateAccountForThinRequest']['request'];
        $testData['request']['content']['email'] = 'email.Ojha@test.com';

        $this->startTest($testData);
    }

    public function testCreateAccountWithInvalidMCCCode()
    {
        $this->setUpNonPurePlatformPartner();

        $testData = $this->testData[__FUNCTION__];

        $testData['request'] = $this->testData['testCreateAccountForThinRequest']['request'];
        $testData['request']['content']['profile']['mcc'] = '1234';

        $this->startTest($testData);
    }

    public function testCreateAccountWithoutRegisteredAddress()
    {
        $this->setUpNonPurePlatformPartner();

        $testData = $this->testData[__FUNCTION__];

        $testData['request'] = $this->testData['testCreateAccountForThinRequest']['request'];
        $testData['request']['content']['profile']['addresses'][0]['type'] = 'operation';

        $this->startTest($testData);
    }

    public function testCreateAccountForInvalidPartner()
    {
        $this->markMerchantAsNonPurePlatformPartner('10000000000000', Constants::RESELLER);

        $this->ba->privateAuth();

        $this->startTest();
    }

    /**
     * Test that changing all the attributes works
     */
    public function testEditAccount()
    {
        $this->setUpNonPurePlatformPartner();

        $testData = $this->testData['testCreateAccountForCompletelyFilledRequest'];

        $result = $this->runRequestResponseFlow($testData);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/accounts/'. $result['id'];

        $this->startTest($testData);
    }

    /**
     * Test that un-setting all the non required attributes works
     */
    public function testEditThinAccount()
    {
        $this->setUpNonPurePlatformPartner();

        $testData = $this->testData['testCreateAccountForCompletelyFilledRequest'];

        $result = $this->runRequestResponseFlow($testData);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/accounts/'. $result['id'];

        $this->startTest($testData);
    }

    public function testEditPhoneNumber()
    {
        $this->setUpNonPurePlatformPartner();

        $testData = $this->testData['testCreateAccountForCompletelyFilledRequest'];

        $result = $this->runRequestResponseFlow($testData);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/accounts/'. $result['id'];

        $this->startTest($testData);
    }

    public function testEditProfileData()
    {
        $this->setUpNonPurePlatformPartner();

        $testData = $this->testData['testCreateAccountForCompletelyFilledRequest'];

        $result = $this->runRequestResponseFlow($testData);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/accounts/'. $result['id'];

        $this->startTest($testData);
    }

    public function testFetchAccount()
    {
        $this->setUpNonPurePlatformPartner();

        $testData = $this->testData['testCreateAccountForThinRequest'];

        $result = $this->runRequestResponseFlow($testData);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/accounts/'. $result['id'];

        $this->startTest($testData);
    }

    public function testFetchAllAccounts()
    {
        $this->setUpNonPurePlatformPartner();

        $testData = $this->testData['testCreateAccountForCompletelyFilledRequest'];

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testCreateAccountForThinRequest'];

        $this->runRequestResponseFlow($testData);

        $result = $this->startTest();

        $this->assertCount(2, $result);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content'] = ['skip' => 1];

        $result = $this->startTest($testData);

        $this->assertCount(1, $result);
    }

    public function testEnableAccountAction()
    {
        $this->setUpNonPurePlatformPartner();

        // creating account
        $testData = $this->testData['testCreateAccountForThinRequest'];

        $result = $this->runRequestResponseFlow($testData);

        // disable account
        $testData = $this->testData['testDisableAccountAction'];

        $testData['request']['url'] = '/accounts/'. $result['id'] . '/disable';

        $this->runRequestResponseFlow($testData);

        // enable account
        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/accounts/'. $result['id'] . '/enable';

        $this->startTest($testData);
    }

    protected function setUpNonPurePlatformPartner()
    {
        $this->markMerchantAsNonPurePlatformPartner('10000000000000', Constants::AGGREGATOR);

        $this->fixtures->user->createUserForMerchant('10000000000000', [], Role::OWNER, Mode::LIVE);

        $orgHostName = $this->fixtures->org->build('org_hostname', [
            'org_id'    => self::RZP_ORG,
            'hostname'  => 'dashboard.razorpay.in'
        ]);

        $orgHostName->setConnection('live')->saveOrFail();

        // Merchant needs to be activated to make live requests
        $this->fixtures->merchant->edit('10000000000000', ['activated' => 1]);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');
    }
}
