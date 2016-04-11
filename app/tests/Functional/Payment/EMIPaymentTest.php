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

        $this->mockTokenex();
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

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['emi_plan_id'], $emiPlan[0]['id']);
        $this->assertEquals($payment['method'], 'emi');
        $this->assertEquals($payment['status'], 'captured');

        $this->fixtures->merchant->disableEmi();
    }

    public function testEmiFileGenerate()
    {
        $emiPlan = $this->fixtures->create('emi_plan:default_emi_plans');

        $this->fixtures->merchant->enableEmi();

        $this->ba->publicAuth();

        //Kotak Card
        $this->makeEmiPaymentOnCard('4280951000002433', 9);

        //Axis Card
        $this->makeEmiPaymentOnCard('4111460212312338', 3);

        $request = array(
            'method' => 'POST',
            'url' => '/emi/generate/excel',
            'content' => array());

        $this->ba->privateAuth();

        $content = $this->makeRequestAndGetContent($request);

        $this->fixtures->merchant->disableEmi();
    }

    protected function makeEmiPaymentOnCard($card, $emiDuration)
    {
        $this->payment['amount'] = 500000;
        $this->payment['method'] = 'emi';
        $this->payment['emi_duration'] = $emiDuration;
        $this->payment['card']['number'] = $card;

        $this->doAuthAndCapturePayment($this->payment);
    }

    public function testEmiPaymentEmiNotSupported()
    {
        $emiPlan = $this->fixtures->create('emi_plan:default_emi_plans');
        $this->fixtures->merchant->enableEmi();
        $this->ba->publicAuth();
        $this->payment['amount'] = 500000;
        $this->payment['method'] = 'emi';
        $this->payment['emi_duration'] = 9;
        $this->payment['card']['number'] = '4000400000000004';

        $this->changeEnvToNonTest();
        $content = $this->doAuthPayment($this->payment);

        $this->assertEquals($content['error']['http_status_code'], 400);
        $this->assertEquals($content['error']['internal_error_code'], 'BAD_REQUEST_PAYMENT_EMI_NOT_AVAILABLE_ON_CARD');
    }
}
