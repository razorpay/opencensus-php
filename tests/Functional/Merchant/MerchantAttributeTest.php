<?php

namespace Functional\Merchant;

use RZP\Diag\EventCode;
use RZP\Models\Merchant;
use RZP\Services\DiagClient;
use RZP\Services\RazorXClient;
use RZP\Services\SalesForceClient;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
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

    public function setUp()
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
//        $merchantDetail = $this->fixtures->create('merchant_detail');

//        $this->ba->proxyAuth('rzp_live_' . $merchantDetail['merchant_id']);
        $this->ba->adminAuth();

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $this->startTest();
    }

    public function mockRazorxTreatment(string $expVal)
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                                function ($mid, $feature, $mode) use ($expVal)
                                {
                                    if ($feature === RazorxTreatment::X_MERCHANT_SELF_SERVE_ONBOARDING)
                                    {
                                        return $expVal;
                                    }
                                    return 'control';
                                }));
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

        $salesforceClientMock->expects($this->exactly(1))
                             ->method($methodName);
    }

    public function testMerchantOnboardingCategoryAttributeCreatedOnSwitchProductOn(string $expVal = 'on', string $expectedValue = 'self_serve')
    {
        $this->mockRazorxTreatment($expVal);

        $methodCalled = false;

        $this->mockLumberjackEventTracked('trackOnboardingEvent', $methodCalled, EventCode::MERCHANT_ONBOARDING_CATEGORY_SET);

        $this->testSwitchProductScenario();

        $merchantAttribute = $this->getLastEntity('merchant_attribute', true);

        $this->assertEquals($merchantAttribute['value'], $expectedValue);

        $this->assertTrue($methodCalled);
    }

    public function testMerchantOnboardingCategoryAttributeCreatedOnSwitchProductOff()
    {
        $this->testMerchantOnboardingCategoryAttributeCreatedOnSwitchProductOn('off', 'normal');
    }

    public function testMerchantOnboardingCategoryAttributeCreatedOnSignupOn(string $expVal = 'on', string $expectedValue = 'self_serve')
    {
        $this->mockRazorxTreatment($expVal);

        $methodCalled = false;

        $this->mockLumberjackEventTracked('trackOnboardingEvent', $methodCalled, EventCode::MERCHANT_ONBOARDING_CATEGORY_SET);

        $this->testSignupScenario();

        $merchantAttribute = $this->getLastEntity('merchant_attribute', true);

        $this->assertEquals($merchantAttribute['value'], $expectedValue);

        $this->assertTrue($methodCalled);
    }

    public function testMerchantOnboardingCategoryAttributeCreatedOnSignupOff()
    {
        $this->testMerchantOnboardingCategoryAttributeCreatedOnSignupOn('off', 'normal');
    }

    public function testMerchantOnboardingCategoryCron(string $merchantId = '10000000000000',
                                                        string $startingValue = 'self_serve',
                                                        string $finalExpectedValue = 'normal',
                                                        int $doneAt = null)
    {
        if ($startingValue != $finalExpectedValue)
        {
            $methodCalled = false;

            $this->mockLumberjackEventTracked('trackOnboardingEvent', $methodCalled, EventCode::MERCHANT_ONBOARDING_CATEGORY_UPDATE);

            $this->mockSalesforceEventTracked('updateChangeInBankingMerchantOnboardingCategory');
        }

        $merchantAttribute = $this->fixtures->create('merchant_attribute',
            [
                'merchant_id' => $merchantId,
                'product' => 'banking',
                'group' => 'onboarding',
                'type' => 'merchant_onboarding_category',
                'value' => $startingValue,
                'updated_at' => ($doneAt === null)? time():$doneAt,
                'created_at' => ($doneAt === null)? time():$doneAt
            ]);

        $this->ba->cronAuth();

        $this->startTest();

        $newmerchantAttribute = $this->getDbEntityById('merchant_attribute', $merchantAttribute['id']);

        $this->assertEquals($newmerchantAttribute['value'], $finalExpectedValue);
    }

    public function testMerchantOnboardingCategoryCronOnboarded()
    {
        $this->createPayout([
            'processed_at' => time(),
            'status'       => 'processed'
        ]);

        $payout = $this->getLastEntity('payout', true);

        $merchantId = $payout['merchant_id'];

        $this->testMerchantOnboardingCategoryCron($merchantId, 'self_serve', 'self_serve');
    }

    public function testMerchantOnboardingCategoryCronNotOnboardedPayoutNotProcessed()
    {
        $this->createPayout();

        $payout = $this->getLastEntity('payout', true);

        $merchantId = $payout['merchant_id'];

        $this->testMerchantOnboardingCategoryCron($merchantId, 'self_serve', 'normal');
    }

    public function testMerchantOnboardingCategoryCronNotOnboardedDaysNotMatch()
    {
        $time5DaysAgo = strtotime(strval(-5).' days', time());

        $this->testMerchantOnboardingCategoryCron('10000000000000', 'self_serve', 'self_serve', $time5DaysAgo);
    }
}
