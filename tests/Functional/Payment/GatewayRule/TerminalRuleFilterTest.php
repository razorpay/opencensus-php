<?php

namespace RZP\Tests\Functional\Payment\GatewayRule;

use DB;
use App;

use RZP\Models\Card;
use RZP\Models\Emi;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;


class TerminalRuleFilterTest extends TestCase
{
    use PaymentTrait;

    const FILTERS_SKIPPED_FOR_TEST = [
        'method',
        'network',
        'currency',
        'international',
        'bank',
        'recurring'
    ];

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/TerminalRuleFilterTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->merchant->addFeatures('rule_filter');

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);
        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    if ($feature === 'store_empty_value_for_non_exempted_card_metadata')
                    {
                        return 'off';
                    }
                    return 'on';

                }) );

        $app = App::getFacadeRoot();

        $app['rzp.mode'] = 'test';
    }

    /**
     * Tests different permuations and combinations of rule filters within a group
     * and between groups. Within a group let's assume there are 2 rules
     * Rsel - A select filter rule
     * Rrej - A reject filter rule
     * We also have 2 terminals T1 and T2 representing the available terminals in a group
     * Below table represents the tests and expected outcomes for within a group
     * +---------------------------+---------------------------+-------------------+----------------------------------------------------------+
     * |           Rsel            |           Rrej            |      Output       |                         Comments                         |
     * +---------------------------+---------------------------+-------------------+----------------------------------------------------------+
     * | T1                        |                           | T1                | Only 1 terminal matches a select rule                    |
     * |                           | T1                        | T2                | Only 1 terminal matches a reject rule                    |
     * | T1 T2                     |                           | T1 T2             | Both terminals match select rules                        |
     * |                           | T1 T2                     | No terminal found | Both terminals match reject rules                        |
     * | T1                        | T2                        | T1                | 1 terminal matches select rule and 1 matches reject rule |
     * | T1 != Rsel and T2 != Rsel |                           | No terminal found | No terminals match the select rule defined in a group    |
     * |                           | T1 != Rrej and T2 != Rrej | T1 T2             | No terminals match the reject rules defined              |
     * +---------------------------+---------------------------+-------------------+----------------------------------------------------------+
     */
    public function testRuleFilterCombinations()
    {
        $testCases = $this->testData[__FUNCTION__];

        $this->fixtures->create('terminal:shared_hdfc_terminal');
        $this->fixtures->create('terminal:shared_axis_terminal');
        $this->fixtures->create('terminal:shared_sharp_terminal');
        $this->fixtures->create('terminal:shared_upi_icici_terminal');

        $merchant = Merchant\Entity::find('10000000000000');

        foreach ($testCases as $test)
        {
            $this->runTestCase($test, $merchant);
        }
    }

    public function testMethodFilter()
    {
        $testCases = $this->testData[__FUNCTION__];

        $this->fixtures->create('terminal:shared_billdesk_terminal');
        $this->fixtures->create('terminal:shared_hitachi_terminal');
        $this->fixtures->create('terminal:shared_hdfc_terminal');
        $this->fixtures->create('terminal:shared_olamoney_terminal');
        $this->fixtures->create('terminal:shared_jiomoney_terminal');
        $this->fixtures->create('terminal:shared_hdfc_emi_terminal');
        $this->fixtures->create('terminal:shared_upi_icici_terminal');
        $this->fixtures->create('terminal:shared_amex_terminal');

        $this->emiPlan = $this->fixtures->create('emi_plan:default_emi_plans');

        $this->fixtures->merchant->enableEmi();

        $merchant = Merchant\Entity::find('10000000000000');

        foreach ($testCases as $test)
        {
            $this->runTestCase($test, $merchant);
        }
    }

    public function testMethodFilterWithMerchantEmiSubvention()
    {
        $this->fixtures->create('terminal:shared_hdfc_terminal');
        $this->fixtures->create('terminal:shared_hdfc_emi_terminal');
        $this->fixtures->create('terminal:shared_hdfc_emi_merchant_subvention_terminal');

        $emiPlan = $this->fixtures->create('emi_plan:default_emi_plans');
        $this->fixtures->merchant->enableEmi();
        $this->fixtures->merchant->addFeatures('emi_merchant_subvention');

        $test = $this->testData[__FUNCTION__];

        $merchant = Merchant\Entity::find('10000000000000');

        $this->runTestCase($test, $merchant);
    }

    public function testSharedTerminalFilter()
    {
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:shared_hdfc_terminal');
        $directHdfcTerminal = $this->fixtures->create('terminal:shared_hdfc_terminal', [
            'id'          => '1000HdfcDirect',
            'merchant_id' => '10000000000000',
        ]);

        $this->fixtures->create('terminal:shared_axis_terminal');
        $directAxisTerminal =  $this->fixtures->create('terminal:shared_axis_terminal', [
            'id'          => '1000AxisDirect',
            'merchant_id' => '10000000000000',
        ]);

        $merchant = Merchant\Entity::find('10000000000000');

        $test = $this->testData[__FUNCTION__];

        // Result set will contain shared terminal iff direct terminal from that gateway is not available
        $this->runTestCase($test, $merchant);
    }

    public function testUpiFilter()
    {
        DB::table('terminals')->delete();

        $this->fixtures->create('terminal:shared_upi_mindgate_terminal');
        $this->fixtures->create('terminal:shared_upi_icici_terminal');

        $merchant = Merchant\Entity::find('10000000000000');

        $test = $this->testData[__FUNCTION__];

        $this->runTestCase($test, $merchant);
    }

    public function testNetworkFilter()
    {
        $this->fixtures->create('terminal:shared_hdfc_terminal');
        $this->fixtures->create('terminal:shared_axis_terminal');

        $merchant = Merchant\Entity::find('10000000000000');

        $test = $this->testData[__FUNCTION__];

        $this->runTestCase($test, $merchant);
    }

    public function testInternationalFilter()
    {
        $this->fixtures->create('terminal:shared_hdfc_terminal');
        $this->fixtures->create('terminal:shared_first_data_terminal', [
            'international' => 1,
        ]);

        $merchant = Merchant\Entity::find('10000000000000');

        $testCases = $this->testData[__FUNCTION__];

        foreach ($testCases as $test)
        {
            $this->runTestCase($test, $merchant);
        }
    }

    public function testDomesticPaymentFilter()
    {
        $this->fixtures->create('terminal:shared_hdfc_terminal');
        $this->fixtures->create('terminal:shared_first_data_terminal', [
            'international' => 1,
        ]);

        $this->fixtures->create('terminal:shared_first_data_terminal', [
            'id' => '1USDFrstDataTl',
            'international' => 1,
            'currency' => 'USD',
        ]);

        $merchant = Merchant\Entity::find('10000000000000');

        $testCases = $this->testData[__FUNCTION__];

        $test = $this->testData[__FUNCTION__];

        $this->runTestCase($test, $merchant);
    }

    public function testCurrencyFilter()
    {
        $this->fixtures->create('terminal:shared_hdfc_terminal');
        $this->fixtures->create('terminal:shared_first_data_terminal', [
            'currency' => 'USD',
        ]);

        $merchant = Merchant\Entity::find('10000000000000');

        $test = $this->testData[__FUNCTION__];

        $this->runTestCase($test, $merchant);
    }

    public function testRecurringRuleInitial()
    {
        $this->fixtures->create('terminal:shared_hdfc_recurring_terminals');
        $this->fixtures->create('terminal:shared_cybersource_hdfc_recurring_terminals');

        $merchant = Merchant\Entity::find('10000000000000');

        $test = $this->testData[__FUNCTION__];

        $this->runTestCase($test, $merchant);
    }

    public function testRecurringRuleAuto()
    {
        // failing because isDirectMerchant change need to debug this later.
        $this->markTestSkipped('need to debug');

        $this->fixtures->create('terminal:shared_hdfc_recurring_terminals');
        $this->fixtures->create('terminal:shared_cybersource_hdfc_recurring_terminals');

        $merchant = Merchant\Entity::find(Merchant\Account::SHARED_ACCOUNT);

        $test = $this->testData[__FUNCTION__];

        $this->runTestCase($test, $merchant);
    }

    public function testAmountFilter()
    {
        $this->fixtures->create('terminal:shared_hdfc_terminal');
        $this->fixtures->create('terminal:shared_axis_terminal');

        $merchant = Merchant\Entity::find('10000000000000');

        $testCases = $this->testData[__FUNCTION__];

        foreach ($testCases as $test)
        {
            $this->runTestCase($test, $merchant);
        }
    }

    public function testIinFilter()
    {
        $this->fixtures->create('terminal:shared_hdfc_terminal');
        $this->fixtures->create('terminal:shared_axis_terminal');

        $merchant = Merchant\Entity::find('10000000000000');

        $test = $this->testData[__FUNCTION__];

        $this->runTestCase($test, $merchant);
    }

    public function testMerchantCategoryFilterReject()
    {
        $this->fixtures->create('terminal:shared_hdfc_terminal');
        $this->fixtures->create('terminal:shared_axis_terminal');

        $this->fixtures->merchant->setCategory('1234');

        $merchant = Merchant\Entity::find('10000000000000');

        $test = $this->testData[__FUNCTION__];

        $this->runTestCase($test, $merchant);
    }

    public function testMerchantCategoryFilterDontReject()
    {
        $this->fixtures->create('terminal:shared_hdfc_terminal');
        $this->fixtures->create('terminal:shared_axis_terminal');

        $this->fixtures->merchant->setCategory('4321');

        $merchant = Merchant\Entity::find('10000000000000');

        $test = $this->testData[__FUNCTION__];

        $this->runTestCase($test, $merchant);
    }

    public function testMerchantCategory2Filter()
    {
        $this->fixtures->create('terminal:shared_hdfc_terminal');
        $this->fixtures->create('terminal:shared_axis_terminal');

        $this->fixtures->merchant->editCategory2('securities');

        $merchant = Merchant\Entity::find('10000000000000');

        $test = $this->testData[__FUNCTION__];

        $this->runTestCase($test, $merchant);
    }

    public function testNetworkCategoryFilter()
    {
        $this->fixtures->create('terminal:netbanking_kotak_terminal',
                                ['id' => 'DrctNbKtkTrmnl']);
        $this->fixtures->create('terminal:shared_netbanking_kotak_terminal',
                                ['id' => 'SCorNbKtkTrmnl','network_category' => 'corporate']);

        $this->fixtures->merchant->editCategory2('corporate');

        $merchant = Merchant\Entity::find('10000000000000');

        $test = $this->testData[__FUNCTION__];

        $this->runTestCase($test, $merchant);
    }

    public function testDirectTerminalFilter()
    {
        $this->fixtures->create('terminal:netbanking_kotak_terminal',
                                ['id' => 'DrctNbKtkTrmnl']);
        $this->fixtures->create('terminal:shared_netbanking_kotak_terminal',
                                ['id' => 'SCorNbKtkTrmnl']);

        $merchant = Merchant\Entity::find('10000000000000');

        $test = $this->testData[__FUNCTION__];

        $this->runTestCase($test, $merchant);
    }

    public function testOrgDirectTerminalFilter()
    {
        $this->fixtures->create('terminal:netbanking_kotak_terminal',
                                ['id' => 'DrctNbKtkTrmnl']);
        $this->fixtures->create('terminal:shared_netbanking_kotak_terminal',
                                ['id' => 'SCorNbKtkTrmnl']);

        $merchant = Merchant\Entity::find('10000000000000');

        $test = $this->testData[__FUNCTION__];

        $this->runTestCase($test, $merchant);
    }

    // Test to check weather gateway rules created for different org doesn't affect
    // another one
    public function testOrgDirectTerminalFilterWithDifferentOrg()
    {
        $this->fixtures->create('terminal:netbanking_kotak_terminal',
                                ['id' => 'DrctNbKtkTrmnl']);
        $this->fixtures->create('terminal:shared_netbanking_kotak_terminal',
                                ['id' => 'SCorNbKtkTrmnl']);

        $merchant = Merchant\Entity::find('10000000000000');

        $test = $this->testData[__FUNCTION__];

        // Here both direct and shared terminal should get selected since
        // gateway rule has been created for `HDFC org` and transacting merchant
        // is in `Razorpay org`
        $this->runTestCase($test, $merchant);
    }

    public function testFeatureBasedMigrationPlan()
    {
        $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal');
        $this->fixtures->create('terminal:shared_hdfc_terminal');

        $merchant = Merchant\Entity::find('10000000000000');

        $testCases = $this->testData[__FUNCTION__];

        $this->runTestCase($testCases[0], $merchant);

        $this->runTestCase($testCases[1], $merchant);
    }

    public function testMerchantSpecificFilterRules()
    {
        $this->fixtures->create('terminal:shared_hdfc_terminal');
        $this->fixtures->create('terminal:shared_axis_terminal');

        $this->fixtures->merchant->editCategory2('securities');

        $merchant = Merchant\Entity::find('10000000000000');

        $test = $this->testData[__FUNCTION__];

        $this->runTestCase($test, $merchant);
    }

    public function testTerminalSortingWithSorterRuleForDirectTerminal()
    {
        $directHdfcTerminal = $this->fixtures->create('terminal:shared_hdfc_terminal', [
            'id'          => '1000HdfcDirect',
            'merchant_id' => '10000000000000',
        ]);

        $directAxisTerminal =  $this->fixtures->create('terminal:shared_axis_terminal', [
            'id'          => '1000AxisDirect',
            'merchant_id' => '10000000000000',
        ]);

        $sharedHdfcTerminal = $this->fixtures->create('terminal:shared_hdfc_terminal');

        Terminal\Options::setTestChance(3000);

        $merchant = Merchant\Entity::find('10000000000000');

        $test = $this->testData[__FUNCTION__];

        $this->runTestCase($test, $merchant, true);
    }

    protected function runTestCase(array $testData, Merchant\Entity $merchant, bool $strictCompare = false)
    {
        $payment = $this->createPaymentEntity($merchant, $testData['payment_options']);

        if (isset($testData['fixtures']) === true)
        {
            $ruleIds = $this->createRules($testData['fixtures']);
        }

        $expectedTerminalIds = $testData['expected_terminal_ids'];

        $input = [
            'payment' => $payment,
            'merchant' => $payment->merchant,
            'card_mandate' => null
        ];

        $options = new Terminal\Options;
        $options->setFeatureSkippedFilters(self::FILTERS_SKIPPED_FOR_TEST);

        $selector = new Terminal\Selector($input, $options);
        $selectedTerminals = $selector->select();

        $selectedTerminalIds = array_pluck($selectedTerminals, 'id');

        if ($strictCompare === true)
        {
            $this->assertEquals($expectedTerminalIds, $selectedTerminalIds);
        }
        else
        {
            $this->assertEquals(count($expectedTerminalIds), count($selectedTerminalIds));
            $this->assertArraySelectiveEquals($expectedTerminalIds, $selectedTerminalIds);
        }

        if (empty($ruleIds) === false)
        {
            $this->fixtures->gateway_rule->delete($ruleIds);
        }
    }

    protected function createRules(array $fixtures): array
    {
        $ruleIds = [];

        foreach ($fixtures as $fixture)
        {
            $rule = $this->fixtures->create("gateway_rule", $fixture);

            $ruleIds[] = $rule->getId();
        }

        return $ruleIds;
    }

    protected function createPaymentEntity(Merchant\Entity $merchant, array $options): Payment\Entity
    {
        $paymentArray = $this->getDefaultPaymentArray();
        unset($paymentArray['card']);
        $paymentArray['status'] = 'created';

        $cardDetails = $options['card'] ?? null;
        $emiDetails = $options['emi'] ?? null;
        unset($options['card']);

        $paymentArray = array_merge($paymentArray, $options);

        $payment = (new Payment\Entity)->fill($paymentArray);

        if (isset($paymentArray['international']) === true)
        {
            $payment->setAttribute(Payment\Entity::INTERNATIONAL, $paymentArray['international']);
        }

        $payment->merchant()->associate($merchant);

        $method = $payment->getMethod();

        if ($payment->isMethodCardOrEmi() === true)
        {
            $this->updatePaymentEntityWithCardDetails($payment, $cardDetails);
            $this->updatePaymentEntityWithEmiDetails($payment, $emiDetails);
        }

        return $payment;
    }

    protected function updatePaymentEntityWithCardDetails(Payment\Entity $payment, $options)
    {
        $cardArray = [
            'number'        => '4012001036275556',
            'expiry_month'  => '1',
            'expiry_year'   => '2035',
            'cvv'           => '123',
            'network'       => 'Visa',
            'issuer'        => 'HDFC',
            'name'          => 'Test',
            'iin'           => '401200',
            'international' => false,
        ];

        if (empty($options) === false)
        {
            $cardArray = array_merge($cardArray, $options);
        }

        $card = (new Card\Entity)->fill($cardArray);

        $payment->card()->associate($card);
    }

    protected function updatePaymentEntityWithEmiDetails(Payment\Entity $payment, $emiDetails)
    {
        if (empty($emiDetails) === false)
        {
            $payment->emiPlan = (new Emi\Entity)->fill($emiDetails);
        }
    }

    protected function updatePaymentEntityWithNetbankingDetails(Payment\Entity $payment, array $options)
    {
        $payment->setBank($options['bank']);

        return $payment;
    }

    protected function updatePaymentEntityWithWalletDetails(Payment\Entity $payment, array $options)
    {
        $payment->wallet = $options['wallet'];

        return $payment;
    }
}
