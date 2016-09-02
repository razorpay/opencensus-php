<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;

class SavedCardPaymentCreateTest extends TestCase
{
    use InteractsWithSession;
    use PaymentTrait;

    public function setUp()
    {
        parent::setUp();

        $this->ba->publicAuth();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->emiPlan = $this->fixtures->create('emi_plan:default_emi_plans');

        $this->mockTokenex();
    }

    /**
     * test card payment creation using a local saved card and token
     */
    public function testLocalSavedCardPaymentCreate()
    {
        $this->payment = $this->getDefaultPaymentArray();

        $this->payment['card'] = array('cvv'  => 111);
        $this->payment['token'] = '10000cardtoken';
        $this->payment['customer_id'] = 'cust_100000customer';

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['token'], '10000cardtoken');
        $this->assertEquals($payment['customer_id'], '100000customer');
    }

    /**
     * test card payment creation using a local saved card and token_id
     */
    public function testLocalSavedCardPaymentCreateWithTokenId()
    {
        $this->payment = $this->getDefaultPaymentArray();

        $this->payment['card'] = array('cvv'  => 111);
        $this->payment['token'] = 'token_100000custcard';
        $this->payment['customer_id'] = 'cust_100000customer';

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['token'], 'token_100000custcard');
        $this->assertEquals($payment['customer_id'], '100000customer');
    }

    /**
     * test emi payment creation using a local saved card
     */
    public function testLocalSavedCardEmiPaymentCreate()
    {
        $this->fixtures->merchant->enableEmi();

        $this->payment = $this->getDefaultPaymentArrayEmi(true);

        $this->payment['token'] = '10000cardtoken';
        $this->payment['customer_id'] = 'cust_100000customer';

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($payment['token'], '10000cardtoken');
        $this->assertEquals($payment['customer_id'], '100000customer');
    }

    /**
     * test card payment creation using global saved card and token
     */
    public function testGlobalSavedCardPaymentCreate()
    {
        $this->mockSession();

        $this->payment = $this->getDefaultPaymentArray();

        $this->payment['card'] = array(
            'cvv'  => 111
        );

        $this->payment['token'] = '1000gcardtoken';

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);
        $card = $this->getLastEntity('card', true);

        $this->assertEquals($payment['global_token'], '1000gcardtoken');
        $this->assertEquals($payment['app_token'], '1000000custapp');
        $this->assertEquals($card['global_card_id'], '100000000gcard');
    }

    /**
     * test card payment creation using global saved card and token_id
     */
    public function testGlobalSavedCardPaymentCreateWithTokenId()
    {
        $this->payment = $this->getDefaultPaymentArray();

        $this->payment['card'] = array(
            'cvv'  => 111
        );

        $this->payment['token'] = 'token_10000custgcard';
        $this->payment['app_token'] = 'capp_1000000custapp';

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);
        $card = $this->getLastEntity('card', true);

        $this->assertEquals($payment['global_token'], 'token_10000custgcard');
        $this->assertEquals($payment['app_token'], '1000000custapp');
        $this->assertEquals($card['global_card_id'], '100000000gcard');
    }

    /**
     * test emi payment creation using a global saved card
     */
    public function testGlobalSavedCardEmiPaymentCreate()
    {
        $this->mockSession();

        $this->fixtures->merchant->enableEmi();

        $this->payment = $this->getDefaultPaymentArrayEmi(true);

        $this->payment['token'] = '1000gcardtoken';

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);
        $card = $this->getLastEntity('card', true);

        $this->assertEquals($payment['global_token'], '1000gcardtoken');
        $this->assertEquals($payment['app_token'], '1000000custapp');
        $this->assertEquals($card['global_card_id'], '100000000gcard');
    }

    /**
     * test card payment with save card local
     */
    public function testPaymentCreateAndSaveCardLocal()
    {
        $this->payment = $this->getDefaultPaymentArray();

        $this->payment['save'] = 1;
        $this->payment['card']['number'] = '4000400000000004';
        $this->payment['customer_id'] = 'cust_100000customer';

        $content = $this->doAuthAndCapturePayment($this->payment);
        $payment = $this->getLastEntity('payment', true);
        $card = $this->getLastEntity('card', true);
        $token = $this->getLastEntity('token', true);

        $this->assertEquals($payment['card_id'], $card['id']);
        $this->assertEquals($payment['token'], $token['token']);
        $this->assertEquals('card_'.$token['card_id'], $card['id']);
        $this->assertEquals($payment['customer_id'], '100000customer');

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

        $this->payment = $this->getDefaultPaymentArrayEmi(false);

        $this->payment['save'] = 1;
        $this->payment['customer_id'] = 'cust_100000customer';

        $content = $this->doAuthAndCapturePayment($this->payment);
        $payment = $this->getLastEntity('payment', true);
        $card = $this->getLastEntity('card', true);
        $token = $this->getLastEntity('token', true);

        $this->assertEquals($payment['card_id'], $card['id']);
        $this->assertEquals($payment['token'], $token['token']);
        $this->assertEquals('card_'.$token['card_id'], $card['id']);
        $this->assertEquals($payment['customer_id'], '100000customer');

        $this->payment['card'] = array('cvv'  => 111);

        $this->payment['token'] = $token['token'];
        $this->payment['customer_id'] = 'cust_100000customer';

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
        $this->mockSession();

        $this->payment = $this->getDefaultPaymentArray();
        $this->payment['save'] = 1;
        $this->payment['card']['number'] = '4000400000000004';

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);
        $card = $this->getLastEntity('card', true);
        $token = $this->getLastEntity('token', true);


        $this->assertEquals($payment['card_id'], $card['id']);
        $this->assertNotEquals('card_'.$token['card_id'], $card['id']);
        $this->assertEquals($card['global_card_id'], $token['card_id']);
        $this->assertEquals($payment['app_token'], '1000000custapp');
        $this->assertEquals($payment['global_token'], $token['token']);
        $this->assertEquals($payment['token'], null);

        $this->payment['card'] = array('cvv'  => 111);
        $this->payment['token'] = $token['token'];

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);
        $card = $this->getLastEntity('card', true);

        $this->assertEquals($payment['global_token'], $token['token']);
        $this->assertEquals($payment['token'], null);
        $this->assertEquals($payment['app_token'], '1000000custapp');
        $this->assertEquals($card['global_card_id'], $token['card_id']);
    }

    /**
     * test emi payment with save card global
     */
    public function testEmiPaymentCreateAndSaveCardGlobal()
    {
        $this->mockSession();

        $this->fixtures->merchant->enableEmi();

        $this->payment = $this->getDefaultPaymentArrayEmi(false);
        $this->payment['save'] = 1;

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);
        $card = $this->getLastEntity('card', true);
        $token = $this->getLastEntity('token', true);

        $this->assertEquals($payment['card_id'], $card['id']);
        $this->assertNotEquals('card_'.$token['card_id'], $card['id']);
        $this->assertEquals($card['global_card_id'], $token['card_id']);
        $this->assertEquals($payment['app_token'], '1000000custapp');
        $this->assertEquals($payment['global_token'], $token['token']);
        $this->assertEquals($payment['token'], null);

        $this->payment['token'] = $token['token'];

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);
        $card = $this->getLastEntity('card', true);

        $this->assertEquals($payment['global_token'], $token['token']);
        $this->assertEquals($payment['token'], null);
        $this->assertEquals($payment['app_token'], '1000000custapp');
        $this->assertEquals($card['global_card_id'], $token['card_id']);
    }

    /**
     * test card multiple payments with save card local, only one card should be saved
     */
    public function testMultiplePaymentsCreateAndSaveCardLocal()
    {
        $this->payment = $this->getDefaultPaymentArray();

        $this->payment['save'] = 1;
        $this->payment['card']['number'] = '4000400000000004';
        $this->payment['card']['expiry_year'] = '20';
        $this->payment['customer_id'] = 'cust_100000customer';

        $content = $this->doAuthAndCapturePayment($this->payment);
        $payment1 = $this->getLastEntity('payment', true);

        $content = $this->doAuthAndCapturePayment($this->payment);
        $payment2 = $this->getLastEntity('payment', true);

        $this->assertEquals($payment1['card_id'], $payment2['card_id']);
    }

    /**
     * test card multiple payments with save card local, only one card should be saved
     */
    public function testCustomerFetchPayments()
    {
        $this->testPaymentCreateAndSaveCardGlobal();

        $this->mockSession();

        $this->ba->publicAuth();

        $request = array(
            'url' => '/apps/payments',
            'method' => 'get',
            'content' => [
                'skip'  => 1
            ]);

        $payments = $this->makeRequestAndGetContent($request);

        $this->assertEquals(empty($payments), false);
        $this->assertEquals($payments['entity'], 'collection');
        $this->assertEquals($payments['count'], 1);
    }

    protected function mockSession()
    {
        $data = array(
            'test_app_token' => 'capp_1000000custapp'
        );

        $this->session($data);
    }
}