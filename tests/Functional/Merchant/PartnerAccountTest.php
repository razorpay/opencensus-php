<?php

namespace RZP\Tests\Functional\Merchant\Account;

use Illuminate\Database\Eloquent\Factory;

use RZP\Models\Merchant\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Merchant\CommissionTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class PartnerAccountTest extends TestCase
{
    use PaymentTrait;
    use CommissionTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/PartnerAccountTestData.php';

        parent::setUp();

        $factoryPath = base_path() . '/vendor/razorpay/oauth/database/factories';

        $this->app->make(Factory::class)->load($factoryPath);

        $this->mockCardVault();
    }

    public function testCreateAccountForCompletelyFilledRequest()
    {
        $this->markMerchantAsNonPurePlatformPartner('10000000000000', Constants::AGGREGATOR);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCreateAccountForThinRequest()
    {
        $this->markMerchantAsNonPurePlatformPartner('10000000000000', Constants::AGGREGATOR);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCreateAccountWithDuplicateEmail()
    {
        $this->markMerchantAsNonPurePlatformPartner('10000000000000', Constants::AGGREGATOR);

        $this->ba->privateAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request'] = $this->testData['testCreateAccountForThinRequest']['request'];
        $testData['request']['content']['email'] = 'email.Ojha@test.com';

        $this->startTest($testData);
    }

    public function testCreateAccountWithInvalidMCCCode()
    {
        $this->markMerchantAsNonPurePlatformPartner('10000000000000', Constants::AGGREGATOR);

        $this->ba->privateAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request'] = $this->testData['testCreateAccountForThinRequest']['request'];
        $testData['request']['content']['profile']['mcc'] = '1234';

        $this->startTest($testData);
    }

    public function testCreateAccountForInvalidPartner()
    {
        $this->markMerchantAsNonPurePlatformPartner('10000000000000', Constants::RESELLER);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testFetchAccount()
    {
        $this->markMerchantAsNonPurePlatformPartner('10000000000000', Constants::AGGREGATOR);

        $this->ba->privateAuth();

        $testData = $this->testData['testCreateAccountForThinRequest'];

        $result = $this->startTest($testData);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/accounts/'. $result['id'];

        $this->startTest($testData);
    }

    public function testFetchAllAccounts()
    {
        $this->markMerchantAsNonPurePlatformPartner('10000000000000', Constants::AGGREGATOR);

        $this->ba->privateAuth();

        $testData = $this->testData['testCreateAccountForCompletelyFilledRequest'];

        $this->startTest($testData);

        $testData = $this->testData['testCreateAccountForThinRequest'];

        $this->startTest($testData);

        $result = $this->startTest();

        $this->assertCount(2, $result);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content'] = ['skip' => 1];

        $result = $this->startTest($testData);

        $this->assertCount(1, $result);
    }

    public function testEnableAccountAction()
    {
        $this->markMerchantAsNonPurePlatformPartner('10000000000000', Constants::AGGREGATOR);

        $this->ba->privateAuth();

        // creating account
        $testData = $this->testData['testCreateAccountForThinRequest'];

        $result = $this->startTest($testData);

        // disable account
        $testData = $this->testData['testDisableAccountAction'];

        $testData['request']['url'] = '/accounts/'. $result['id'] . '/disable';

        $this->startTest($testData);

        // enable account
        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/accounts/'. $result['id'] . '/enable';

        $this->startTest($testData);
    }
}
