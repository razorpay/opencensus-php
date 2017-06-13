<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Exception\RuntimeException;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant;
use RZP\Models\Terminal\Category;
use RZP\Models\Terminal\Options;

class TerminalSelectionTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/TerminalSelectionTestData.php';

        parent::setUp();
    }

    public function testChooseGatewayWithSharedTerminals()
    {
        $this->fixtures->create('terminal:multiple_netbanking_terminals');

        // Create all shared terminals
        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment = $this->doAuthAndCapturePayment($payment);

        // ICIC should be served with billdesk under these conditions
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('billdesk', $payment['gateway']);
        $this->assertEquals('1000BdeskTrmnl', $payment['terminal_id']);
    }

    public function testChooseGatewayWithDirectTerminals()
    {
        $this->fixtures->create('terminal:multiple_netbanking_terminals');
        $this->fixtures->create('terminal:direct_billdesk_terminal');
        // Create all shared terminals
        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment = $this->doAuthAndCapturePayment($payment);

        // ICIC should be served with Direct billdesk Terminal
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('billdesk', $payment['gateway']);
        $this->assertEquals('10BillDirTrmnl', $payment['terminal_id']);
    }

    /**
     * Assign Direct Terminal To Another Merchant
     * Assign That Direct Terminal to Test Merchant also
     * Unassign that terminal from test merchant and test if payment fails
     */
    public function testMultipleMerchantForTerminal()
    {
        $this->ba->appAuth();

        $this->fixtures->create('terminal:multiple_netbanking_terminals');

        $this->fixtures->create('terminal:direct_terminal_for_non_test_merchant');

        $mid = Merchant\Account::TEST_ACCOUNT;

        $tid = '10BillDirTrmn2';

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment = $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('1000BdeskTrmnl', $payment['terminal_id']);

        $this->assignSubMerchant($tid, $mid);

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment = $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($tid, $payment['terminal_id']);

        $url = '/terminals/' . $tid . '/merchants/' . $mid;

        $request = [
            'url'    => $url,
            'method' => 'DELETE',
        ];

        $content = $this->makeRequestAndGetContent($request);

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment = $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('1000BdeskTrmnl', $payment['terminal_id']);
    }

    protected function assignSubMerchant(string $tid, string $mid)
    {
        $url = '/terminals/' . $tid . '/merchants/' . $mid;

        $request = [
            'url'    => $url,
            'method' => 'PUT',
        ];

        $content = $this->makeRequestAndGetContent($request);
    }

    public function testSubMerchantAssignWithMultipleAssignments()
    {
        $this->ba->appAuth();

        $this->fixtures->create('terminal:multiple_netbanking_terminals');

        $this->fixtures->create('terminal:direct_terminal_for_non_test_merchant');

        $mid = Merchant\Account::TEST_ACCOUNT;

        $tid = '10BillDirTrmn2';

        $this->assignSubMerchant($tid, $mid);

        $data = $this->testData['testSubMerchantAssignWithMultipleAssignments'];

        $this->runRequestResponseFlow($data, function() use ($tid, $mid)
        {
            $this->assignSubMerchant($tid, $mid);
        });
    }

    public function testMerchantAssign()
    {
        $this->ba->appAuth();

        $this->fixtures->create('terminal:multiple_netbanking_terminals');

        $this->fixtures->create('terminal:direct_terminal_for_non_test_merchant');

        $mid = Merchant\Account::TEST_ACCOUNT;

        $tid = '10BillDirTrmn2';

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment = $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('1000BdeskTrmnl', $payment['terminal_id']);

        $url = '/terminals/' . $tid . '/reassign';

        $requestContent = ['merchant_id' => $mid];

        $request = [
            'url'    => $url,
            'method' => 'PUT',
            'content' => $requestContent,
        ];

        $content = $this->makeRequestAndGetContent($request);

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment = $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($tid, $payment['terminal_id']);

        $url = '/terminals/' . $tid . '/reassign';

        $requestContent = ['merchant_id' => '1MercShareTerm'];

        $request = [
            'url'     => $url,
            'method'  => 'PUT',
            'content' => $requestContent,
        ];

        $content = $this->makeRequestAndGetContent($request);

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment = $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('1000BdeskTrmnl', $payment['terminal_id']);
    }

    public function testChooseTerminalWithCategory()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:multiple_category_terminals');

        $this->fixtures->merchant->setCategory(123);

        // Make Payment
        $payment = $this->getDefaultPaymentArray();
        $payment = $this->doAuthAndCapturePayment($payment);

        // Payment should have been made through shared terminl of correct category
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('SharedTrmnl123', $payment['terminal_id']);
    }

    public function testHDFCCardTerminalNotUsedForEmi()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_hdfc_terminal');
        $this->fixtures->create('terminal:shared_hdfc_emi_terminal');
        $emiPlan = $this->fixtures->create('emi_plan:default_emi_plans');

        $this->fixtures->merchant->enableEmi();
        $this->mockTokenex();

        $payment = $this->getDefaultPaymentArray();
        $payment['amount'] = 500000;
        $payment['method'] = 'emi';
        $payment['emi_duration'] = 9;
        $payment['card']['number'] = '41476700000006';

        $content = $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        // Payment should have been made through shared emi terminl
        $this->assertEquals('ShrdHdfcEmiTrm', $payment['terminal_id']);
    }

    public function testHDFCEmiTerminalNotUsedForCard()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_hdfc_terminal');
        $this->fixtures->create('terminal:shared_hdfc_emi_terminal');
        $emiPlan = $this->fixtures->create('emi_plan:default_emi_plans');

        $this->fixtures->merchant->enableEmi();
        $this->mockTokenex();

        $payment = $this->getDefaultPaymentArray();
        $content = $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        // Payment should have been made through shared terminl of correct category
        // $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('1000HdfcShared', $payment['terminal_id']);
    }

    public function testKotakEmisFlowThroughCardTerminal()
    {
        // Disable particular hdfc terminal, enable shared hdfc and shared hdfc emi terminal
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_amex_terminal');
        $this->fixtures->create('terminal:all_shared_terminals');

        // $this->fixtures->create('terminal:shared_hdfc_terminal');
        $this->fixtures->create('terminal:shared_hdfc_emi_terminal');

        // Enable default emi plans and mocktokenex
        $this->emiPlan = $this->fixtures->create('emi_plan:default_emi_plans');
        $this->fixtures->merchant->enableEmi();

        $this->mockTokenex();

        $emiPlan = $this->emiPlan;

        $this->payment = $this->getDefaultPaymentArray();
        $this->ba->publicAuth();
        $this->payment['amount'] = 500000;
        $this->payment['method'] = 'emi';
        $this->payment['emi_duration'] = 9;
        $this->payment['card']['number'] = '42809500000009';

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('1000HdfcShared', $payment['terminal_id']);
        $this->fixtures->merchant->disableEmi();
    }

    public function testTerminalChoiceonChance()
    {
        $this->fixtures->create('terminal:all_shared_terminals');
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->mockTokenex();

        $chances = [
            // Chance from 96 to 100 should give First Data
            //[ 'chanceValue' => 100,  'expected_terminal_id' => '1000FrstDataTl' ],
            //[ 'chanceValue' => 98,  'expected_terminal_id' => '1000FrstDataTl' ],
            //[ 'chanceValue' => 96,  'expected_terminal_id' => '1000FrstDataTl' ],
            // Chance from 5100 to 9500 should give AxisMigs
            ['chanceValue' => 9500, 'expected_terminal_id' => '1000AxisMigsTl'],
            ['chanceValue' => 7000, 'expected_terminal_id' => '1000AxisMigsTl'],
            ['chanceValue' => 5100, 'expected_terminal_id' => '1000AxisMigsTl'],
            // Chance from 4600 to 5000 should give Cybersource axis
            ['chanceValue' => 5000, 'expected_terminal_id' => '1000CybAxTrmnl'],
            ['chanceValue' => 4700, 'expected_terminal_id' => '1000CybAxTrmnl'],
            ['chanceValue' => 4600, 'expected_terminal_id' => '1000CybAxTrmnl'],
            // Chance 4500 or below should give HDFC
            ['chanceValue' => 4500, 'expected_terminal_id' => '1000HdfcShared'],
            ['chanceValue' => 2000, 'expected_terminal_id' => '1000HdfcShared'],
            ['chanceValue' => 0, 'expected_terminal_id' => '1000HdfcShared'],
        ];

        foreach ($chances as $chance)
        {
            $this->chanceTerminalTest($chance['chanceValue'], $chance['expected_terminal_id']);
        }
    }

    private function chanceTerminalTest($chance, $expectedTerminalId)
    {
        Options::setTestChance($chance);

        $this->payment = $this->getDefaultPaymentArray();

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($expectedTerminalId, $payment['terminal_id']);
    }

    public function testTerminalChoiceOnRiskyMerchant()
    {
        $this->markTestSkipped();

        $this->fixtures->merchant->enableRisky();
        $this->fixtures->create('terminal:all_shared_terminals');

        $payment = $this->getDefaultPaymentArray();
        $content = $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        // Payment should have been made through axis terminal
        $this->assertEquals('1000AxisMigsTl', $payment['terminal_id']);

        $this->fixtures->merchant->disableRisky();
    }

    public function testTerminalCategoryChoice()
    {
        $this->fixtures->merchant->editCategory2('govt_education');
        $this->fixtures->merchant->enableMethod('10000000000000', 'amex');
        $this->fixtures->create('terminal:all_shared_terminals');
        $this->fixtures->create('terminal:shared_amex_terminal');
        $this->fixtures->create('terminal:shared_amex_category_terminals');

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '341111111111111';
        $payment['card']['cvv'] = '8888';

        $content = $this->doAuthAndCapturePayment($payment);
        $payment = $this->getLastEntity('payment', true);

        // Payment should have been made through amex education services terminal
        $this->assertEquals('ShAmexEduTrmnl', $payment['terminal_id']);
    }

    public function testTerminalCategoryChoiceNoOverride()
    {
        $this->fixtures->merchant->editCategory2('corporate');
        $this->fixtures->merchant->enableMethod('10000000000000', 'amex');
        $this->fixtures->create('terminal:all_shared_terminals');
        $this->fixtures->create('terminal:shared_amex_terminal');
        $this->fixtures->create('terminal:shared_amex_category_terminals');

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '341111111111111';
        $payment['card']['cvv'] = '8888';

        $content = $this->doAuthAndCapturePayment($payment);
        $payment = $this->getLastEntity('payment', true);

        // Payment should have been made through amex education services terminal
        $this->assertEquals('ShRetailSvcsTl', $payment['terminal_id']);
    }

    public function testTerminalCategoryCorporate()
    {
        $this->fixtures->merchant->editCategory2('corporate');
        $this->fixtures->create('terminal:netbanking_kotak_terminal',
                                ['id' => 'DCrpNbKtkTrmnl', 'network_category' => 'corporate']);
        $this->fixtures->create('terminal:netbanking_kotak_terminal',
                                ['id' => 'DEduNbKtkTrmnl', 'network_category' => 'education']);
        $this->fixtures->create('terminal:netbanking_kotak_terminal',
                                ['id' => 'DrctNbKtkTrmnl']);
        $this->fixtures->create('terminal:shared_netbanking_kotak_terminal',
                                ['id' => 'SCorNbKtkTrmnl','network_category' => 'corporate']);
        $this->fixtures->create('terminal:shared_netbanking_kotak_terminal',
                                ['id' => 'SEduNbKtkTrmnl','network_category' => 'education']);
        $this->fixtures->create('terminal:shared_netbanking_kotak_terminal',
                                ['id' => 'SharNbKtkTrmnl']);

        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment['amount'] = '5000000';
        $payment['bank'] = 'KKBK';

        $content = $this->doAuthAndCapturePayment($payment);
        $payment = $this->getLastEntity('payment', true);

        // Payment should have been made through direct
        // corporate netbanking kotak terminal.
        $this->assertEquals('DCrpNbKtkTrmnl', $payment['terminal_id']);
    }

    public function testTerminalDefaultCategoryChoiceForAmex()
    {
        $this->fixtures->merchant->editCategory2('grocery');
        $this->fixtures->merchant->enableMethod('10000000000000', 'amex');
        $this->fixtures->create('terminal:all_shared_terminals');
        $this->fixtures->create('terminal:shared_amex_terminal');
        $this->fixtures->create('terminal:shared_amex_category_terminals');

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '341111111111111';
        $payment['card']['cvv'] = '8888';

        $content = $this->doAuthAndCapturePayment($payment);
        $payment = $this->getLastEntity('payment', true);

        // Payment should have been made through default amex category terminal
        // when no appropriate category terminal is available.
        $this->assertEquals('ShRetailSvcsTl', $payment['terminal_id']);
    }

    /**
     * Used to test if the terminals can be given categories
     * before adding categories for merchants.
     *
     * @param void
     * @return void
     * */
    public function testTerminalDefaultCategoryChoiceForAmexNonCategoryMerchant()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'amex');
        $this->fixtures->create('terminal:all_shared_terminals');
        $this->fixtures->create('terminal:shared_amex_category_terminals');

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '341111111111111';
        $payment['card']['cvv'] = '8888';

        $content = $this->doAuthAndCapturePayment($payment);
        $payment = $this->getLastEntity('payment', true);

        // Payment should have been made through default amex
        // category terminal when merchant has no category
        // assigned and shared terminal is not available
        $this->assertEquals('ShRetailSvcsTl', $payment['terminal_id']);
    }

    public function testTerminalChoiceForInternatioalCard()
    {
        $this->markTestSkipped();

        $this->fixtures->create('terminal:all_shared_terminals');

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '42451200000003';

        $content = $this->doAuthAndCapturePayment($payment);

        $card = $this->getLastEntity('card', true);
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('1000AxisMigsTl', $payment['terminal_id']);

        $this->fixtures->merchant->disableRisky();
    }

    public function testDisableDirectTerminalForCategory()
    {
        $this->fixtures->merchant->editCategory2('corporate');

        //Enables both terminals
        $this->fixtures->create('terminal:netbanking_kotak_terminal',
            ['id' => 'DCrpNbKtkTmnl1', 'network_category' => 'corporate']);

        $this->fixtures->create('terminal:netbanking_kotak_terminal',
            ['id' => 'DCrpNbKtkTmnl2', 'network_category' => 'corporate']);

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['amount'] = '500000';
        $payment['bank'] = 'KKBK';

        $this->doAuthAndCapturePayment($payment);

        $payment1 = $this->getLastEntity('payment', true);

        $this->assertEquals('DCrpNbKtkTmnl1', $payment1['terminal_id']);

        // Disables terminal 1
        $this->fixtures->terminal->edit('DCrpNbKtkTmnl1',['enabled' => false]);

        $this->doAuthAndCapturePayment($payment);

        $payment2 = $this->getLastEntity('payment', true);

        $this->assertEquals('DCrpNbKtkTmnl2', $payment2['terminal_id']);
    }

    public function testDisableSharedTerminal()
    {
        //Enables both terminals
        $this->fixtures->create('terminal:shared_netbanking_kotak_terminal',
             ['id' => 'SharNbKtkTmnl1']);

        $this->fixtures->create('terminal:shared_netbanking_kotak_terminal',
             ['id' => 'SharNbKtkTmnl2']);

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['bank'] = 'KKBK';

        $this->doAuthAndCapturePayment($payment);

        $payment1 = $this->getLastEntity('payment', true);

        $this->assertEquals('SharNbKtkTmnl1', $payment1['terminal_id']);

        // Disables terminal 1
        $this->fixtures->terminal->edit('SharNbKtkTmnl1',['enabled' => false]);

        $this->doAuthAndCapturePayment($payment);

        $payment2 = $this->getLastEntity('payment', true);

        $this->assertEquals('SharNbKtkTmnl2', $payment2['terminal_id']);
    }

    public function testAmountFilterForTerminals()
    {
        $this->fixtures->merchant->editCategory2('corporate');

        $this->fixtures->create('terminal:netbanking_kotak_terminal',
                                ['id' => 'DCrpNbKtkTrmnl', 'network_category' => 'corporate']);
        $this->fixtures->create('terminal:netbanking_kotak_terminal',
                                ['id' => 'DrctNbKtkTrmnl', 'network_category' => 'ecommerce']);

        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment['bank'] = 'KKBK';
        $payment['amount'] = 100000;
        $content = $this->doAuthAndCapturePayment($payment);
        $payment = $this->getLastEntity('payment', true);

        // ecomm KKBK terminal
        $this->assertEquals('DrctNbKtkTrmnl', $payment['terminal_id']);

        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment['bank'] = 'KKBK';
        $payment['amount'] = 300000;
        $content = $this->doAuthAndCapturePayment($payment);
        $payment = $this->getLastEntity('payment', true);

        // KKBK corporate category terminal
        $this->assertEquals('DCrpNbKtkTrmnl', $payment['terminal_id']);
    }

    protected function getPaymentForTPV($attributes = [])
    {
        $order = $this->fixtures->create('order:tpv_order', $attributes);

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['order_id'] = $order->getPublicId();

        $payment['amount'] = $order->getAmount();

        $payment['bank'] = $order->getBank();

        return $payment;
    }

    public function testSecuritiesMerchantTerminalSelection()
    {
        $this->fixtures->merchant->enableTPV();

        $this->fixtures->create('terminal:shared_billdesk_terminal',
            [
                'id'          => 'DrctNbBdkTmnl1',
                'merchant_id' => Merchant\Account::TEST_ACCOUNT,
                'tpv'         => 1,
                'shared'      => 0
            ]);

        $this->fixtures->create('terminal:shared_billdesk_terminal',
            [
                'id'               => 'DrctNbBdkTmnl2',
                'merchant_id'      => Merchant\Account::TEST_ACCOUNT,
                'network_category' => 'ecommerce',
                'tpv'              => 1,
                'shared'           => 0
            ]);

        $this->fixtures->create('terminal:shared_billdesk_terminal',
            [
                'id'               => 'DrctNbBdkTmnl3',
                'merchant_id'      => Merchant\Account::TEST_ACCOUNT,
                'network_category' => 'securities',
                'tpv'              => 1,
                'shared'           => 0
            ]);

        $this->fixtures->create('terminal:shared_billdesk_terminal',
            [
                'id'               => 'SharNbBdkTmnl1',
                'merchant_id'      => Merchant\Account::SHARED_ACCOUNT,
                'tpv'              => 1,
                'network_category' => 'securities'
            ]);

        $payment = $this->getPaymentForTPV(['bank' => 'ICIC']);

        $this->doAuthAndCapturePayment($payment);

        $payment1 = $this->getLastEntity('payment', true);

        $this->assertEquals('DrctNbBdkTmnl3', $payment1['terminal_id']);

        $this->verifyPayment($payment1['id']);

        $this->fixtures->terminal->edit('DrctNbBdkTmnl3',['enabled' => false]);

        $payment = $this->getPaymentForTPV(['bank' => 'ICIC']);

        $data = [];

        // TPV payment should not be routed through either ecommerce or null terminal
        $this->makeRequestAndCatchException(function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testSecuritiesMerchantOnKKBKTerminalSelection()
    {
        $this->fixtures->merchant->enableTPV();

        $this->fixtures->create('terminal:shared_netbanking_kotak_terminal',
             ['id' => 'DrctNbKtkTmnl1',
              'merchant_id' => Merchant\Account::TEST_ACCOUNT,
              'shared' => 0]);

        $this->fixtures->create('terminal:shared_netbanking_kotak_terminal',
             ['id' => 'DrctNbKtkTmnl2',
              'merchant_id' => Merchant\Account::TEST_ACCOUNT,
              'network_category' => 'ecommerce',
              'shared' => 0]);

        $this->fixtures->create('terminal:shared_netbanking_kotak_terminal',
             ['id' => 'DrctNbKtkTmnl3',
              'merchant_id' => Merchant\Account::TEST_ACCOUNT,
              'network_category' => 'securities',
              'shared' => 0]);

        $this->fixtures->create('terminal:shared_netbanking_kotak_terminal',
             ['id' => 'SharNbKtkTmnl1',
              'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
              'network_category' => 'securities']);

        $payment = $this->getPaymentForTPV(['bank' => 'KKBK']);

        $this->doAuthAndCapturePayment($payment);

        $payment1 = $this->getLastEntity('payment', true);

        // Payment should go through drct tpv terminals
        $this->assertEquals('DrctNbKtkTmnl3', $payment1['terminal_id']);
    }

    public function testCorporateMerchantsBilldeskICICI()
    {
        $this->fixtures->merchant->editCategory2('corporate');

        $this->fixtures->create('terminal:shared_billdesk_terminal',
             ['id' => 'SharNbBdkTmnl1',
              'merchant_id' => Merchant\Account::SHARED_ACCOUNT]);

        $this->fixtures->create('terminal:shared_billdesk_terminal',
             ['id' => 'SharNbBdkTmnl2',
              'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
              'network_category' => 'ecommerce']);

        $this->fixtures->create('terminal:shared_billdesk_terminal',
             ['id' => 'SharNbBdkTmnl3',
              'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
              'network_category' => 'corporate']);

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['bank'] = 'ICIC';

        $this->doAuthAndCapturePayment($payment);

        $payment1 = $this->getLastEntity('payment', true);

        // Ideally the terminal picked should have been the corporate terminal
        // But because of the newly added icici billdesk filter,
        // the ecommerce one should get picked.
        $this->assertEquals('SharNbBdkTmnl2', $payment1['terminal_id']);
    }

    public function testPharmaMerchantTerminalSelection()
    {
        $this->fixtures->merchant->editCategory2(Category::PHARMA);
        $this->mockTokenex();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal');
        $this->fixtures->create('terminal:shared_cybersource_axis_terminal');

        $payment = $this->getDefaultPaymentArray();
        $this->doAuthAndCapturePayment($payment);

        $payment1 = $this->getLastEntity('payment', true);
        $this->assertEquals('1000CybAxTrmnl', $payment1['terminal_id']);

        $terminalAttrs = [
            'id' => 'DrctHDFCTermnl',
            'merchant_id' => '10000000000000',
            'shared' => 0,
        ];
        $this->fixtures->create('terminal:shared_hdfc_terminal', $terminalAttrs);

        $payment = $this->getDefaultPaymentArray();
        $this->doAuthAndCapturePayment($payment);

        $payment1 = $this->getLastEntity('payment', true);
        $this->assertEquals('DrctHDFCTermnl', $payment1['terminal_id']);
    }
}
