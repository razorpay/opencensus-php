<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;

class SavedCardsPaymentCreateTest extends TestCase
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
        // set payment data using token
        $this->payment = $this->getDefaultPaymentArray();

        $this->payment['card'] = array('cvv'  => 111);

        $this->payment['token'] = '10000cardtoken';

        $this->payment['customer_id'] = 'cust_100000customer';

        // create and fetch payment
        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        // validations
        $this->assertEquals($payment['token_id'], '100000custcard');

        $this->assertEquals($payment['global_token_id'], null);

        $this->assertEquals($payment['customer_id'], '100000customer');

        $this->assertEquals($payment['global_customer_id'], null);
    }

    /**
     * test card payment creation using a local saved card and token_id
     */
    public function testLocalSavedCardPaymentCreateWithTokenId()
    {
        // create payment data using token id
        $this->payment = $this->getDefaultPaymentArray();

        $this->payment['card'] = array('cvv'  => 111);

        $this->payment['token'] = 'token_100000custcard';

        $this->payment['customer_id'] = 'cust_100000customer';

        // create and fetch payment
        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        // validations
        $this->assertEquals($payment['token_id'], '100000custcard');

        $this->assertEquals($payment['global_token_id'], null);

        $this->assertEquals($payment['customer_id'], '100000customer');

        $this->assertEquals($payment['global_customer_id'], null);
    }

    /**
     * test emi payment creation using a local saved card
     */
    public function testLocalSavedCardEmiPaymentCreate()
    {
        // set emi payment data
        $this->fixtures->merchant->enableEmi();

        $this->payment = $this->getDefaultEmiPaymentArray(true);

        $this->payment['token'] = '10000cardtoken';

        $this->payment['customer_id'] = 'cust_100000customer';

        // create payment and fetch from db
        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        // validations
        $this->assertEquals($payment['token_id'], '100000custcard');

        $this->assertEquals($payment['global_token_id'], null);

        $this->assertEquals($payment['customer_id'], '100000customer');

        $this->assertEquals($payment['global_customer_id'], null);
    }

    /**
     * test card payment creation using global saved card and token
     */
    public function testGlobalSavedCardPaymentCreate()
    {
        // create payment data
        $this->mockSession();

        $this->payment = $this->getDefaultPaymentArray();

        $this->payment['card'] = ['cvv' => 111];

        $this->payment['token'] = '1000gcardtoken';

        // create payment and fetch entities
        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $card = $this->getLastEntity('card', true);

        // vallidations
        $this->assertEquals($payment['token_id'], null);

        $this->assertEquals($payment['global_token_id'], '10000custgcard');

        $this->assertEquals($payment['app_token'], '1000000custapp');

        $this->assertEquals($card['global_card_id'], '100000000gcard');

        $this->assertEquals($payment['global_customer_id'], '10000gcustomer');

        $this->assertEquals($payment['customer_id'], null);
    }

    /**
     * test card payment creation using global saved card and token_id
     */
    public function testGlobalSavedCardPaymentCreateWithTokenId()
    {
        // create payment data
        $this->payment = $this->getDefaultPaymentArray();

        $this->payment['card'] = ['cvv' => 111];

        $this->payment['token'] = 'token_10000custgcard';

        $this->payment['app_token'] = 'capp_1000000custapp';

        // create payment and fetch entities
        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $card = $this->getLastEntity('card', true);

        // validations
        $this->assertEquals($payment['global_token_id'], '10000custgcard');

        $this->assertEquals($payment['app_token'], '1000000custapp');

        $this->assertEquals($card['global_card_id'], '100000000gcard');

        $this->assertEquals($payment['global_customer_id'], '10000gcustomer');

        $this->assertEquals($payment['customer_id'], null);
    }

    /**
     * test emi payment creation using a global saved card
     */
    public function testGlobalSavedCardEmiPaymentCreate()
    {
        // create payment data
        $this->mockSession();

        $this->fixtures->merchant->enableEmi();

        $this->payment = $this->getDefaultEmiPaymentArray(true);

        $this->payment['token'] = '1000gcardtoken';

        // create payment and fetch entities
        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $card = $this->getLastEntity('card', true);

        // validations
        $this->assertEquals($payment['global_token_id'], '10000custgcard');

        $this->assertEquals($payment['app_token'], '1000000custapp');

        $this->assertEquals($card['global_card_id'], '100000000gcard');

        $this->assertEquals($payment['global_customer_id'], '10000gcustomer');

        $this->assertEquals($payment['customer_id'], null);
    }

    /**
     * test card payment with save card local
     */
    public function testPaymentCreateAndSaveCardLocal()
    {
        // create payment data
        $this->payment = $this->getDefaultPaymentArray();

        $this->payment['save'] = 1;

        $this->payment['card']['number'] = '4000400000000004';

        $this->payment['customer_id'] = 'cust_100000customer';

        // create payment and fetch entities
        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $card = $this->getLastEntity('card', true);

        $token = $this->getLastEntity('token', true);

        // validations
        $this->assertEquals($payment['card_id'], $card['id']);

        $this->assertEquals('token_'.$payment['token_id'], $token['id']);

        $this->assertEquals('card_'.$token['card_id'], $card['id']);

        $this->assertEquals($payment['customer_id'], '100000customer');

        $this->assertEquals($payment['global_customer_id'], null);

        $this->assertEquals($token['used_count'], 1);

        $this->assertNotEquals($token['used_at'], null);

        // create payment data from newly saved tokens
        $this->payment['card'] = array('cvv'  => 111);

        $this->payment['token'] = $token['token'];

        $this->payment['customer_id'] = 'cust_100000customer';

        // create payment and fetch entities
        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $token = $this->getLastEntity('token', true);

        // validations
        $this->assertEquals('token_'.$payment['token_id'], $token['id']);

        $this->assertEquals($payment['global_token_id'], null);

        $this->assertEquals($payment['customer_id'], '100000customer');

        $this->assertEquals($payment['global_customer_id'], null);

        $this->assertEquals($token['used_count'], 2);

        $this->assertNotEquals($token['used_at'], null);
    }

    /**
     * test emi payment with save card local
     */
    public function testEmiPaymentCreateAndSaveCardLocal()
    {
        // sets payment data
        $this->fixtures->merchant->enableEmi();

        $this->payment = $this->getDefaultEmiPaymentArray(false);

        $this->payment['save'] = 1;

        $this->payment['customer_id'] = 'cust_100000customer';

        // create payment and fetch entities
        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $card = $this->getLastEntity('card', true);

        $token = $this->getLastEntity('token', true);

        // validations
        $this->assertEquals($payment['card_id'], $card['id']);

        $this->assertEquals('token_'.$payment['token_id'], $token['id']);

        $this->assertEquals('card_'.$token['card_id'], $card['id']);

        $this->assertEquals($payment['customer_id'], '100000customer');

        $this->assertEquals($token['used_count'], 1);

        $this->assertNotEquals($token['used_at'], null);

        // create another payment using new saved token and fetch enttites
        $this->payment['card'] = array('cvv'  => 111);

        $this->payment['token'] = $token['token'];

        $this->payment['customer_id'] = 'cust_100000customer';

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $token = $this->getLastEntity('token', true);

        // validations
        $this->assertEquals('token_'.$payment['token_id'], $token['id']);

        $this->assertEquals($payment['global_token_id'], null);

        $this->assertEquals($payment['customer_id'], '100000customer');

        $this->assertEquals($payment['global_customer_id'], null);

        $this->assertEquals($token['used_count'], 2);

        $this->assertNotEquals($token['used_at'], null);
    }

    /**
     * test card payment with save card global
     */
    public function testPaymentCreateAndSaveCardGlobal()
    {
        // set payment data
        $this->mockSession();

        $this->payment = $this->getDefaultPaymentArray();

        $this->payment['save'] = 1;

        $this->payment['card']['number'] = '4000400000000004';

        // create payment and fetch entities
        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $card = $this->getLastEntity('card', true);

        $token = $this->getLastEntity('token', true);

        // validations
        $this->assertEquals($payment['card_id'], $card['id']);

        $this->assertNotEquals('card_'.$token['card_id'], $card['id']);

        $this->assertEquals($card['global_card_id'], $token['card_id']);

        $this->assertEquals($payment['app_token'], '1000000custapp');

        $this->assertEquals('token_'.$payment['global_token_id'], $token['id']);

        $this->assertEquals($payment['token_id'], null);

        $this->assertEquals($token['used_count'], 1);

        $this->assertNotEquals($token['used_at'], null);

        // create another payment with new token and fetch entities
        $this->payment['card'] = array('cvv'  => 111);

        $this->payment['token'] = $token['token'];

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $card = $this->getLastEntity('card', true);

        $token = $this->getLastEntity('token', true);

        // validations
        $this->assertEquals('token_'.$payment['global_token_id'], $token['id']);

        $this->assertEquals($payment['token_id'], null);

        $this->assertEquals($payment['app_token'], '1000000custapp');

        $this->assertEquals($card['global_card_id'], $token['card_id']);

        $this->assertEquals($payment['customer_id'], null);

        $this->assertEquals($payment['global_customer_id'], '10000gcustomer');

        $this->assertEquals($token['used_count'], 2);

        $this->assertNotEquals($token['used_at'], null);
    }

    /**
     * test emi payment with save card global
     */
    public function testEmiPaymentCreateAndSaveCardGlobal()
    {
        // set payment data
        $this->mockSession();

        $this->fixtures->merchant->enableEmi();

        $this->payment = $this->getDefaultEmiPaymentArray(false);

        $this->payment['save'] = 1;

        // create emi payment and fetch entities
        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $card = $this->getLastEntity('card', true);

        $token = $this->getLastEntity('token', true);

        // validations
        $this->assertEquals($payment['card_id'], $card['id']);

        $this->assertNotEquals('card_'.$token['card_id'], $card['id']);

        $this->assertEquals($card['global_card_id'], $token['card_id']);

        $this->assertEquals($payment['app_token'], '1000000custapp');

        $this->assertEquals('token_'.$payment['global_token_id'], $token['id']);

        $this->assertEquals($payment['token_id'], null);

        $this->assertEquals($token['used_count'], 1);

        $this->assertNotEquals($token['used_at'], null);

        // create another payment using new token and fetch entities
        $this->payment['token'] = $token['token'];

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $card = $this->getLastEntity('card', true);

        $token = $this->getLastEntity('token', true);

        // validations
        $this->assertEquals('token_'.$payment['global_token_id'], $token['id']);

        $this->assertEquals($payment['token_id'], null);

        $this->assertEquals($payment['app_token'], '1000000custapp');

        $this->assertEquals($card['global_card_id'], $token['card_id']);

        $this->assertEquals($payment['customer_id'], null);

        $this->assertEquals($payment['global_customer_id'], '10000gcustomer');

        $this->assertEquals($token['used_count'], 2);

        $this->assertNotEquals($token['used_at'], null);
    }

    /**
     * test card multiple payments with save card local, only one card should be saved
     */
    public function testMultiplePaymentsCreateAndSaveCardLocal()
    {
        // create payment data
        $this->payment = $this->getDefaultPaymentArray();

        $this->payment['save'] = 1;

        $this->payment['card']['number'] = '4000400000000004';

        $this->payment['card']['expiry_year'] = '20';

        $this->payment['customer_id'] = 'cust_100000customer';

        // create payment 1
        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment1 = $this->getLastEntity('payment', true);

        // create payment 2
        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment2 = $this->getLastEntity('payment', true);

        // validate cards
        $this->assertEquals($payment1['card_id'], $payment2['card_id']);
    }

    /**
     * test card multiple payments with save card local, only one card should be saved
     */
    public function testCustomerFetchPayments()
    {
        // create payments and fetch on public auth
        $this->testPaymentCreateAndSaveCardGlobal();

        $this->mockSession();

        $this->ba->publicAuth();

        $request = array(
            'url'     => '/apps/payments',
            'method'  => 'get',
            'content' => [
                'skip'  => 1
            ]);

        $payments = $this->makeRequestAndGetContent($request);

        // validations
        $this->assertEquals(empty($payments), false);

        $this->assertEquals($payments['entity'], 'collection');

        $this->assertEquals($payments['count'], 1);
    }

    /**
     * test card multiple payments with save card local, only one card should be saved
     */
    public function testCustomerFetchPaymentsInvalidApp()
    {
        // create payments and fetch on public auth
        $this->testPaymentCreateAndSaveCardGlobal();

        $this->mockSession('capp_ksjdfkjsaf');

        $this->ba->publicAuth();

        $request = array(
            'url'     => '/apps/payments',
            'method'  => 'get',
            'content' => [
                'skip'  => 1
            ]);

        $payments = $this->makeRequestAndGetContent($request);

        // validations
        $this->assertEquals(empty($payments), false);

        $this->assertEquals($payments['entity'], 'collection');

        $this->assertEquals($payments['count'], 0);
    }

    protected function mockSession($appToken = 'capp_1000000custapp')
    {
        $data = [ 'test_app_token' => $appToken ];

        $this->session($data);
    }
}
