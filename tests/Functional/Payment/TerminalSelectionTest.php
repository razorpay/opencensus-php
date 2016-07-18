<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Exception\RuntimeException;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

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

        $this->assertEquals($payment['emi_plan_id'], $emiPlan[0]['id']);
        $this->assertEquals($payment['method'], 'emi');
        $this->assertEquals($payment['status'], 'captured');

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

}
