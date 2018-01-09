<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Models\Card;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Error\PublicErrorCode;
use RZP\Models\Terminal\Options;
use RZP\Models\Terminal\Category;
use RZP\Models\Terminal\Selector;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\RuntimeException;
use RZP\Models\Merchant\Preferences;
use RZP\Error\PublicErrorDescription;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

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

        $this->fixtures->merchant->setCategory(124);

        // Make Payment
        $payment = $this->getDefaultPaymentArray();
        $payment = $this->doAuthAndCapturePayment($payment);

        // Payment should have been made through shared terminl of correct category
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('SharedTrmnl124', $payment['terminal_id']);
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

    public function testHDFCTerminalNotUsedForIin()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_hdfc_terminal');
        $this->fixtures->create('terminal:shared_axis_terminal');

        $this->fixtures->create('gateway_rule', [
            'method'        => 'card',
            'merchant_id'   => '100000Razorpay',
            'gateway'       => 'axis_migs',
            'type'          => 'filter',
            'filter_type'   => 'select',
            'group'         => 'prepaid_iin_filter',
            'iins'          => ['457392'],
        ]);

        $this->mockTokenex();

        $payment = $this->getDefaultPaymentArray();
        $payment['amount'] = 500000;
        $payment['method'] = 'card';
        $payment['card']['number'] = '4573920000000008';

        $content = $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        // Payment should have been made through shared emi terminl
        $this->assertEquals('1000AxisMigsTl', $payment['terminal_id']);
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

    public function testHDFCEmiTerminalWithMerchantSubvention()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_hdfc_terminal');
        $this->fixtures->create('terminal:shared_hdfc_emi_terminal');
        $this->fixtures->create('terminal:shared_hdfc_emi_merchant_subvention_terminal');
        $emiPlan = $this->fixtures->create('emi_plan:default_emi_plans');

        $this->fixtures->merchant->enableEmi();
        $this->fixtures->merchant->addFeatures('emi_merchant_subvention');
        $this->mockTokenex();

        $payment = $this->getDefaultPaymentArray();

        $payment['method'] = 'emi';

        $payment['emi_duration'] = 9;

        $payment['amount'] = 500000;

        $content = $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        // Payment should have been made through shared terminl of correct category
        // $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('ShrdEmiMrSubTr', $payment['terminal_id']);
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

        $this->fixtures->create('gateway_rule', [
            'method'           => 'card',
            'merchant_id'      => '100000Razorpay',
            'category2'        => 'govt_education',
            'gateway'          => 'amex',
            'network'          => 'AMEX',
            'network_category' => 'education',
            'type'             => 'filter',
            'filter_type'      => 'select',
            'group'            => 'category_filter',
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '341111111111111';
        $payment['card']['cvv'] = '8888';

        $this->fixtures->create(
            'iin',
            [
                'iin' => 341111,
                'network' => 'Amex',
                'type' => 'credit',
                'country' => null,
            ]);

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

        $this->fixtures->create('gateway_rule', [
            'method'           => 'card',
            'merchant_id'      => '100000Razorpay',
            'gateway'          => 'amex',
            'network'          => 'AMEX',
            'network_category' => 'retail_services',
            'type'             => 'filter',
            'filter_type'      => 'select',
            'group'            => 'category_filter',
        ]);

        $this->fixtures->create('gateway_rule', [
            'method'           => 'card',
            'merchant_id'      => '100000Razorpay',
            'gateway'          => 'amex',
            'network'          => 'AMEX',
            'type'             => 'filter',
            'filter_type'      => 'select',
            'group'            => 'category_filter',
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '341111111111111';
        $payment['card']['cvv'] = '8888';

        $this->fixtures->create(
            'iin',
            [
                'iin' => 341111,
                'network' => 'Amex',
                'type' => 'credit',
                'country' => null,
            ]);

        $content = $this->doAuthAndCapturePayment($payment);
        $payment = $this->getLastEntity('payment', true);

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

        $this->fixtures->create(
            'iin',
            [
                'iin' => 341111,
                'network' => 'Amex',
                'type' => 'credit',
                'country' => null,
            ]);

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

        $this->fixtures->create(
            'iin',
            [
                'iin' => 341111,
                'network' => 'Amex',
                'type' => 'credit',
                'country' => null,
            ]);

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

        // Rule to select netbanking_kotak terminals for kotak bank payments
        $this->fixtures->create('gateway_rule', [
            'method'      => 'netbanking',
            'merchant_id' => '100000Razorpay',
            'gateway'     => 'netbanking_kotak',
            'issuer'      => 'KKBK',
            'type'        => 'filter',
            'filter_type' => 'select',
            'group'       => 'method_filter',
        ]);

        // Rule to reject corporate network_category terminals for KOTAK
        // for amount les than 2000 INR
        $this->fixtures->create('gateway_rule', [
            'method'           => 'netbanking',
            'merchant_id'      => '100000Razorpay',
            'gateway'          => 'netbanking_kotak',
            'issuer'           => 'KKBK',
            'type'             => 'filter',
            'filter_type'      => 'reject',
            'min_amount'       => 0,
            'max_amount'       => 200000,
            'network_category' => 'corporate',
            'group'            => 'min_amount_filter',
        ]);

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
                'tpv'         => 0,
                'shared'      => 0
            ]);

        $this->fixtures->create('terminal:shared_billdesk_terminal',
            [
                'id'               => 'DrctNbBdkTmnl2',
                'merchant_id'      => Merchant\Account::TEST_ACCOUNT,
                'network_category' => 'ecommerce',
                'tpv'              => 0,
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

        $this->fixtures->create('gateway_rule', [
            'method'           => 'netbanking',
            'merchant_id'      => '100000Razorpay',
            'gateway'          => 'billdesk',
            'type'             => 'filter',
            'filter_type'      => 'select',
            'category2'        => 'securities',
            'network_category' => 'securities',
            'group'            => 'tpv_filter',
        ]);

        $payment = $this->getPaymentForTPV(['bank' => 'ICIC']);

        $this->doAuthAndCapturePayment($payment);

        $payment1 = $this->getLastEntity('payment', true);

        $this->assertEquals('DrctNbBdkTmnl3', $payment1['terminal_id']);

        $this->verifyPayment($payment1['id']);

        $this->fixtures->terminal->edit('DrctNbBdkTmnl3',['enabled' => false]);

        $payment = $this->getPaymentForTPV(['bank' => 'ICIC']);

        $this->doAuthPayment($payment);

        $payment2 = $this->getLastPayment(true);

        $this->assertEquals('SharNbBdkTmnl1', $payment2['terminal_id']);

        $this->fixtures->terminal->edit('SharNbBdkTmnl1',['enabled' => false]);

        $payment = $this->getPaymentForTPV(['bank' => 'ICIC']);

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->doAuthPayment($payment);
            },
            RuntimeException::class);
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
              'shared' => 0,
              'tpv'    => 1]);

        $this->fixtures->create('terminal:shared_netbanking_kotak_terminal',
             ['id' => 'SharNbKtkTmnl1',
              'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
              'network_category' => 'securities',
              'tpv'    => 1]);

        $this->fixtures->create('gateway_rule', [
            'method'           => 'netbanking',
            'merchant_id'      => '100000Razorpay',
            'gateway'          => 'netbanking_kotak',
            'issuer'           => 'KKBK',
            'type'             => 'filter',
            'filter_type'      => 'select',
            'category2'        => 'securities',
            'network_category' => 'securities',
            'group'            => 'tpv_filter',
        ]);

        $payment = $this->getPaymentForTPV(['bank' => 'KKBK']);

        $this->doAuthAndCapturePayment($payment);

        $payment1 = $this->getLastEntity('payment', true);

        // Payment should go through drct tpv terminals
        $this->assertEquals('DrctNbKtkTmnl3', $payment1['terminal_id']);
    }

    public function testCorporateMerchantsSharedBilldeskICICI()
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

        $this->fixtures->create('gateway_rule', [
            'method'           => 'netbanking',
            'merchant_id'      => '100000Razorpay',
            'gateway'          => 'billdesk',
            'issuer'           => 'ICIC',
            'type'             => 'filter',
            'filter_type'      => 'reject',
            'network_category' => 'corporate',
            'shared_terminal'  => 1,
            'group'            => 'billdesk_category_filter',
        ]);

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['bank'] = 'ICIC';

        $this->doAuthAndCapturePayment($payment);

        $payment1 = $this->getLastEntity('payment', true);

        // Ideally the terminal picked should have been the corporate terminal
        // But because of the newly added icici billdesk filter,
        // the ecommerce one should get picked.
        $this->assertEquals('SharNbBdkTmnl2', $payment1['terminal_id']);
    }

    public function testCorporateMerchantsBilldeskCorporateICICISelection()
    {
        // Corporate Enabled Icici terminal for Billdesk
        $this->fixtures->create('terminal:shared_billdesk_terminal',
             [
                'id'          => 'DrctNbBdkTmnl1',
                'merchant_id' => Merchant\Account::TEST_ACCOUNT,
                'corporate'   => 1,
             ]
            );

        $this->fixtures->create('terminal:shared_billdesk_terminal',
             [
              'id' => 'SharNbBdkTmnl1',
              'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
             ]
            );

        $data = [
            'response'  => [
                'content'     => [
                    'error' => [
                        'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_FAILED,
                    ],
                ],
                'status_code' => 400,
            ],
            'exception' => [
                'class'                 => \RZP\Exception\GatewayErrorException::class,
                'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
            ]
        ];

        $payment = $this->getDefaultNetbankingPaymentArray('ICIC');

        $this->doAuthPayment($payment);

        $payment1 = $this->getLastEntity('payment', true);
        $billdesk = $this->getLastEntity('billdesk', true);

        $this->assertEquals('SharNbBdkTmnl1', $payment1['terminal_id']);
        $this->assertEquals('ICI', $billdesk['BankID']);

        $this->fixtures->merchant->editCategory2('corporate');
        $this->fixtures->merchant->addFeatures('corporate_banks');

        $payment = $this->getDefaultNetbankingPaymentArray('ICIC_C');

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
           $this->doAuthPayment($payment);
        });

        $payment1 = $this->getLastEntity('payment', true);
        $billdesk = $this->getLastEntity('billdesk', true);

        $this->assertEquals('DrctNbBdkTmnl1', $payment1['terminal_id']);
        $this->assertEquals('ICO', $billdesk['BankID']);

    }

    public function testPharmaMerchantTerminalSelection()
    {
        $this->fixtures->merchant->editCategory2(Category::PHARMA);
        $this->mockTokenex();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->create('terminal:shared_axis_terminal', [
            'id'               => '1000AxisHdfcTl',
            'gateway_acquirer' => 'hdfc'
        ]);

        $this->fixtures->create('terminal:shared_axis_terminal');

        $payment = $this->getDefaultPaymentArray();
        $this->doAuthAndCapturePayment($payment);

        $payment1 = $this->getLastEntity('payment', true);
        $this->assertEquals('1000AxisMigsTl', $payment1['terminal_id']);

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

    public function testMutualFundsMerchantTerminalSelection()
    {
        $this->fixtures->merchant->editCategory2(Category::MUTUAL_FUNDS);

        $this->fixtures->create('terminal:shared_billdesk_terminal',
             ['id' => 'DrctNbBdkTmnl1',
              'merchant_id' => Merchant\Account::TEST_ACCOUNT]);

        $this->fixtures->create('terminal:shared_billdesk_terminal',
             ['id' => 'DrctNbBdkTmnl2',
              'merchant_id' => Merchant\Account::TEST_ACCOUNT,
              'network_category' => 'ecommerce']);

        $this->fixtures->create('terminal:shared_billdesk_terminal',
             ['id' => 'DrctNbBdkTmnl3',
              'merchant_id' => Merchant\Account::TEST_ACCOUNT,
              'network_category' => 'mutual_funds']);

        $this->fixtures->create('terminal:shared_netbanking_icici_terminal');

        $this->fixtures->create('gateway_rule', [
            'method'           => 'netbanking',
            'merchant_id'      => '100000Razorpay',
            'gateway'          => 'billdesk',
            'category2'        => 'mutual_funds',
            'issuer'           => 'ICIC',
            'type'             => 'filter',
            'filter_type'      => 'reject',
            'shared_terminal'  => '0',
            'group'            => 'billdesk_category_filter',
        ]);

        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment['bank'] = 'SBIN';
        $this->doAuthAndCapturePayment($payment);
        $payment1 = $this->getLastEntity('payment', true);
        $this->assertEquals('DrctNbBdkTmnl3', $payment1['terminal_id']);

        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment['bank'] = 'ICIC';
        $this->doAuthAndCapturePayment($payment);
        $payment1 = $this->getLastEntity('payment', true);
        $this->assertEquals('100NbIciciTmnl', $payment1['terminal_id']);
    }

    public function testHousingMerchantTerminalSelection()
    {
        $this->fixtures->merchant->editCategory2(Category::HOUSING);

        $this->fixtures->create('terminal:shared_billdesk_terminal',
             ['id' => 'DrctNbBdkTmnl1',
              'merchant_id' => Merchant\Account::TEST_ACCOUNT]);

        $this->fixtures->create('terminal:shared_billdesk_terminal',
             ['id' => 'DrctNbBdkTmnl2',
              'merchant_id' => Merchant\Account::TEST_ACCOUNT,
              'network_category' => 'ecommerce']);

        $this->fixtures->create('terminal:shared_billdesk_terminal',
             ['id' => 'DrctNbBdkTmnl3',
              'merchant_id' => Merchant\Account::TEST_ACCOUNT,
              'network_category' => 'mutual_funds']);

        $this->fixtures->create('terminal:shared_netbanking_icici_terminal');

        $this->fixtures->create('gateway_rule', [
            'method'           => 'netbanking',
            'merchant_id'      => '100000Razorpay',
            'gateway'          => 'billdesk',
            'category2'        => 'housing',
            'issuer'           => 'ICIC',
            'type'             => 'filter',
            'filter_type'      => 'reject',
            'group'            => 'billdesk_category_filter',
        ]);

        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment['bank'] = 'ICIC';
        $this->doAuthAndCapturePayment($payment);
        $payment1 = $this->getLastEntity('payment', true);
        $this->assertEquals('100NbIciciTmnl', $payment1['terminal_id']);
    }

    public function testInsuranceMerchantTerminalSelection()
    {
        $this->fixtures->merchant->editCategory2(Category::INSURANCE);

        $this->fixtures->create('terminal:shared_billdesk_terminal',
             ['id' => 'ShrdNbBdkTmnl1',
              'merchant_id' => Merchant\Account::SHARED_ACCOUNT]);

        $this->fixtures->create('terminal:shared_billdesk_terminal',
             ['id' => 'ShrdNbBdkTmnl2',
              'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
              'network_category' => 'ecommerce']);

        $this->fixtures->create('terminal:shared_billdesk_terminal',
             ['id' => 'ShrdNbBdkTmnl3',
              'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
              'network_category' => 'insurance']);

        $this->fixtures->create('gateway_rule', [
            'method'           => 'netbanking',
            'merchant_id'      => '100000Razorpay',
            'gateway'          => 'billdesk',
            'category2'        => 'insurance',
            'issuer'           => 'SBIN',
            'type'             => 'filter',
            'filter_type'      => 'reject',
            'network_category' => 'insurance',
            'shared_terminal'  => '1',
            'group'            => 'billdesk_category_filter',
        ]);

        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment['bank'] = 'SBIN';
        $this->doAuthAndCapturePayment($payment);
        $payment1 = $this->getLastEntity('payment', true);
        $this->assertEquals('ShrdNbBdkTmnl2', $payment1['terminal_id']);
    }

    public function testBilldeskHousingChoiceForForexMerchant()
    {
        $this->fixtures->merchant->editCategory2(Category::FOREX);

        $this->fixtures->create('terminal:shared_billdesk_terminal',
             ['id' => 'ShrdNbBdkNoCat',
              'merchant_id' => Merchant\Account::SHARED_ACCOUNT]);

        $this->fixtures->create('terminal:shared_billdesk_terminal',
             ['id' => 'ShrdNbBdkEComm',
              'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
              'network_category' => 'ecommerce']);

        $this->fixtures->create('terminal:shared_billdesk_terminal',
             ['id' => 'ShrdNbBdkHouse',
              'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
              'network_category' => 'housing']);

        // Should not be picked. Not even allowed with the new config.
        $this->fixtures->create('terminal:shared_billdesk_terminal',
             ['id' => 'ShrdNbBdkForex',
              'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
              'network_category' => 'forex']);

        $this->fixtures->create('gateway_rule', [
            'method'           => 'netbanking',
            'merchant_id'      => '100000Razorpay',
            'gateway'          => 'billdesk',
            'type'             => 'filter',
            'filter_type'      => 'reject',
            'min_amount'       => 0,
            'max_amount'       => 200000,
            'network_category' => 'forex',
            'group'            => 'min_amount_filter',
        ]);

        $this->fixtures->create('gateway_rule', [
            'method'           => 'netbanking',
            'merchant_id'      => '100000Razorpay',
            'gateway'          => 'billdesk',
            'type'             => 'filter',
            'filter_type'      => 'reject',
            'min_amount'       => 0,
            'max_amount'       => 150000,
            'network_category' => 'housing',
            'group'            => 'min_amount_filter',
        ]);

        $payment = $this->getDefaultNetbankingPaymentArray();

        // Amount filter should have rejected the housing terminal
        $payment['bank'] = 'SBIN';

        $this->doAuthAndCapturePayment($payment);
        $payment1 = $this->getLastEntity('payment', true);

        $this->assertEquals('ShrdNbBdkEComm', $payment1['terminal_id']);

        // Amount filter will let the payment though for amount greater than 2K
        $payment['amount'] = '300000';

        $this->doAuthAndCapturePayment($payment);
        $payment1 = $this->getLastEntity('payment', true);

        $this->assertEquals('ShrdNbBdkHouse', $payment1['terminal_id']);
    }

    public function testCorporateBankTerminalSelection()
    {
        $this->fixtures->create('terminal:billdesk_terminal', ['corporate' => 1]);
        $this->fixtures->create('terminal:shared_netbanking_icici_corp_terminal', ['merchant_id' => '10000000000000']);
        $this->fixtures->merchant->addFeatures('corporate_banks');

        $payment = $this->getDefaultNetbankingPaymentArray();

        // Amount filter should have rejected the housing terminal
        $payment['bank'] = 'ICIC_C';

        $this->doAuthAndCapturePayment($payment);
        $payment1 = $this->getLastEntity('payment', true);

        $this->assertEquals('100NbIcicCrpTl', $payment1['terminal_id']);
    }

    public function testMccFilterWithSharedCategoryTerminal()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_hdfc_terminal');
        $this->fixtures->create('terminal:multiple_category_terminals');

        $expectedTerminalIds = ['SharedTrmnl124'];

        $this->runTestCase($expectedTerminalIds);
    }

    public function testMccFilterWithSharedCategoryTerminalAndDirectTerminal()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->create('terminal:multiple_category_terminals');

        $this->fixtures->create('terminal:shared_hdfc_terminal', [
            'id'          => '1000HdfcDirect',
            'merchant_id' => '10000000000000'
        ]);

        $expectedTerminalIds = ['1000HdfcDirect', 'SharedTrmnl124'];

        $this->runTestCase($expectedTerminalIds);
    }

    protected function runTestCase(array $expectedTerminalIds)
    {
        $this->fixtures->merchant->setCategory(124);

        $cardArray = [
            'number'        => '4012001036275556',
            'expiry_month'  => '1',
            'expiry_year'   => '2035',
            'cvv'           => '123',
            'network'       => 'Visa',
            'issuer'        => 'HDFC',
            'name'          => 'Test',
            'international' => false,
        ];

        $card = (new Card\Entity)->fill($cardArray);

        $paymentArray = $this->getDefaultPaymentArray();
        unset($paymentArray['card']);
        $paymentArray['status'] = 'created';
        $paymentArray['method'] = 'card';

        $payment = (new Payment\Entity)->fill($paymentArray);
        $payment->card = $card;

        $merchant = Merchant\Entity::find('10000000000000');
        $payment->merchant()->associate($merchant);

        $input = [
            'payment' => $payment,
            'merchant' => $payment->merchant
        ];

        $options = new Options;
        $selector = new Selector($input, $options);
        $selectedTerminals = $selector->select();
        $selectedTerminalIds = array_pluck($selectedTerminals, 'id');

        $this->assertArraySelectiveEquals($expectedTerminalIds, $selectedTerminalIds);
    }

    public function testGatewayFilterRejectsCyberSource()
    {
        $attributes = [
            'id'              => '10000000001017',
            'org_id'          => Org::RZP_ORG,
            'activated'       => 1,
            'live'            => 1,
            'pricing_plan_id' => '1hDYlICobzOCYt',
        ];

        $this->fixtures->on(Mode::LIVE)->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->on(Mode::LIVE)->create('terminal:shared_cybersource_hdfc_terminal');

        $this->fixtures->on(Mode::LIVE)->create('merchant', $attributes);
        $this->fixtures->on(Mode::LIVE)->create('methods', [
            'merchant_id'    => '10000000001017',
            'disabled_banks' => [],
            'banks'          => '[]'
        ]);

        $key = $this->fixtures->on(Mode::LIVE)->create('key', [
            'id'          => '10000000rzpkey',
            'merchant_id' => '10000000001017',
        ]);

        $key = 'rzp_live_' . $key->getId();

        $data = $this->testData[__FUNCTION__];
        $this->runRequestResponseFlow($data, function () use ($key)
        {
            $payment = $this->getDefaultPaymentArray();
            $this->doAuthPayment($payment, null, $key);
        });
    }

    public function testGatewayFilterRejectsMigsForZomato()
    {
        $attributes = [
            'id'              => Preferences::MID_ZOMATO,
            'org_id'          => Org::RZP_ORG,
            'activated'       => 1,
            'live'            => 1,
            'pricing_plan_id' => '1hDYlICobzOCYt',
        ];

        $this->fixtures->on(Mode::LIVE)->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->on(Mode::LIVE)->create('terminal:shared_axis_terminal');

        $this->fixtures->on(Mode::LIVE)->create('merchant', $attributes);
        $this->fixtures->on(Mode::LIVE)->create('methods', [
            'merchant_id'    => Preferences::MID_ZOMATO,
            'disabled_banks' => [],
            'banks'          => '[]'
        ]);

        $key = $this->fixtures->on(Mode::LIVE)->create('key', [
            'id'          => '10000000rzpkey',
            'merchant_id' => Preferences::MID_ZOMATO,
        ]);

        $key = 'rzp_live_' . $key->getId();

        $data = $this->testData[__FUNCTION__];
        $this->runRequestResponseFlow($data, function () use ($key)
        {
            $payment = $this->getDefaultPaymentArray();
            $this->doAuthPayment($payment, null, $key);
        });
    }
}
