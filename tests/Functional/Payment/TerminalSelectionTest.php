<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Exception\RuntimeException;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant;
use RZP\Models\Terminal\Options;
use RZP\Models\Payment\Gateway;
use RZP\Models\Terminal\Sorters\TerminalLoadSorter;

class TerminalSelectionTest extends TestCase
{
    use PaymentTrait;

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

    public function testTrialGatewaySelection()
    {
        $this->fixtures->create('terminal:shared_hdfc_terminal');
        $this->fixtures->create('terminal:shared_axis_terminal');

        $payment = $this->getDefaultPaymentArray();

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        // Payment will use default hdfc gateway
        $this->assertEquals('hdfc', $payment['gateway']);

        // We have to assert that HDFC won't be selected
        $trialGateway = [
            Gateway::HDFC
        ];

        // Set HDFC as trial gateway and assert that axis is used
        TerminalLoadSorter::setTestTrialGateways($trialGateway);

        $payment = $this->getDefaultPaymentArray();

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        // sd($payment['terminal_id']);

        // payment will use axis bank
        $this->assertEquals('axis_migs', $payment['gateway']);

        // Unsetting Gateway::HDFC as trial
        TerminalLoadSorter::setTestTrialGateways([]);
    }

    // protected function setTrialGateway($)

    public function testTerminalChoiceonChance()
    {
        $this->fixtures->create('terminal:all_shared_terminals');
        $this->mockTokenex();

        $chances = [
            // Chance from 86 to 95 should give Cybersource
            [ 'chanceValue' => 95, 'expected_terminal_id' => '1000CybrsTrmnl' ],
            // Chance from 80 to 85 should give First Data
            [ 'chanceValue' => 81,  'expected_terminal_id' => '1000FrstDataTl' ],
            // Chance 80 or below should give HDFC
            [ 'chanceValue' => 0,   'expected_terminal_id' => '1n25f6uN5S1Z5a' ],

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
             ['id' => 'DrctNbBdkTmnl1',
              'merchant_id' => Merchant\Account::TEST_ACCOUNT,
              'shared' => 0]);

        $this->fixtures->create('terminal:shared_billdesk_terminal',
             ['id' => 'DrctNbBdkTmnl2',
              'merchant_id' => Merchant\Account::TEST_ACCOUNT,
              'network_category' => 'ecommerce',
              'shared' => 0]);

        $this->fixtures->create('terminal:shared_billdesk_terminal',
             ['id' => 'DrctNbBdkTmnl3',
              'merchant_id' => Merchant\Account::TEST_ACCOUNT,
              'network_category' => 'securities',
              'shared' => 0]);

        $this->fixtures->create('terminal:shared_billdesk_terminal',
             ['id' => 'SharNbBdkTmnl1',
              'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
              'network_category' => 'securities']);

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

    public function testOlderTPVMerchantTerminalSelection()
    {
        $this->markTestSkipped('Not required now, users migrated.');

        $this->fixtures->merchant->setCategory('6211');

        $this->fixtures->create('terminal:shared_billdesk_terminal',
             ['id' => 'SharNbBdkTmnl1',
              'merchant_id' => Merchant\Account::SHARED_ACCOUNT]);

        $this->fixtures->create('terminal:shared_billdesk_terminal',
             ['id' => 'SharNbBdkTmnl2',
              'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
              'category' => '1234']);

        $this->fixtures->create('terminal:shared_billdesk_terminal',
             ['id' => 'SharNbBdkTmnl3',
              'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
              'category' => '6211']);

        $payment = $this->getPaymentForTPV(['bank' => 'ICIC']);

        $this->doAuthAndCapturePayment($payment);

        $payment1 = $this->getLastEntity('payment', true);

        $this->assertEquals('SharNbBdkTmnl3', $payment1['terminal_id']);

        $this->fixtures->terminal->edit('SharNbBdkTmnl3',['network_category' => 'securities']);

        $payment = $this->getPaymentForTPV(['bank' => 'ICIC']);

        // TPV payment should not be routed through either ecommerce or null terminal
        $this->makeRequestAndCatchException(function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $this->fixtures->merchant->editCategory2('securities');

        $payment = $this->getPaymentForTPV(['bank' => 'ICIC']);

        $this->doAuthAndCapturePayment($payment);
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
}
