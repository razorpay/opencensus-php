<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Models\Feature;
use RZP\Models\Terminal;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class MethodsTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/MethodsTestData.php';

        parent::setUp();

        $this->ba->appAuth();
    }

    public function testGetPaymentMethodsRoute()
    {
        $this->ba->publicLiveAuth();

        $this->fixtures->merchant->activate('10000000000000');

        $attributes = array(
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'billdesk',
            'card'                      => 0,
            'gateway_merchant_id'       => 'razorpay billdesk',
            'gateway_terminal_id'       => 'nodal account billdesk',
            'gateway_terminal_password' => 'razorpay_password',
        );

        $this->fixtures->on('live')->create('terminal', $attributes);

        $content = $this->startTest();

        $count = count($content['netbanking']);

        $this->assertEquals(59, $count);

        $this->assertArrayNotHasKey('recurring', $content);
    }

    public function testGetPaymentMethodsRouteWithNetbankingFalse()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->disableNetbanking('10000000000000');

        $content = $this->startTest();

        $count = count($content['netbanking']);
        $this->assertEquals(0, $count);
    }

    public function testNumOfBanksInTestMode()
    {
        $this->ba->publicTestAuth();

        $content = $this->getPaymentMethods();

        $count = count($content['netbanking']);

        $this->assertEquals(59, $count);
    }

    public function testBulkMethodUpdate()
    {
        $this->fixtures->merchant->disableAllMethods('10000000000000');

        $this->fixtures->merchant->enableMobikwik('10000000000000');

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1A0Fkd38fGZPVC']);

        $this->ba->appAuth();

        $this->startTest();

        $content = $this->getLastEntity('methods', true);

        $this->assertEquals($content['netbanking'], true);
        $this->assertEquals($content['mobikwik'], true);
    }

    public function testRecurringCardsOnChargeAtWill()
    {
        $this->ba->publicTestAuth();

        $this->fixtures->merchant->enableMobikwik('10000000000000');

        $this->fixtures->merchant->addFeatures([Feature\Constants::CHARGE_AT_WILL]);

        $testData = $this->testData['testRecurringCards'];

        $content = $this->startTest($testData);

        $this->assertArrayNotHasKey('netbanking', $content['recurring']);
    }

    public function testRecurringCardsOnSubscriptions()
    {
        $this->ba->publicTestAuth();

        $this->fixtures->merchant->enableMobikwik('10000000000000');

        $this->fixtures->merchant->addFeatures([Feature\Constants::SUBSCRIPTIONS]);

        $testData = $this->testData['testRecurringCards'];

        $content = $this->startTest($testData);

        $this->assertArrayNotHasKey('netbanking', $content['recurring']);
    }

    public function testRecurringNetbankingOnChargeAtWill()
    {
        $this->ba->publicTestAuth();

        $this->fixtures->merchant->enableMobikwik('10000000000000');

        $this->fixtures->merchant->addFeatures([Feature\Constants::CHARGE_AT_WILL, Feature\Constants::E_MANDATE]);

        $content = $this->startTest();

        $this->assertCount(0, $content['recurring']['netbanking']);

        $attributes = [
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'netbanking_icici',
            'card'                      => 0,
            'netbanking'                => 1,
            'gateway_merchant_id'       => 'razorpay billdesk',
            'gateway_terminal_id'       => 'nodal account billdesk',
            'gateway_terminal_password' => 'razorpay_password',
            'type'                      => [
                                                Terminal\Type::RECURRING_3DS => '1',
                                                Terminal\Type::RECURRING_NON_3DS => '1'
                                           ],
            'enabled'                   => 1,
            'deleted_at'                => null,
        ];

        $this->fixtures->create('terminal', $attributes);

        $content = $this->startTest();

        $this->assertArraySelectiveEquals(['ICIC' => 'ICICI Bank'], $content['recurring']['netbanking']);
    }

    public function testRecurringNetbankingOnSubscriptions()
    {
        $this->ba->publicTestAuth();

        $this->fixtures->merchant->enableMobikwik('10000000000000');

        $this->fixtures->merchant->addFeatures([Feature\Constants::SUBSCRIPTIONS, Feature\Constants::E_MANDATE]);

        $testData = $this->testData['testRecurringCards'];

        $content = $this->startTest($testData);

        // No netbanking for subscriptions
        $this->assertArrayNotHasKey('netbanking', $content['recurring']);
    }
}
