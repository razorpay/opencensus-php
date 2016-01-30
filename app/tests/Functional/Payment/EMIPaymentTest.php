<?php

namespace Tests\Functional\Payment;

use Tests\Functional\TestCase;
use Tests\Functional\Helpers\Payment\PaymentTrait;

class EmiPaymentTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        parent::setUp();

        $this->ba->publicAuth();

        $this->payment = $this->getDefaultPaymentArray();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
    }

    public function testEmiPaymentCreate()
    {
        $emiPlan = $this->fixtures->create('emi_plan:default_emi_plans');
        $this->fixtures->merchant->enableEmi();
        $this->ba->publicAuth();
        $this->payment['amount'] = 500000;
        $this->payment['method'] = 'emi';
        $this->payment['emi_duration'] = 9;
        $this->payment['card']['number'] = '41476700000006';

        $content = $this->doAuthAndCapturePayment($this->payment);

        $this->assertEquals($content['emi_plan_id'], $emiPlan[0]['id']);
        $this->assertEquals($content['method'], 'emi');
        $this->assertEquals($content['status'], 'captured');
        
        $this->fixtures->merchant->disableEmi();
    }
}