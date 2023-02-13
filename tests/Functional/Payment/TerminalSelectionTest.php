<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Constants\Environment;
use RZP\Exception\BadRequestException;
use RZP\Gateway\Hdfc\ErrorCodes\ErrorCodeDescriptions;
use RZP\Gateway\Mpi\Blade\Mock\CardNumber;
use RZP\Models\Card;
use RZP\Models\WalletAccount;
use RZP\Constants\Mode;
use RZP\Models\Gateway\Terminal\GatewayProcessor\Hitachi\GatewayProcessor;
use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Services\RazorXClient;
use RZP\Error\PublicErrorCode;
use RZP\Gateway\Hitachi\Gateway;
use RZP\Models\Terminal\Options;
use RZP\Models\Terminal\Category;
use RZP\Models\Terminal\Selector;
use Illuminate\Database\Eloquent\Factory;
use RZP\Exception\BadRequestValidationFailureException;

use RZP\Tests\Functional\TestCase;
use RZP\Exception\RuntimeException;
use RZP\Models\Merchant\Preferences;
use RZP\Error\PublicErrorDescription;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Helpers\TerminalTrait;
use RZP\Gateway\Hitachi\Gateway as HitachiGateway;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class TerminalSelectionTest extends TestCase
{
    use PaymentTrait;
    use TerminalTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/TerminalSelectionTestData.php';

        parent::setUp();

        $this->terminalsServiceMock = $this->getTerminalsServiceMock();
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

    public function testChooseGatewayWithDirectSettlementTerminals()
    {
        $this->fixtures->create('terminal:direct_settlement_hdfc_terminal');
        $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal');

        $payment = $this->getDefaultNetbankingPaymentArray("HDFC");
        $payment = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('netbanking_hdfc', $payment['gateway']);
        $this->assertEquals('10DirectseTmnl', $payment['terminal_id']);
    }

    /**
     * Assign Direct Terminal To Another Merchant
     * Assign That Direct Terminal to Test Merchant also
     * Unassign that terminal from test merchant and test if payment fails
     */
    public function testMultipleMerchantForTerminal()
    {
        $this->ba->adminAuth();

        $this->fixtures->create('terminal:multiple_netbanking_terminals');

        $this->fixtures->create('terminal:direct_terminal_for_non_test_merchant');

        $mid = Merchant\Account::TEST_ACCOUNT;

        $tid = '10BillDirTrmn2';

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment = $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('1000BdeskTrmnl', $payment['terminal_id']);

        $this->ba->getAdmin()->merchants()->attach($mid);

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

    public function testSubMerchantAssignWithMultipleAssignments()
    {
        $this->ba->adminAuth();

        $this->fixtures->create('terminal:multiple_netbanking_terminals');

        $this->fixtures->create('terminal:direct_terminal_for_non_test_merchant');

        $mid = Merchant\Account::TEST_ACCOUNT;

        $tid = '10BillDirTrmn2';

        $this->ba->getAdmin()->merchants()->attach($mid);

        $this->assignSubMerchant($tid, $mid);

        $data = $this->testData['testSubMerchantAssignWithMultipleAssignments'];

        $this->runRequestResponseFlow($data, function() use ($tid, $mid)
        {
            $this->assignSubMerchant($tid, $mid);
        });
    }

    public function testMerchantAssign()
    {
        $this->ba->adminAuth();

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
        $this->mockCardVault();

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
            'step'          => 'authorization',
        ]);

        $this->mockCardVault();

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
        $this->mockCardVault();

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
        $this->mockCardVault();

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

        $this->mockCardVault();

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
        $this->fixtures->merchant->enableCardNetworks('10000000000000', ['amex']);
        $this->fixtures->create('terminal:all_shared_terminals');
        $this->fixtures->create('terminal:shared_amex_terminal');
        $this->fixtures->create('terminal:shared_amex_category_terminals');

        $methods = $this->getLastEntity('methods', true);

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
            'step'             => 'authorization',
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
        $this->fixtures->merchant->enableCardNetworks('10000000000000', ['amex']);
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
            'step'             => 'authorization',
        ]);

        $this->fixtures->create('gateway_rule', [
            'method'           => 'card',
            'merchant_id'      => '100000Razorpay',
            'gateway'          => 'amex',
            'network'          => 'AMEX',
            'type'             => 'filter',
            'filter_type'      => 'select',
            'group'            => 'category_filter',
            'step'             => 'authorization',
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
        $this->fixtures->merchant->enableCardNetworks('10000000000000', ['amex']);
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
        $this->fixtures->merchant->enableCardNetworks('10000000000000', ['amex']);
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
            'step'        => 'authorization',
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
            'step'             => 'authorization',
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
        // Removed ICICI from billdesk tpv
        $this->markTestSkipped();

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
            'step'             => 'authorization',
        ]);

        $payment = $this->getPaymentForTPV(['bank' => 'ICIC']);

        $this->doAuthAndCapturePayment($payment);

        $payment1 = $this->getLastEntity('payment', true);

        $this->assertEquals('DrctNbBdkTmnl3', $payment1['terminal_id']);

        $this->ba->adminAuth();

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
            'step'             => 'authorization',
        ]);

        $payment = $this->getPaymentForTPV(['bank' => 'KKBK']);

        $this->doAuthAndCapturePayment($payment);

        $payment1 = $this->getLastEntity('payment', true);

        // Payment should go through drct tpv terminals
        $this->assertEquals('DrctNbKtkTmnl3', $payment1['terminal_id']);
    }

    public function testCorporateMerchantsSharedBilldeskICICI()
    {
        // Skipping this test as removing ICICI from billdesk.
        $this->markTestSkipped();

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
            'step'             => 'authorization',
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
        // Skipping test as removing icici from billdesk
        $this->markTestSkipped();

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
        $this->mockCardVault();

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

        $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal');

        $this->fixtures->create('gateway_rule', [
            'method'           => 'netbanking',
            'merchant_id'      => '100000Razorpay',
            'gateway'          => 'billdesk',
            'category2'        => 'mutual_funds',
            'issuer'           => 'HDFC',
            'type'             => 'filter',
            'filter_type'      => 'reject',
            'shared_terminal'  => '0',
            'group'            => 'billdesk_category_filter',
            'step'             => 'authorization',
        ]);

        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment['bank'] = 'SBIN';
        $this->doAuthAndCapturePayment($payment);
        $payment1 = $this->getLastEntity('payment', true);
        $this->assertEquals('DrctNbBdkTmnl3', $payment1['terminal_id']);

        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment['bank'] = 'HDFC';
        $this->doAuthAndCapturePayment($payment);
        $payment1 = $this->getLastEntity('payment', true);
        $this->assertEquals('100NbHdfcTrmnl', $payment1['terminal_id']);
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

        $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal');

        $this->fixtures->create('gateway_rule', [
            'method'           => 'netbanking',
            'merchant_id'      => '100000Razorpay',
            'gateway'          => 'billdesk',
            'category2'        => 'housing',
            'issuer'           => 'HDFC',
            'type'             => 'filter',
            'filter_type'      => 'reject',
            'group'            => 'billdesk_category_filter',
            'step'             => 'authorization',
        ]);

        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment['bank'] = 'HDFC';
        $this->doAuthAndCapturePayment($payment);
        $payment1 = $this->getLastEntity('payment', true);
        $this->assertEquals('100NbHdfcTrmnl', $payment1['terminal_id']);
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
            'step'             => 'authorization',
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
            'step'             => 'authorization',
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
            'step'             => 'authorization',
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
        $this->fixtures->create('terminal:shared_netbanking_hdfc_corp_terminal', ['merchant_id' => '10000000000000']);
        $this->fixtures->merchant->addFeatures('corporate_banks');

        $payment = $this->getDefaultNetbankingPaymentArray();

        // Amount filter should have rejected the housing terminal
        $payment['bank'] = 'HDFC_C';

        $this->doAuthAndCapturePayment($payment);
        $payment1 = $this->getLastEntity('payment', true);

        $this->assertEquals('100NbHdfcCrpTl', $payment1['terminal_id']);
    }

    public function testMccFilterWithSharedCategoryTerminal()
    {
        $this->app['rzp.mode'] = Mode::TEST;

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_hdfc_terminal');
        $this->fixtures->create('terminal:multiple_category_terminals');

        $expectedTerminalIds = ['SharedTrmnl124'];

        $this->runTestCase($expectedTerminalIds);
    }

    public function testMccFilterWithSharedCategoryTerminalAndDirectTerminal()
    {
        $this->app['rzp.mode'] = Mode::TEST;

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->create('terminal:multiple_category_terminals');

        $this->fixtures->create('terminal:shared_hdfc_terminal', [
            'id'          => '1000HdfcDirect',
            'merchant_id' => '10000000000000'
        ]);

        $expectedTerminalIds = ['1000HdfcDirect'];

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

        $payment->card()->associate($card);

        $merchant = Merchant\Entity::find('10000000000000');

        $payment->merchant()->associate($merchant);

        $input = [
            'payment' => $payment,
            'merchant' => $payment->merchant,
            'card_mandate' => null
        ];

        $options = new Options;
        $selector = new Selector($input, $options);
        $selectedTerminals = $selector->select();

        $selectedTerminalIds = array_pluck($selectedTerminals, 'id');
        $this->assertArraySelectiveEquals($expectedTerminalIds, $selectedTerminalIds);

        return $selectedTerminalIds;
    }

    public function testFulcrumTerminalCreationOnRun()
    {
        $this->enableRazorXTreatmentForFulcrumTerminal();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->merchant->setCategory('1240');

        $input = $this->getInputForFulcrumTerminalCreationOnRun();

        $this->app['rzp.mode'] = Mode::TEST;

        $options = new Options;
        $selector = new Selector($input, $options);

        $this->terminalsServiceMock = $this->getTerminalsServiceMock();

        $category = "1240";
        $this->mockTerminalsServiceConsecutiveSendRequest($this->getHitachiOnboardResponseAndCreate($category),
            $this->getFulcrumOnboardResponseAndCreate($category));

        $selectedTerminals = $selector->select();

        $this->assertEquals(2, sizeof($selectedTerminals));

        $selectedTerminalGateways = array_map(function($term) {
            return $term->getGateway();
        }, $selectedTerminals);

        $this->assertTrue(in_array("fulcrum", $selectedTerminalGateways));
    }

    public function testSkipFulcrumTerminalCreationOnRun() {

        $this->enableRazorXTreatmentForFulcrumTerminal();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->merchant->setCategory('1240');

        $this->fixtures->merchant->addFeatures(Feature\Constants::SKIP_HITACHI_AUTO_ONBOARD);
        $this->fixtures->merchant->addFeatures(Feature\Constants::SKIP_FULCRUM_AUTO_ONBOARD);

        $input = $this->getInputForFulcrumTerminalCreationOnRun();

        $this->app['rzp.mode'] = Mode::TEST;

        $options = new Options;
        $selector = new Selector($input, $options);
        $selectedTerminals = $selector->select();

        // There should be no seleted terminals, not even of 'fulcrum' gateway
        $this->assertEquals(1, sizeof($selectedTerminals));
        $this->assertNull($selectedTerminals[0]);
    }

    // should not create terminal if method is not card
    public function testFulcrumTerminalCreationOnRunForWallet()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    if ($feature === 'ROUTER_FULCRUM_ON_BOARDING')
                    {
                        return 'on';
                    }
                    return 'off';

                }) );

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->merchant->setCategory('1240');

        $merchantDetailArray = [
            'contact_name'                  => 'rzp',
            'contact_email'                 => 'test@rzp.com',
            'merchant_id'                   => '10000000000000',
            'business_operation_address'    => 'Koramangala',
            'business_operation_state'      => 'KARNATAKA',
            'business_operation_pin'        =>  560047,
            'business_dba'                  => 'test',
            'business_name'                 => 'rzp_test',
            'business_operation_city'       => 'Bangalore',
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailArray);

        $paymentArray = $this->getDefaultPaymentArray();
        unset($paymentArray['card']);
        $paymentArray['status'] = 'created';
        $paymentArray['method'] = 'wallet';
        $paymentArray['wallet'] = 'olamoney';


        $payment = (new Payment\Entity)->fill($paymentArray);

        $merchant = Merchant\Entity::find('10000000000000');

        $payment->merchant()->associate($merchant);
        $payment->setId("IUZpAQvrlzgaHU");

        $input = [
            'payment' => $payment,
            'merchant' => $payment->merchant
        ];

        $this->app['rzp.mode'] = Mode::TEST;

        $options = new Options;
        $selector = new Selector($input, $options);
        $selectedTerminals = $selector->select();

        $this->assertNotTrue(in_array("fulcrum", $selectedTerminals));
    }

    //should not create new terminal if already fulcrum terminal exists
    public function testDuplicateFulcrumTerminalCreationOnRun()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->enableRazorXTreatmentForFulcrumTerminal();

        $this->fixtures->create('terminal:direct_fulcrum_terminal');
        $this->fixtures->create('terminal:direct_hitachi_terminal');

        $this->fixtures->merchant->setCategory('1240');

        $input = $this->getInputForFulcrumTerminalCreationOnRun();

        $this->app['rzp.mode'] = Mode::TEST;

        $options = new Options;
        $selector = new Selector($input, $options);
        $selectedTerminals = $selector->select();

        $this->assertEquals(2, sizeof($selectedTerminals));

        $terminal = $selectedTerminals[0];

        $this->assertEquals('fulcrum', $terminal->getGateway());
        $this->assertEquals('fulcrumDirectMerchantId', $terminal->getGatewayMerchantId());
        $this->assertEquals('fulcrumDirectTerminalId', $terminal->getGatewayTerminalId());
    }

    public function testFulcrumTerminalIsDisabledOnMerchantMccEdit()
    {
        $this->enableRazorXTreatmentForFulcrumTerminal();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $merchantId = $this->setUpMerchantForHitachiTerminalDisableTest();

        $oldMccTerminal = $this->fixtures->create('terminal', $this->getTerminalCreateArrayForFulcrumTerminalDisableTest([
            'category'    => '1234',
            'merchant_id' => $merchantId,
        ]));

        $this->assertTrue($oldMccTerminal->isEnabled());

        $this->editMerchant($merchantId, ['category' => "4321"]);

        $oldMccTerminal = $this->getEntityById('terminal', $oldMccTerminal['id'], true);

        $this->assertFalse($oldMccTerminal['enabled']);

        $this->assertEquals(Terminal\Status::DEACTIVATED, $oldMccTerminal[Terminal\Entity::STATUS]);

        // start onboarding flow

        $input = $this->getInputForHitachiTerminalTestOnMccUpdate($merchantId);

        $this->app['rzp.mode'] = Mode::TEST;

        $options = new Options;
        $selector = new Selector($input, $options);
        $this->terminalsServiceMock = $this->getTerminalsServiceMock();
        $category = "4321";
        $this->mockTerminalsServiceConsecutiveSendRequest($this->getHitachiOnboardResponseAndCreate($category, $merchantId),
            $this->getFulcrumOnboardResponseAndCreate($category, $merchantId));

        $selectedTerminals = $selector->select();
        $this->assertEquals(2, sizeof($selectedTerminals));

        $selectedTerminalGateways = array_map(function($term) {
            return $term->getGateway();
        }, $selectedTerminals);

        $selectedTerminalCategory = array_map(function($term) {
            return $term->getCategory();
        }, $selectedTerminals);

        $this->assertTrue(in_array("fulcrum", $selectedTerminalGateways));
        $this->assertTrue(in_array("4321", $selectedTerminalCategory));
    }

    public function testHitachiTerminalCreationOnRun()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    if ($feature === 'TerminalsService_MigrateTerminal')
                    {
                        return 'migrate';
                    }
                    return 'control';

                }) );

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->merchant->setCategory('1240');

        $input = $this->getInputForHitachiTerminalCreationOnRun();

        $this->app['rzp.mode'] = Mode::TEST;

        $options = new Options;
        $selector = new Selector($input, $options);

        $this->terminalsServiceMock = $this->getTerminalsServiceMock();

        $category = "1240";
        $this->mockTerminalsServiceSendRequest(function () use($category){
            return $this->getHitachiOnboardResponseAndCreate($category);
        }, 1);

        $selectedTerminals = $selector->select();

        $this->assertEquals(1, sizeof($selectedTerminals));
        $terminal = $selectedTerminals[0];

        $this->assertEquals('hitachi', $terminal->getGateway());
    }

    public function testSkipHitachiTerminalCreationOnRun()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->merchant->setCategory('1240');

        $this->fixtures->merchant->addFeatures(Feature\Constants::SKIP_HITACHI_AUTO_ONBOARD);

        $input = $this->getInputForHitachiTerminalCreationOnRun();

        $this->app['rzp.mode'] = Mode::TEST;

        $options = new Options;
        $selector = new Selector($input, $options);
        $selectedTerminals = $selector->select();

        // There should be no seleted terminals, not even of 'hitachi' gateway
        $this->assertEquals(1, sizeof($selectedTerminals));
        $this->assertNull($selectedTerminals[0]);
    }

    public function testBlockedMccOnHitachiTerminalWithOverrideFeature()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::OVERRIDE_HITACHI_BLACKLIST]);

        $this->fixtures->merchant->setCategory(HitachiGateway::BLACKLISTED_MCC[0]);

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

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

        $merchantDetailArray = [
            'contact_name'               => 'rzp',
            'contact_email'              => 'test@rzp.com',
            'merchant_id'                => '10000000000000',
            'business_operation_address' => 'Koramangala',
            'business_operation_state'   => 'KARNATAKA',
            'business_operation_pin'     => 560047,
            'business_dba'               => 'test',
            'business_name'              => 'rzp_test',
            'business_operation_city'    => 'Bangalore',
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailArray);

        $paymentArray = $this->getDefaultPaymentArray();
        unset($paymentArray['card']);
        $paymentArray['status'] = 'created';
        $paymentArray['method'] = 'card';

        $payment       = (new Payment\Entity)->fill($paymentArray);

        $payment->card()->associate($card);

        $merchant = Merchant\Entity::find('10000000000000');

        $payment->merchant()->associate($merchant);

        $input = [
            'payment'  => $payment,
            'merchant' => $payment->merchant,
            'card_mandate' => null
        ];

        $this->app['rzp.mode'] = Mode::TEST;

        $options           = new Options;
        $selector          = new Selector($input, $options);

        $this->terminalsServiceMock = $this->getTerminalsServiceMock();
        $category = $merchant->getCategory();
        $this->mockTerminalsServiceSendRequest(function () use($category){
            return $this->getHitachiOnboardResponseAndCreate($category);
        }, 1);

        $selectedTerminals = $selector->select();

        $selectedTerminals = array_filter($selectedTerminals);

        $this->assertEquals(1, sizeof($selectedTerminals));

        $terminal = $selectedTerminals[0];

        $this->assertEquals('hitachi', $terminal->getGateway());
        $this->assertEquals(HitachiGateway::BLACKLISTED_MCC[0], $terminal->getCategory());
    }

    public function testHitachiTerminalCreationOnRunWithZeroStratingCategory()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->merchant->setCategory('0240');

        $input = $this->getInputForHitachiTerminalCreationOnRun();

        $this->app['rzp.mode'] = Mode::TEST;

        $options = new Options;
        $selector = new Selector($input, $options);

        $this->terminalsServiceMock = $this->getTerminalsServiceMock();

        $category = "0240";
        $this->mockTerminalsServiceSendRequest(function () use($category){
            return $this->getHitachiOnboardResponseAndCreate($category);
        }, 1);

        $selectedTerminals = $selector->select();
        $this->assertEquals(1, sizeof($selectedTerminals));

        $terminal = $selectedTerminals[0];

        $this->assertEquals('hitachi', $terminal->getGateway());
        $this->assertEquals('0240', $terminal->getCategory());
    }

    // should not create terminal if methood is not card or emi
    public function testHitachiTerminalCreationOnRunForWallet()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->merchant->setCategory('1240');

        $merchantDetailArray = [
            'contact_name'                  => 'rzp',
            'contact_email'                 => 'test@rzp.com',
            'merchant_id'                   => '10000000000000',
            'business_operation_address'    => 'Koramangala',
            'business_operation_state'      => 'KARNATAKA',
            'business_operation_pin'        =>  560047,
            'business_dba'                  => 'test',
            'business_name'                 => 'rzp_test',
            'business_operation_city'       => 'Bangalore',
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailArray);

        $paymentArray = $this->getDefaultPaymentArray();
        unset($paymentArray['card']);
        $paymentArray['status'] = 'created';
        $paymentArray['method'] = 'wallet';
        $paymentArray['wallet'] = 'olamoney';


        $payment = (new Payment\Entity)->fill($paymentArray);

        $merchant = Merchant\Entity::find('10000000000000');

        $payment->merchant()->associate($merchant);

        $input = [
            'payment' => $payment,
            'merchant' => $payment->merchant
        ];

        $this->app['rzp.mode'] = Mode::TEST;

        $options = new Options;
        $selector = new Selector($input, $options);
        $selectedTerminals = $selector->select();

        $this->assertEquals(1, sizeof($selectedTerminals));

        $terminal = $selectedTerminals[0];

        $this->assertEquals(null, $terminal);
    }

    public function testHitachiTerminalIsDisabledOnMerchantMccEdit()
    {
        $merchantId = $this->setUpMerchantForHitachiTerminalDisableTest();

        $oldMccTerminal = $this->fixtures->create('terminal', $this->getTerminalCreateArrayForHitachiTerminalDisableTest([
            'category'    => '1234',
            'merchant_id' => $merchantId,
        ]));

        $this->assertTrue($oldMccTerminal->isEnabled());

        $this->editMerchant($merchantId, ['category' => "4321"]);

        $oldMccTerminal = $this->getEntityById('terminal', $oldMccTerminal['id'], true);

        $this->assertFalse($oldMccTerminal['enabled']);

        $this->assertEquals(Terminal\Status::DEACTIVATED, $oldMccTerminal[Terminal\Entity::STATUS]);

        // start onboarding flow

        $input = $this->getInputForHitachiTerminalTestOnMccUpdate($merchantId);

        $options = new Options;
        $selector = new Selector($input, $options);
        $this->terminalsServiceMock = $this->getTerminalsServiceMock();
        $category = "4321";
        $this->mockTerminalsServiceSendRequest(function () use($category){
            return $this->getHitachiOnboardResponseAndCreate($category);
        }, 1);
        $selectedTerminals = $selector->select();
        $this->assertEquals(1, sizeof($selectedTerminals));

        $terminal = $selectedTerminals[0];

        $this->assertEquals('hitachi', $terminal->getGateway());
        $this->assertEquals('4321', $terminal->getCategory());
    }

    public function testHitachiTerminalNotDisabledIfMccNotEdited()
    {
        $merchantId = $this->setUpMerchantForHitachiTerminalDisableTest();

        $terminal = $this->fixtures->create('terminal', $this->getTerminalCreateArrayForHitachiTerminalDisableTest([
            'category'    => '1234',
            'merchant_id' => $merchantId,
        ]));

        $this->assertTrue($terminal->isEnabled());

        $this->editMerchant($merchantId, ['name' => "new name"]);

        $terminal = $this->getEntityById('terminal', $terminal['id'], true);

        $this->assertTrue($terminal['enabled']);

        $this->assertEquals(Terminal\Status::ACTIVATED, $terminal[Terminal\Entity::STATUS]);
    }

    public function testHitachiTerminalMccEditBackAndForth()
    {
        $merchantId = $this->setUpMerchantForHitachiTerminalDisableTest();

        // merchant category is 1234 initially

        $terminalA = $this->fixtures->create('terminal', $this->getTerminalCreateArrayForHitachiTerminalDisableTest([
            'category'    => '4321',
            'merchant_id' => $merchantId,
            'enabled'     => false,
            'status'      => Terminal\Status::DEACTIVATED,
        ]));

        $terminalB = $this->fixtures->create('terminal', $this->getTerminalCreateArrayForHitachiTerminalDisableTest([
            'category'    => '1234',
            'merchant_id' => $merchantId,
            'enabled'     => true,
        ]));

        $this->editMerchant($merchantId, ['category' => '4321']);

        $this->assertFalse($this->getEntityById('terminal', $terminalA['id'], true)['enabled']);

        $this->assertFalse($this->getEntityById('terminal', $terminalB['id'], true)['enabled']);

        // start onboarding flow. assert new terminal doesnt get created.

        $input = $this->getInputForHitachiTerminalTestOnMccUpdate($merchantId);

        $options = new Options;
        $selector = new Selector($input, $options);
        $this->terminalsServiceMock = $this->getTerminalsServiceMock();
        $category = "4321";
        $this->mockTerminalsServiceSendRequest(function () use($category){
            return $this->getHitachiOnboardResponseAndCreate($category);
        }, 1);
        $selectedTerminals = $selector->select();
        $this->assertEquals(1, sizeof($selectedTerminals));

        $terminal = $selectedTerminals[0];

        $this->assertEquals('hitachi', $terminal->getGateway());
        $this->assertEquals('4321', $terminal->getCategory());
        $this->assertNotEquals($terminalA['id'], $terminal->getId());
        $this->assertNotEquals($terminalB['id'], $terminal->getId());
    }

    public function testHitachiTerminalSelectionWhenDisabledManually()
    {
        $merchantId = $this->setUpMerchantForHitachiTerminalDisableTest();

        $terminal = $this->fixtures->create('terminal', $this->getTerminalCreateArrayForHitachiTerminalDisableTest([
            'category'    => '1234',
            'merchant_id' => $merchantId,
            'enabled'     => true,
        ]));

        $this->ba->adminAuth();

        $request = [
          'url'      =>  '/terminals/' . $terminal['id'] . '/toggle',
          'method'   => 'PUT',
          'content'  => [
              'toggle' => false,
          ],
        ];

        $response = $this->makeRequestAndGetContent($request);

        $input = $this->getInputForHitachiTerminalTestOnMccUpdate($merchantId);

        $options = new Options;
        $selector = new Selector($input, $options);
        $selectedTerminals = $selector->select();
        $this->assertEquals(1, sizeof($selectedTerminals));

        $terminal = $selectedTerminals[0];

        $this->assertNull($terminal);
    }

    public function testHitachiTerminalMccMismatchIsNotDisabledOnMerchantMccEdit()
    {
        $merchantId = $this->setUpMerchantForHitachiTerminalDisableTest();

        $oldMccTerminal = $this->fixtures->create('terminal', $this->getTerminalCreateArrayForHitachiTerminalDisableTest([
            'category'    => '1234',
            'merchant_id' => $merchantId
        ]));

        $unrelatedMccTerminal = $this->fixtures->create('terminal', $this->getTerminalCreateArrayForHitachiTerminalDisableTest([
            'category'    => '9999',
            'merchant_id' => $merchantId
        ]));

        $this->assertTrue($oldMccTerminal->isEnabled());

        $this->assertTrue($unrelatedMccTerminal->isEnabled());

        $this->editMerchant($merchantId, ['category' => "4321"]);

        $this->assertFalse($this->getEntityById('terminal', $oldMccTerminal->getId(), true)['enabled']);

        $this->assertTrue($this->getEntityById('terminal', $unrelatedMccTerminal->getId(), true)['enabled']);
    }


    public function testNonHitachiTerminalNotDisabledOnMccEdit()
    {
        $merchantId = $this->setUpMerchantForHitachiTerminalDisableTest();

        $hitachiTerminal = $this->fixtures->create('terminal', $this->getTerminalCreateArrayForHitachiTerminalDisableTest([
            'category'    => '1234',
            'merchant_id' => $merchantId
        ]));

        $nonHitachiTerminal = $this->fixtures->create('terminal',  $defaults = [
            "category"           => "1234",
            "gateway"            => "hdfc",
            "gateway_merchant_id"=> "10",
            "gateway_terminal_id"=> "8",
            "gateway_acquirer"   =>"ratn",
            "mode"               => "3",
            "enabled"            => true,
        ]);

        $this->assertTrue($this->getEntityById('terminal', $hitachiTerminal->getId(), true)['enabled']);

        $this->assertTrue($this->getEntityById('terminal', $nonHitachiTerminal->getId(), true)['enabled']);

        $this->editMerchant($merchantId, ['category' => '4321']);

        $this->assertFalse($this->getEntityById('terminal', $hitachiTerminal->getId(), true)['enabled']);

        $this->assertTrue($this->getEntityById('terminal', $nonHitachiTerminal->getId(), true)['enabled']);

    }
    //should not create new terminal if already hitachi terminal exists
    public function testDuplicateHitachiTerminalCreationOnRun()
    {
        $this->enableRazorXTreatmentForFulcrumTerminal();
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->create('terminal:direct_hitachi_terminal');

        $this->fixtures->merchant->setCategory('1240');

        $input = $this->getInputForHitachiTerminalCreationOnRun();

        $this->app['rzp.mode'] = Mode::TEST;

        $options = new Options;
        $selector = new Selector($input, $options);
        $selectedTerminals = $selector->select();

        $this->assertEquals(1, sizeof($selectedTerminals));

        $terminal = $selectedTerminals[0];

        $this->assertEquals('hitachi', $terminal->getGateway());
        $this->assertEquals('hitachiDirectMerchantId', $terminal->getGatewayMerchantId());
        $this->assertEquals('hitachiDirectTerminalId', $terminal->getGatewayTerminalId());
    }

    //should not create new hitachi terminal as mocked response is of error
    public function testHitachiTerminalCreationTerminalServiceError()
    {
        $this->fixtures->merchant->setCategory('1240');

        $this->enableRazorXTreatmentForTerminalService();

        $this->terminalsServiceMock = $this->getTerminalsServiceMock();

        $this->mockTerminalsServiceSendRequest(function () {
            return $this->getHitachiOnboardErrorResponse();
        }, 1);

        $input = $this->getInputForHitachiTerminalCreationOnRun();

        $this->app['rzp.mode'] = Mode::TEST;

        $options = new Options;
        $selector = new Selector($input, $options);
        $selectedTerminals = $selector->select();

        // size of selectedTerminals should be 1 only with existing hdfc terminals, hitachi terminal won't be created
        $this->assertEquals(1, sizeof($selectedTerminals));

        $terminal = $selectedTerminals[0];

        $this->assertEquals('hdfc', $terminal->getGateway());
    }

    protected function enableRazorXTreatmentForFulcrumTerminal() {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    if ($feature === 'ROUTER_FULCRUM_ON_BOARDING')
                    {
                        return 'on';
                    }
                    return 'off';

                }) );
    }

    protected function enableRazorXTreatmentForTerminalService()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn('terminals');
    }

    public function testDuplicateHitachiTerminalCreationOnRunWithDisabledTerminal()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $terminal = $this->fixtures->create('terminal:direct_hitachi_terminal');

        $terminalId = $terminal->getId();

        // We are changing to international because all the other eligible
        // terminals which are created by fixtures have international=true
        // Since the test is to check for duplicate and international is
        // one of the parameters that we check against, we are making hitachi terminal as international.
        $this->fixtures->edit('terminal', $terminalId, ['enabled' => 0, 'international' => 1]);

        $this->fixtures->merchant->setCategory('1240');

        $input = $this->getInputForHitachiTerminalCreationOnRun();

        $this->app['rzp.mode'] = Mode::TEST;

        $options = new Options;
        $selector = new Selector($input, $options);
        $selectedTerminals = $selector->select();

        $this->assertEquals(1, sizeof($selectedTerminals));

        $terminal = $selectedTerminals[0];

        $this->assertEquals(null, $terminal);
    }

    public function testHitachiTerminalCreationBlacklistedMCC()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->app['rzp.mode'] = Mode::TEST;

        $cardArray = [
            'number'        => CardNumber::VALID_ENROLL_NUMBER,
            'expiry_month'  => '1',
            'expiry_year'   => '35',
            'cvv'           => '123',
            'name'          => 'Test',
        ];

        $card = (new Card\Entity)->fill($cardArray);

        $paymentArray = $this->getDefaultPaymentArray();
        unset($paymentArray['card']);
        $paymentArray['status'] = 'created';
        $paymentArray['method'] = 'card';

        foreach (Gateway::BLACKLISTED_MCC as $category)
        {
            $this->fixtures->merchant->setCategory($category);

            $payment = (new Payment\Entity)->fill($paymentArray);

            $payment->card()->associate($card);

            $merchant = Merchant\Entity::find('10000000000000');

            $payment->merchant()->associate($merchant);

            $input = [
                'payment' => $payment,
                'merchant' => $payment->merchant
            ];

            $options = new Options;
            $selector = new Selector($input, $options);
            $selectedTerminals = $selector->select();
            $this->assertEquals(1, sizeof($selectedTerminals));

            $terminal = $selectedTerminals[0];

            $this->assertEquals($terminal, null);
        }
    }

    public function testMswipeTerminalAssigning()
    {
        config(['app.query_cache.mock' => false]);

        $this->app['rzp.mode'] = Mode::TEST;

        $this->fixtures->merchant->addFeatures('use_mswipe_terminals');

        $this->fixtures->create('terminal', ['id' => 'C7EW8LggSH7FnY']);
        $this->fixtures->create('terminal', ['id' => 'CXjvHPZlPnqWBX']);
        $this->fixtures->create('terminal', ['id' => 'CNqL80h9pI0hsI']);
        $this->fixtures->create('terminal', ['id' => 'CHYaN0FnjkG5ni']);
        $this->fixtures->create('terminal', ['id' => 'CWybuzsFqa9KDz']);

        $cardArray = [
            'number'        => CardNumber::VALID_ENROLL_NUMBER,
            'expiry_month'  => '1',
            'expiry_year'   => '35',
            'cvv'           => '123',
            'name'          => 'Test',
        ];

        $card = (new Card\Entity)->fill($cardArray);

        $paymentArray = $this->getDefaultPaymentArray();
        unset($paymentArray['card']);
        $paymentArray['status'] = 'created';
        $paymentArray['method'] = 'card';

        $payment = (new Payment\Entity)->fill($paymentArray);

        $payment->card()->associate($card);

        $merchant = Merchant\Entity::find('10000000000000');

        $payment->merchant()->associate($merchant);

        $input = [
            'payment' => $payment,
            'merchant' => $payment->merchant,
            'card_mandate' => null
        ];

        $options = new Options;
        $selector = new Selector($input, $options);

        $selectedTerminals = $selector->select();

        $this->assertEquals(6, sizeof($selectedTerminals));
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

        $merchant = $this->fixtures->on(Mode::LIVE)->create('merchant', $attributes);
        $this->fixtures->on(Mode::LIVE)->create('balance', ['id' => $merchant->getId(), 'merchant_id' => $merchant->getId()]);

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

        $merchant = $this->fixtures->on(Mode::LIVE)->create('merchant', $attributes);
        $this->fixtures->on(Mode::LIVE)->create('balance', ['id' => $merchant->getId(), 'merchant_id' => $merchant->getId()]);

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

    public function testUpiFilterRejectsMindgate()
    {
        $iciciTerminal = $this->fixtures->create('terminal:shared_upi_icici_terminal', ['enabled' => false]);
        $mgTerminal = $this->fixtures->create('terminal:shared_upi_mindgate_terminal', ['gateway' => 'upi_mindgate', 'type' => []]);

        $this->fixtures->merchant->enableUpi();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function ()
        {
            $payment = $this->getDefaultUpiPaymentArray();
            unset($payment['vpa']);

            $payment['_']['flow'] = 'intent';

            $this->doAuthPayment($payment);
        });
    }

    public function testUpiFilterSelectsMindgate()
    {
        $iciciTerminal = $this->fixtures->create('terminal:shared_upi_icici_terminal', ['enabled' => false]);
        $mgTerminal = $this->fixtures->create('terminal:shared_upi_mindgate_terminal', ['gateway' => 'upi_mindgate']);

        $this->fixtures->merchant->enableUpi();

        $payment = $this->getDefaultUpiPaymentArray();

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($mgTerminal->getId(), $payment['terminal_id']);
    }

    public function testUpiFilterWithBharatQrFilter()
    {
        $mgTerminal = $this->fixtures->create('terminal:shared_upi_mindgate_terminal', ['gateway' => 'upi_mindgate']);

        $this->fixtures->merchant->enableUpi();

        $this->fixtures->create('terminal:bharat_qr_terminal_upi');

        $payment = $this->getDefaultUpiPaymentArray();

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($mgTerminal->getId(), $payment['terminal_id']);
    }

    public function testHitachiFilterWithBharatQrFilter()
    {
        $this->mockCardVault();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        // after addition of shared terminal filter in filtering there is no terminal in applicable terminals list
        // hence making hitachi shared terminal as direct so that it does not gets filtered out and payment flow can be tested
        $hitachiTerminal = $this->fixtures->create('terminal:direct_hitachi_terminal');

        $this->fixtures->create('terminal:bharat_qr_terminal');

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '5257834104683413';

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($hitachiTerminal->getId(), $payment['terminal_id']);
    }

    public function testHitachiFilterWithMotoFilter()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['auth_type'] = 'skip';

        $payment['card']['number'] = '5257834104683413';

        unset($payment['card']['cvv']);

        $hitachiTerminal = $this->fixtures->create('terminal:shared_hitachi_terminal');

        $motoTerminal = $this->fixtures->create('terminal:shared_hitachi_moto_terminal');

        $this->fixtures->merchant->addFeatures(['direct_debit']);

        $this->doAuthAndCapturePayment($payment);

        $lastPayment = $this->getLastEntity('payment', true);

        $this->assertEquals($lastPayment['terminal_id'], $motoTerminal['id']);
    }

    public function testHdfcFilterWithMotoFilter()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['auth_type'] = 'skip';

        $payment['card']['number'] = '5257834104683413';

        unset($payment['card']['cvv']);

        $hitachiTerminal = $this->fixtures->create('terminal:shared_hdfc_terminal');

        $motoTerminal = $this->fixtures->create('terminal:shared_hdfc_moto_terminal');

        $this->fixtures->merchant->addFeatures(['direct_debit']);

        $this->doAuthAndCapturePayment($payment);

        $lastPayment = $this->getLastEntity('payment', true);

        $this->assertEquals($lastPayment['terminal_id'], $motoTerminal['id']);
    }

    public function testNetbankingTerminalSelectedWithEnabledBanks()
    {
        $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal', [
            'enabled_banks' => ['HDFC'],
        ]);

        $payment = $this->getDefaultNetbankingPaymentArray("HDFC");
        $payment = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('netbanking_hdfc', $payment['gateway']);
    }

    public function testNetbankingTerminalNotSelectedWithoutEnabledBanks()
    {
        $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal', [
            'enabled_banks' => [],
        ]);

        $this->makeRequestAndCatchException(function ()
        {
            $payment = $this->getDefaultNetbankingPaymentArray("HDFC");

            $payment = $this->doAuthPayment($payment);
        }, RuntimeException::class, 'Terminal should not be null');
    }

    public function testTerminalSelectionWithNonActivatedTerminal()
    {
        $this->app['rzp.mode'] = Mode::TEST;

        $selectedTerminals = $this->runTestCase([]);

        $this->assertEquals(['1n25f6uN5S1Z5a'], $selectedTerminals);

        $this->fixtures->edit('terminal', '1n25f6uN5S1Z5a', [
            'status' => 'deactivated',
        ]);

        $selectedTerminals = $this->runTestCase([]);

        $this->assertEquals([null], $selectedTerminals);
    }

    protected function setUpPartnerAndGetSubMerchantId()
    {


        $subMerchant = $this->fixtures->create('merchant');

        $subMerchantId = $subMerchant->getId();

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'aggregator']);

        // Assign submerchant to partner
        $accessMapData = [
            'entity_type'     => 'application',
            'merchant_id'     => $subMerchantId,
            'entity_owner_id' => '10000000000000',
        ];

        $this->fixtures->create('merchant_access_map', $accessMapData);

        return $subMerchantId;
    }

    protected function getInputForHitachiTerminalCreationOnRun()
    {
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

        $merchantDetailArray = [
            'contact_name'                  => 'rzp',
            'contact_email'                 => 'test@rzp.com',
            'merchant_id'                   => '10000000000000',
            'business_operation_address'    => 'Koramangala',
            'business_operation_state'      => 'KARNATAKA',
            'business_operation_pin'        =>  560047,
            'business_dba'                  => 'test',
            'business_name'                 => 'rzp_test',
            'business_operation_city'       => 'Bangalore',
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailArray);

        $paymentArray = $this->getDefaultPaymentArray();
        unset($paymentArray['card']);
        $paymentArray['status'] = 'created';
        $paymentArray['method'] = 'card';

        $payment = (new Payment\Entity)->fill($paymentArray);

        $payment->card()->associate($card);
        $payment->setId("IUZpAQvrlzgaHU");

        $merchant = Merchant\Entity::find('10000000000000');

        $payment->merchant()->associate($merchant);

        $input = [
            'payment' => $payment,
            'merchant' => $payment->merchant,
            'card_mandate' => null
        ];

        return $input;
    }

    protected function getInputForFulcrumTerminalCreationOnRun()
    {
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

        $merchantDetailArray = [
            'contact_name'                  => 'rzp',
            'contact_email'                 => 'test@rzp.com',
            'merchant_id'                   => '10000000000000',
            'business_operation_address'    => 'Koramangala',
            'business_operation_state'      => 'KARNATAKA',
            'business_operation_pin'        =>  560047,
            'business_dba'                  => 'test',
            'business_name'                 => 'rzp_test',
            'business_operation_city'       => 'Bangalore',
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailArray);

        $paymentArray = $this->getDefaultPaymentArray();
        unset($paymentArray['card']);
        $paymentArray['status'] = 'created';
        $paymentArray['method'] = 'card';

        $payment = (new Payment\Entity)->fill($paymentArray);

        $payment->card()->associate($card);
        $payment->setId("IUZpAQvrlzgaHU");

        $merchant = Merchant\Entity::find('10000000000000');

        $payment->merchant()->associate($merchant);

        $input = [
            'payment' => $payment,
            'merchant' => $payment->merchant,
            'card_mandate' => null
        ];

        return $input;
    }

    public function testUpiOtmCollectTerminal()
    {
        $terminal = $this->fixtures->create('terminal:shared_upi_mindgate_terminal', [
            'type'    => [
                'otm_collect'   => '1',
                'non_recurring' => '1'
            ]
        ]);

        $this->fixtures->merchant->enableUpi();

        $payment = $this->getDefaultUpiOtmPayment();

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($terminal->getId(), $payment['terminal_id']);
    }

    public function testUpiOtmPayTerminal()
    {
        $terminal = $this->fixtures->create('terminal:shared_upi_mindgate_terminal', [
            'type'    => [
                'otm_pay'       => '1',
                'non_recurring' => '1'
            ]
        ]);

        $this->fixtures->merchant->enableUpi();

        $payment = $this->getDefaultUpiOtmPayment();

        unset($payment['upi']['vpa']);

        $payment['upi']['flow'] = 'intent';

        //TODO:  Intent is not support currently, remove this check when we start taking intent payments for upi otm
        $this->makeRequestAndCatchException(function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testUpiOtmCollectRejectTerminal()
    {
        $terminal = $this->fixtures->create('terminal:shared_upi_mindgate_terminal');

        $this->fixtures->merchant->enableUpi();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function ()
        {
            $payment = $this->getDefaultUpiOtmPayment();

            $this->doAuthPayment($payment);
        });
    }

    public function testUpiOtmCollectRejectsOtmIntentPayment()
    {
        $terminal = $this->fixtures->create('terminal:shared_upi_mindgate_terminal', [
            'type'    => [
                'otm_collect'   => '1',
                'non_recurring' => '1'
            ]
        ]);

        $this->fixtures->merchant->enableUpi();

        $payment = $this->getDefaultUpiOtmPayment();

        $payment['upi']['flow'] = 'intent';
        unset($payment['upi']['vpa']);

        //TODO:  Intent is not support currently, remove this check when we start taking intent payments for upi otm
        $this->makeRequestAndCatchException(function () use ($payment)
        {
            $this->doAuthPayment($payment);
        },
            BadRequestValidationFailureException::class,
            'Intent flow is not supported for upi mandates.');
    }

    protected function setUpMerchantForHitachiTerminalDisableTest()
    {
        $this->app['rzp.mode'] = Mode::TEST;

        $merchantAttributes = [
            'org_id'          => Org::RZP_ORG,
            'activated'       => 1,
            'live'            => 1,
            'pricing_plan_id' => '1hDYlICobzOCYt',
            'category'        => "1234",
        ];

        $merchant = $this->fixtures->on(Mode::LIVE)->create('merchant', $merchantAttributes);

        $merchantDetailArray = [
            'contact_name'                  => 'rzp',
            'contact_email'                 => 'test@rzp.com',
            'merchant_id'                   => $merchant['id'],
            'business_operation_address'    => 'Koramangala',
            'business_operation_state'      => 'KARNATAKA',
            'business_operation_pin'        =>  560047,
            'business_dba'                  => 'test',
            'business_name'                 => 'rzp_test',
            'business_operation_city'       => 'Bangalore',
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailArray);

        return $merchant['id'];
    }

    protected function editMerchant(string $merchantId, array $attributes)
    {
        $this->ba->adminAuth();

        $request = [
            'method'    => 'put',
            'content'   => $attributes,
            'url'       => "/merchants/" . $merchantId,
        ];

        return $this->makeRequestAndGetContent($request);
    }

    protected function getInputForHitachiTerminalTestOnMccUpdate($merchantId)
    {
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

        $payment->card()->associate($card);

        $merchant = Merchant\Entity::find($merchantId);

        $payment->merchant()->associate($merchant);

        return [
            'payment'    => $payment,
            'merchant'   => $payment->merchant,
            'card_mandate' => null,
            ];

    }

    protected function getTerminalCreateArrayForHitachiTerminalDisableTest(array $attributes = [])
    {
        $defaults = [
            "category"           => "1234",
            "gateway"            => "hitachi",
            "gateway_merchant_id"=> "10",
            "gateway_terminal_id"=> "8",
            "gateway_acquirer"   =>"ratn",
            "mode"               => "3",
            "enabled"            => true,
        ];

        return array_merge($defaults, $attributes);
    }

    protected function getTerminalCreateArrayForFulcrumTerminalDisableTest(array $attributes = [])
    {
        $defaults = [
            "category"           => "1234",
            "gateway"            => "fulcrum",
            "gateway_merchant_id"=> "10",
            "gateway_terminal_id"=> "8",
            "gateway_acquirer"   =>"ratn",
            "mode"               => "3",
            "enabled"            => true,
        ];

        return array_merge($defaults, $attributes);
    }

    /**
     *
     * For card payment if smart router selects no terminal ,
     * It should not fallback to API filters and sorters
     *
     * @throws RuntimeException
     * @throws \RZP\Exception\BadRequestException
     */
    public function testNoTerminalFoundFallbackForCard()
    {
        $this->app['rzp.mode'] = Mode::LIVE;

        // Setting env as production to allow hitting smart router
        $this->app['env'] = Environment::PRODUCTION;

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
        $paymentArray['status'] = 'created';
        $paymentArray['method'] = 'card';

        unset($paymentArray['card']);



        // Setting random payment ID to allow hitting smart router
        $paymentArray['id'] = 'randomid';

        $payment = (new Payment\Entity)->fill($paymentArray);

        $merchant = Merchant\Entity::find('10000000000000');

        $payment->merchant()->associate($merchant);

        $payment->card()->associate($card);

        $input = [
            'payment' => $payment,
            'merchant' => $payment->merchant
        ];

        $options = new Options;
        $selector = new Selector($input, $options);

        $this->makeRequestAndCatchException(
            function() use ($selector)
            {
                $selector->select();
            },
            RuntimeException::class, 'No terminal found.');
    }

    /**
     *
     * For In App UPI payment if smart router selects no terminal ,
     * It should not fallback to API filters and sorters
     *
     * @throws RuntimeException
     * @throws \RZP\Exception\BadRequestException
     */
    public function testNoTerminalFoundForInApp()
    {
        $this->app['rzp.mode'] = Mode::LIVE;
        $this->app['env']      = Environment::PRODUCTION;

        $upiMetadata = [
            'flow'        => 'intent',
            'mode'        => 'in_app',
        ];

        $upi = (new Payment\UpiMetadata\Entity)->fill($upiMetadata);

        $paymentArray = $this->getDefaultUpiPaymentArray();
        $paymentArray['status'] = 'created';

        unset($paymentArray['upi']);

        // Setting random payment ID to allow hitting smart router
        $paymentArray['id'] = 'randomid123';

        $payment = (new Payment\Entity)->fill($paymentArray);

        $merchant = Merchant\Entity::find('10000000000000');

        $payment->merchant()->associate($merchant);

        $upi->payment()->associate($payment);

        $input = [
            'payment' => $payment,
            'merchant' => $payment->merchant
        ];

        $options = new Options;
        $selector = new Selector($input, $options);

        $this->makeRequestAndCatchException(
            function() use ($selector)
            {
                $selector->select();
            },
            RuntimeException::class, 'No terminal found.');
    }

    public function testTerminalFoundForInApp()
    {
        $this->fixtures->create('terminal:upi_in_app_terminal');

        //$this->fixtures->merchant->setCategory('1240');

        $upiMetadata = [
            'flow'        => 'intent',
            'mode'        => 'in_app',
        ];

        $upi = (new Payment\UpiMetadata\Entity)->fill($upiMetadata);

        $paymentArray = $this->getDefaultUpiPaymentArray();
        $paymentArray['status'] = 'created';

        unset($paymentArray['upi']);

        // Setting random payment ID to allow hitting smart router
        $paymentArray['id'] = 'randomid123';

        $payment = (new Payment\Entity)->fill($paymentArray);

        $merchant = Merchant\Entity::find('10000000000000');

        $payment->merchant()->associate($merchant);

        $upi->payment()->associate($payment);

        $this->app['rzp.mode'] = Mode::TEST;

        $input = [
            'payment' => $payment,
            'merchant' => $payment->merchant
        ];

        $options = new Options;
        $selector = new Selector($input, $options);
        $selectedTerminals = $selector->select();

        $this->assertEquals(1, sizeof($selectedTerminals));
        $terminal = $selectedTerminals[0];
        $this->assertEquals('1000UpiInAppTl', $terminal->getId());
    }

    /**
     * Tests terminal selection using the 'enabled_wallets' field
     *
     * @throws RuntimeException
     * @throws \RZP\Exception\BadRequestException
     */
    public function testPayuTerminalForWallet()
    {
        $this->fixtures->create('terminal:payu_terminal');
        $paymentArray = $this->getDefaultPaymentArray();
        unset($paymentArray['card']);
        $paymentArray['status'] = 'created';
        $paymentArray['method'] = 'wallet';
        $paymentArray['wallet'] = 'jiomoney';


        $payment = (new Payment\Entity)->fill($paymentArray);

        $merchant = Merchant\Entity::find('10000000000000');

        $payment->merchant()->associate($merchant);

        $input = [
            'payment' => $payment,
            'merchant' => $payment->merchant,
            'card_mandate' => null
        ];

        $this->app['rzp.mode'] = Mode::TEST;

        $options = new Options;
        $selector = new Selector($input, $options);
        $selectedTerminals = $selector->select();

        $this->assertEquals(1, sizeof($selectedTerminals));

        $terminal = $selectedTerminals[0];

        $this->assertEquals('payu', $terminal->getGateway());
    }

    /**
     *
     * For wallet payment if smart router selects no terminal ,
     * It should not fallback to API filters and sorters
     *
     * @throws RuntimeException
     * @throws \RZP\Exception\BadRequestException
     */
    public function testNoTerminalFoundFallbackForWallet(){
        //for production we will set the mode as LIVE
        $this->app['rzp.mode']=Mode::LIVE;
        $this->app['env']=Environment::PRODUCTION;

        $paymentArray=$this->getDefaultWalletPaymentArray();

        //Setting random payment id to allow hitting the smart router
        $paymentArray['id']='randomid';

        $payment=(new Payment\Entity)->fill($paymentArray);
        $merchant=Merchant\Entity::find('10000000000000');
        $payment->merchant()->associate($merchant);

        $input = [
            'payment' =>$payment,
            'merchant'=>$payment->merchant
        ];
        $options = new Options;
        $selector=new Selector($input,$options);

        $this->makeRequestAndCatchException(
            function () use($selector){
                $selector->select();
            },
            RuntimeException::class,'No terminal found.');
    }
    /**
     *
     * For Net Banking payment if smart router selects no terminal ,
     * It should not fallback to API filters and sorters
     *
     * @throws RuntimeException
     * @throws \RZP\Exception\BadRequestException
     */
    public function testNoTerminalFoundFallbackForNB(){

        $paymentArray=$this->getDefaultNetbankingPaymentArray("HDFC");

        $paymentArray['id']='randomid';

        $payment=(new Payment\Entity)->fill($paymentArray);

        $merchant=Merchant\Entity::find('10000000000000');

        $payment->merchant()->associate($merchant);
        $this->setMerchantBanks(['HDFC']);  //this sets the mode as TEST

        //for production we will set the mode as LIVE
        $this->app['rzp.mode']=Mode::LIVE;

        $this->app['env']=Environment::PRODUCTION;
        $input = [
            'payment' =>$payment,
            'merchant'=>$payment->merchant
        ];
        $options = new Options;
        $selector=new Selector($input,$options);

        $this->makeRequestAndCatchException(
            function () use($selector){
                $selector->select();
            },
            BadRequestException::class,PublicErrorDescription::BAD_REQUEST_PAYMENT_BANK_NOT_ENABLED_FOR_MERCHANT);
    }

    // test to check whether unsupported network will return no terminals
    // See Payment/Gateway@isCardNetworkUnsupportedOnGateway
    // Slack: https://razorpay.slack.com/archives/CNV2GTFEG/p1673252018996959?thread_ts=1673001461.787579&cid=CNV2GTFEG
    public function testHitachiOnboardingForUnsupportedNetwork() {

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_hitachi_terminal');

        $this->fixtures->merchant->setCategory('1240');

        $input = $this->getInputForPayment('American Express', '374741410437245', '1234');

        $this->app['rzp.mode'] = Mode::TEST;

        $options = new Options;
        $selector = new Selector($input, $options);

        $this->terminalsServiceMock = $this->getTerminalsServiceMock();

        // asserting no calls to TS is made
        $category = "1240";
        $this->mockTerminalsServiceSendRequest(function () use($category){
            return $this->getHitachiOnboardResponseAndCreate($category);
        }, 0);

        $selectedTerminals = $selector->select();

        // No terminals should be selected since network is unsupported
        $this->assertEquals(1, sizeof($selectedTerminals));
        $this->assertNull($selectedTerminals[0]);
    }

    // test to check whether only supported network will return terminals
    public function testHitachiOnboardingForSupportedNetwork() {

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->create('terminal:shared_hitachi_terminal');
        $input = $this->getInputForPayment('Visa', '4012001036275556', '123');

        $this->app['rzp.mode'] = Mode::TEST;

        $options = new Options;
        $selector = new Selector($input, $options);
        $selectedTerminals = $selector->select();

        // Since network is supported, hitachi terminal should be returned
        $this->assertEquals(1, sizeof($selectedTerminals));
        $this->assertNotNull($selectedTerminals[0]);
        $this->assertEquals('hitachi', $selectedTerminals[0]['gateway']);
    }

    protected function getInputForPayment($network, $cardNumber, $cvv)
    {
        $cardArray = [
            'number'        => $cardNumber,
            'expiry_month'  => '12',
            'expiry_year'   => '2035',
            'cvv'           => $cvv,
            'network'       => $network,
            'issuer'        => 'HDFC',
            'name'          => 'Test',
            'international' => true,
        ];

        $card = (new Card\Entity)->fill($cardArray);

        $merchantDetailArray = [
            'contact_name'                  => 'rzp',
            'contact_email'                 => 'test@rzp.com',
            'merchant_id'                   => '10000000000000',
            'business_operation_address'    => 'Koramangala',
            'business_operation_state'      => 'KARNATAKA',
            'business_operation_pin'        =>  560047,
            'business_dba'                  => 'test',
            'business_name'                 => 'rzp_test',
            'business_operation_city'       => 'Bangalore',
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailArray);

        $paymentArray = $this->getDefaultPaymentArray();
        unset($paymentArray['card']);
        $paymentArray['status'] = 'created';
        $paymentArray['method'] = 'card';

        $payment = (new Payment\Entity)->fill($paymentArray);

        $payment->card()->associate($card);
        $payment->setId("IUZpaMvrlsraCB");

        $merchant = Merchant\Entity::find('10000000000000');

        $payment->merchant()->associate($merchant);

        $input = [
            'payment' => $payment,
            'merchant' => $payment->merchant,
            'card_mandate' => null
        ];

        return $input;
    }
}
