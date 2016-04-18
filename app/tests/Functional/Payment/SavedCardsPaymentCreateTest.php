<?php

namespace Tests\Functional\Payment;

use Tests\Functional\TestCase;
use Tests\Functional\Helpers\Payment\PaymentTrait;

class SavedCardPaymentCreateTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        parent::setUp();

        $this->ba->publicAuth();

        $this->payment = $this->getDefaultPaymentArray();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->emiPlan = $this->fixtures->create('emi_plan:default_emi_plans');

        $this->mockTokenex();
    }

    /**
     * test card payment creation using a local saved card
     */
    public function testLocalSavedCardPaymentCreate()
    {
        $this->payment['card'] = array('cvv'  => 111);
        $this->payment['token'] = '10000cardtoken';
        $this->payment['customer_id'] = 'cust_100000customer';

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($payment['token'], '10000cardtoken');
        $this->assertEquals($payment['customer_id'], '100000customer');
    }

    /**
     * test emi payment creation using a local saved card
     */
    public function testLocalSavedCardEmiPaymentCreate()
    {
        $this->fixtures->merchant->enableEmi();

        $this->payment['card'] = array('cvv'  => 111);
        $this->payment['method'] = 'emi';
        $this->payment['emi_duration'] = 9;
        $this->payment['amount'] = '300000';
        $this->payment['token'] = '10000cardtoken';
        $this->payment['customer_id'] = 'cust_100000customer';

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($payment['token'], '10000cardtoken');
        $this->assertEquals($payment['customer_id'], '100000customer');
    }

    /**
     * test card payment creation using global saved card
     */
    public function testGlobalSavedCardPaymentCreate()
    {
        $this->payment['card'] = array(
            'cvv'  => 111
        );

        $this->payment['token'] = '1000gcardtoken';
        $this->payment['app_id'] = 'uuuu_1000000custapp';

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);
        $card = $this->getLastEntity('card', true);

        $this->assertEquals($payment['token'], '1000gcardtoken');
        $this->assertEquals($payment['app_id'], '1000000custapp');
        $this->assertEquals($card['global_card_id'], '100000000gcard');
    }

    /**
     * test emi payment creation using a global saved card
     */
    public function testGlobalSavedCardEmiPaymentCreate()
    {
        $this->fixtures->merchant->enableEmi();

        $this->payment['card'] = array('cvv'  => 111);
        $this->payment['method'] = 'emi';
        $this->payment['emi_duration'] = 9;
        $this->payment['amount'] = '300000';
        $this->payment['token'] = '1000gcardtoken';
        $this->payment['app_id'] = 'uuuu_1000000custapp';

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);
        $card = $this->getLastEntity('card', true);

        $this->assertEquals($payment['token'], '1000gcardtoken');
        $this->assertEquals($payment['app_id'], '1000000custapp');
        $this->assertEquals($card['global_card_id'], '100000000gcard');
    }

    /**
     * test card payment with save card local
     */
    public function testPaymentCreateAndSaveCardLocal()
    {
        $this->payment['save'] = 1;
        $this->payment['card']['number'] = '4000400000000004';
        $this->payment['customer_id'] = 'cust_100000customer';

        $content = $this->doAuthAndCapturePayment($this->payment);
        $payment = $this->getLastEntity('payment', true);
        $card = $this->getLastEntity('card', true);
        $token = $this->getLastEntity('token', true);

        $this->assertEquals('card_'.$payment['card_id'], $card['id']);
        $this->assertEquals('card_'.$token['card_id'], $card['id']);

        $this->payment['card'] = array('cvv'  => 111);

        $this->payment['token'] = $token['token'];
        $this->payment['customer_id'] = 'cust_100000customer';

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($payment['token'], $token['token']);
        $this->assertEquals($payment['customer_id'], '100000customer');
    }

    /**
     * test emi payment with save card local
     */
    public function testEmiPaymentCreateAndSaveCardLocal()
    {
        $this->fixtures->merchant->enableEmi();

        $this->payment['save'] = 1;
        $this->payment['card']['number'] = '41476700000006';
        $this->payment['method'] = 'emi';
        $this->payment['emi_duration'] = 9;
        $this->payment['amount'] = '300000';
        $this->payment['customer_id'] = 'cust_100000customer';

        $content = $this->doAuthAndCapturePayment($this->payment);
        $payment = $this->getLastEntity('payment', true);
        $card = $this->getLastEntity('card', true);
        $token = $this->getLastEntity('token', true);

        $this->assertEquals('card_'.$payment['card_id'], $card['id']);
        $this->assertEquals('card_'.$token['card_id'], $card['id']);

        $this->payment['card'] = array('cvv'  => 111);

        $this->payment['token'] = $token['token'];
        $this->payment['customer_id'] = 'cust_100000customer';
        $this->payment['method'] = 'emi';
        $this->payment['emi_duration'] = 9;
        $this->payment['amount'] = '300000';

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($payment['token'], $token['token']);
        $this->assertEquals($payment['customer_id'], '100000customer');
    }

    /**
     * test card payment with save card global
     */
    public function testPaymentCreateAndSaveCardGlobal()
    {
        $this->payment['save'] = 1;
        $this->payment['card']['number'] = '4000400000000004';
        $this->payment['app_id'] = 'uuuu_1000000custapp';

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);
        $card = $this->getLastEntity('card', true);
        $token = $this->getLastEntity('token', true);

        $this->assertEquals('card_'.$payment['card_id'], $card['id']);
        $this->assertNotEquals('card_'.$token['card_id'], $card['id']);
        $this->assertEquals($card['global_card_id'], $token['card_id']);

        $this->payment['card'] = array('cvv'  => 111);
        $this->payment['token'] = $token['token'];
        $this->payment['app_id'] = 'uuuu_1000000custapp';

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);
        $card = $this->getLastEntity('card', true);

        $this->assertEquals($payment['token'], $token['token']);
        $this->assertEquals($payment['app_id'], '1000000custapp');
        $this->assertEquals($card['global_card_id'], $token['card_id']);
    }

    /**
     * test emi payment with save card global
     */
    public function testEmiPaymentCreateAndSaveCardGlobal()
    {
        $this->fixtures->merchant->enableEmi();

        $this->payment['save'] = 1;
        $this->payment['card']['number'] = '41476700000006';
        $this->payment['method'] = 'emi';
        $this->payment['emi_duration'] = 9;
        $this->payment['amount'] = '300000';
        $this->payment['app_id'] = 'uuuu_1000000custapp';

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);
        $card = $this->getLastEntity('card', true);
        $token = $this->getLastEntity('token', true);

        $this->assertEquals('card_'.$payment['card_id'], $card['id']);
        $this->assertNotEquals('card_'.$token['card_id'], $card['id']);
        $this->assertEquals($card['global_card_id'], $token['card_id']);

        $this->payment['card'] = array('cvv'  => 111);
        $this->payment['token'] = $token['token'];
        $this->payment['app_id'] = 'uuuu_1000000custapp';
        $this->payment['method'] = 'emi';
        $this->payment['emi_duration'] = 9;
        $this->payment['amount'] = '300000';

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);
        $card = $this->getLastEntity('card', true);

        $this->assertEquals($payment['token'], $token['token']);
        $this->assertEquals($payment['app_id'], '1000000custapp');
        $this->assertEquals($card['global_card_id'], $token['card_id']);
    }
}