<?php

namespace Functional\Merchant;

use RZP\Exception;
use RZP\Diag\EventCode;
use RZP\Models\Merchant;
use RZP\Services\DiagClient;
use RZP\Services\RazorXClient;
use RZP\Services\SalesForceClient;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;

class MerchantAttributeTest extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;
    use HeimdallTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantAttributeTestData.php';

        parent::setUp();

        $this->setUpMerchantForBusinessBanking(false, 100000);
    }

    public function testSwitchProductScenario()
    {
        $user = (new User())->createUserForMerchant();

        $this->fixtures->edit('merchant',
            '10000000000000',
            ['activated' => false, 'business_banking' => false, 'category2' => 'school']);

        $this->fixtures->create('merchant_detail',
            [
                'merchant_id' => '10000000000000',
                'activation_status' => 'pending'
            ]);

//        $this->fixtures->create('terminal:bank_account_terminal_for_business_banking',
//            ['merchant_id' => '100000Razorpay']);

        // To create a virtual account we need to enable bank transfer
        $this->fixtures->edit('methods', '10000000000000', ['bank_transfer' => true]);

        $liveBankingAccount = $this->getDbEntity('banking_account',
            [
                'merchant_id' => '10000000000000',
            ],
            'live');

        $this->assertNull($liveBankingAccount);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id'], 'owner');

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $this->startTest();
    }

    public function testSignupScenario()
    {
        $this->ba->adminAuth();

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $this->startTest();
    }

    public function mockLumberjackEventTracked(string $methodName, bool &$methodCalled, array $eventCode)
    {
        $calledWithCorrectEventCode = function (array $eventData,
                                                Merchant\Entity $merchant = null,
                                                \Throwable $ex = null,
                                                array $customProperties = []) use(&$methodCalled, $eventCode)
        {
            if($eventData === $eventCode)
            {
                $methodCalled = true;
            }
            // return true for the mock object to consider the input valid
            return true;
        };

        $lumberjackMock = $this->getMockBuilder(DiagClient::class)
                               ->setConstructorArgs([$this->app])
                               ->setMethods([$methodName])
                               ->getMock();

        $this->app->instance('diag', $lumberjackMock);

        $lumberjackMock->expects($this->atLeastOnce())
                       ->method($methodName)
                       ->will($this->returnCallback($calledWithCorrectEventCode));
    }

    public function mockSalesforceEventTracked(string $methodName)
    {
        $salesforceClientMock = $this->getMockBuilder(SalesForceClient::class)
                                     ->setConstructorArgs([$this->app])
                                     ->setMethods([$methodName])
                                     ->getMock();

        $this->app->instance('salesforce', $salesforceClientMock);

        if (in_array($methodName, ['captureInterestOfPrimaryMerchantInBanking', 'sendPreSignupDetails']))
        {
            $salesforceClientMock->expects($this->exactly(1))
                                 ->method($methodName)
                                 ->will($this->returnCallback(function(Merchant\Entity $merchant){
                                            $merchantOnboardingCategory = $merchant->getBankingOnboardingCategory();

                                            $this->assertNotNull($merchantOnboardingCategory);
                                        }));
        }
        else
        {
            $salesforceClientMock->expects($this->exactly(1))
                                 ->method($methodName);
        }
    }

    public function testMerchantAddingNewPreferences()
    {
        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testMerchantUpsertingPreferences()
    {
        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testMerchantPreferencesWithWrongGroup()
    {
        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testMerchantPreferencesWithWrongType()
    {
        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testMerchantPreferencesMissingType()
    {
        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testMerchantPreferencesMissingValue()
    {
        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testMerchantGetPreferencesByGroup()
    {
        $this->createMerchantAttribute('10000000000000', 'banking', 'x_merchant_preferences', 'business_category', 'School');
        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testMerchantGetPreferencesByGroupAndType()
    {
        $this->createMerchantAttribute('10000000000000', 'banking', 'x_merchant_preferences', 'business_category', 'School');
        $this->createMerchantAttribute('10000000000000', 'banking', 'x_merchant_preferences', 'monthly_payout_count', '1000');
        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);
        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function createMerchantAttribute(string $merchant_id, string $product, string $group, string $type, string $value)
    {
        $this->fixtures->create('merchant_attribute',
            [
                'merchant_id'   => $merchant_id,
                'product'       => $product,
                'group'         => $group,
                'type'          => $type,
                'value'         => $value,
                'updated_at'    => time(),
                'created_at'    => time()
            ]);
    }
}
