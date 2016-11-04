<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;
use RZP\Models\Card\Entity as Card;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Customer\Token\Entity as Token;

class SavedCardsPaymentCreateTest extends TestCase
{
    use InteractsWithSession;
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/SavedCardsPaymentTestData.php';

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

        $this->payment[Payment::CARD] = array('cvv'  => 111);

        $this->payment[Payment::TOKEN] = '10000cardtoken';

        $this->payment[Payment::CUSTOMER_ID] = 'cust_100000customer';

        // create and fetch payment
        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        // validations
        $this->assertEquals($payment[Payment::TOKEN_ID], 'token_100000custcard');

        $this->assertEquals($payment[Payment::GLOBAL_TOKEN_ID], null);

        $this->assertEquals($payment[Payment::CUSTOMER_ID], 'cust_100000customer');

        $this->assertEquals($payment[Payment::GLOBAL_CUSTOMER_ID], null);
    }

    /**
     * test card payment creation using a local saved card and token_id
     */
    public function testLocalSavedCardPaymentCreateWithTokenId()
    {
        // create payment data using token id
        $this->payment = $this->getDefaultPaymentArray();

        $this->payment[Payment::CARD] = array('cvv'  => 111);

        $this->payment[Payment::TOKEN] = 'token_100000custcard';

        $this->payment[Payment::CUSTOMER_ID] = 'cust_100000customer';

        // create and fetch payment
        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        // validations
        $this->assertEquals($payment[Payment::TOKEN_ID], 'token_100000custcard');

        $this->assertEquals($payment[Payment::GLOBAL_TOKEN_ID], null);

        $this->assertEquals($payment[Payment::CUSTOMER_ID], 'cust_100000customer');

        $this->assertEquals($payment[Payment::GLOBAL_CUSTOMER_ID], null);
    }

    /**
     * test emi payment creation using a local saved card
     */
    public function testLocalSavedCardEmiPaymentCreate()
    {
        // set emi payment data
        $this->fixtures->merchant->enableEmi();

        $this->payment = $this->getDefaultEmiPaymentArray(true);

        $this->payment[Payment::TOKEN] = '10000cardtoken';

        $this->payment[Payment::CUSTOMER_ID] = 'cust_100000customer';

        // create payment and fetch from db
        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        // validations
        $this->assertEquals($payment[Payment::TOKEN_ID], 'token_100000custcard');

        $this->assertEquals($payment[Payment::GLOBAL_TOKEN_ID], null);

        $this->assertEquals($payment[Payment::CUSTOMER_ID], 'cust_100000customer');

        $this->assertEquals($payment[Payment::GLOBAL_CUSTOMER_ID], null);
    }

    /**
     * test card payment creation using global saved card and token
     */
    public function testGlobalSavedCardPaymentCreate()
    {
        // create payment data
        $this->mockSession();

        $this->payment = $this->getDefaultPaymentArray();

        $this->payment[Payment::CARD] = ['cvv' => 111];

        $this->payment[Payment::TOKEN] = '1000gcardtoken';

        // create payment and fetch entities
        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $card = $this->getLastEntity('card', true);

        // vallidations
        $this->assertEquals($payment[Payment::GLOBAL_TOKEN_ID], '10000custgcard');

        $this->assertEquals($payment[Payment::APP_TOKEN], '1000000custapp');

        $this->assertEquals($card[Card::GLOBAL_CARD_ID], '100000000gcard');

        $this->assertEquals($payment[Payment::GLOBAL_CUSTOMER_ID], '10000gcustomer');
    }

    /**
     * test card payment creation using global saved card and token_id
     */
    public function testGlobalSavedCardPaymentCreateWithTokenId()
    {
        // create payment data
        $this->payment = $this->getDefaultPaymentArray();

        $this->payment[Payment::CARD] = ['cvv' => 111];

        $this->payment[Payment::TOKEN] = 'token_10000custgcard';

        $this->payment[Payment::APP_TOKEN] = 'capp_1000000custapp';

        // create payment and fetch entities
        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $card = $this->getLastEntity('card', true);

        // validations
        $this->assertEquals($payment[Payment::GLOBAL_TOKEN_ID], '10000custgcard');

        $this->assertEquals($payment[Payment::APP_TOKEN], '1000000custapp');

        $this->assertEquals($card[Card::GLOBAL_CARD_ID], '100000000gcard');

        $this->assertEquals($payment[Payment::GLOBAL_CUSTOMER_ID], '10000gcustomer');
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

        $this->payment[Payment::TOKEN] = '1000gcardtoken';

        // create payment and fetch entities
        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $card = $this->getLastEntity('card', true);

        // validations
        $this->assertEquals($payment[Payment::GLOBAL_TOKEN_ID], '10000custgcard');

        $this->assertEquals($payment[Payment::APP_TOKEN], '1000000custapp');

        $this->assertEquals($card[Card::GLOBAL_CARD_ID], '100000000gcard');

        $this->assertEquals($payment[Payment::GLOBAL_CUSTOMER_ID], '10000gcustomer');
    }

    /**
     * test card payment with save card local
     */
    public function testPaymentCreateAndSaveCardLocal()
    {
        // create payment data
        $this->payment = $this->getDefaultPaymentArray();

        $this->payment['save'] = 1;

        $this->payment[Payment::CARD]['number'] = '4000400000000004';

        $this->payment[Payment::CUSTOMER_ID] = 'cust_100000customer';

        // create payment and fetch entities
        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $card = $this->getLastEntity('card', true);

        $token = $this->getLastEntity('token', true);

        // validations
        $this->assertEquals($payment[Payment::CARD_ID], $card['id']);

        $this->assertEquals($payment[Payment::TOKEN_ID], $token['id']);

        $this->assertEquals('card_'.$token[Token::CARD_ID], $card['id']);

        $this->assertEquals($payment[Payment::CUSTOMER_ID], 'cust_100000customer');

        $this->assertEquals($payment[Payment::GLOBAL_CUSTOMER_ID], null);

        $this->assertEquals($token[Token::USED_COUNT], 1);

        $this->assertNotEquals($token[Token::USED_AT], null);

        // create payment data from newly saved tokens
        $this->payment[Payment::CARD] = array('cvv'  => 111);

        $this->payment[Payment::TOKEN] = $token[Payment::TOKEN];

        $this->payment[Payment::CUSTOMER_ID] = 'cust_100000customer';

        // create payment and fetch entities
        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $token = $this->getLastEntity('token', true);

        // validations
        $this->assertEquals($payment[Payment::TOKEN_ID], $token['id']);

        $this->assertEquals($payment[Payment::GLOBAL_TOKEN_ID], null);

        $this->assertEquals($payment[Payment::CUSTOMER_ID], 'cust_100000customer');

        $this->assertEquals($payment[Payment::GLOBAL_CUSTOMER_ID], null);

        $this->assertEquals($token[Token::USED_COUNT], 2);

        $this->assertNotEquals($token[Token::USED_AT], null);
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

        $this->payment[Payment::CUSTOMER_ID] = 'cust_100000customer';

        // create payment and fetch entities
        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $card = $this->getLastEntity('card', true);

        $token = $this->getLastEntity('token', true);

        // validations
        $this->assertEquals($payment[Payment::CARD_ID], $card['id']);

        $this->assertEquals($payment[Payment::TOKEN_ID], $token['id']);

        $this->assertEquals('card_'.$token[Token::CARD_ID], $card['id']);

        $this->assertEquals($payment[Payment::CUSTOMER_ID], 'cust_100000customer');

        $this->assertEquals($token[Token::USED_COUNT], 1);

        $this->assertNotEquals($token[Token::USED_AT], null);

        // create another payment using new saved token and fetch enttites
        $this->payment[Payment::CARD] = array('cvv'  => 111);

        $this->payment[Payment::TOKEN] = $token[Payment::TOKEN];

        $this->payment[Payment::CUSTOMER_ID] = 'cust_100000customer';

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $token = $this->getLastEntity('token', true);

        // validations
        $this->assertEquals($payment[Payment::TOKEN_ID], $token['id']);

        $this->assertEquals($payment[Payment::GLOBAL_TOKEN_ID], null);

        $this->assertEquals($payment[Payment::CUSTOMER_ID], 'cust_100000customer');

        $this->assertEquals($payment[Payment::GLOBAL_CUSTOMER_ID], null);

        $this->assertEquals($token[Token::USED_COUNT], 2);

        $this->assertNotEquals($token[Token::USED_AT], null);
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

        $this->payment[Payment::CARD]['number'] = '4000400000000004';

        // create payment and fetch entities
        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $card = $this->getLastEntity('card', true);

        $token = $this->getLastEntity('token', true);

        // validations
        $this->assertEquals($payment[Payment::CARD_ID], $card['id']);

        $this->assertNotEquals('card_'.$token[Token::CARD_ID], $card['id']);

        $this->assertEquals($card[Card::GLOBAL_CARD_ID], $token[Token::CARD_ID]);

        $this->assertEquals($payment[Payment::APP_TOKEN], '1000000custapp');

        $this->assertEquals('token_'.$payment[Payment::GLOBAL_TOKEN_ID], $token['id']);

        $this->assertEquals($token[Token::USED_COUNT], 1);

        $this->assertNotEquals($token[Token::USED_AT], null);

        // create another payment with new token and fetch entities
        $this->payment[Payment::CARD] = array('cvv'  => 111);

        $this->payment[Payment::TOKEN] = $token[Payment::TOKEN];

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $card = $this->getLastEntity('card', true);

        $token = $this->getLastEntity('token', true);

        // validations
        $this->assertEquals('token_'.$payment[Payment::GLOBAL_TOKEN_ID], $token['id']);

        $this->assertEquals($payment[Payment::APP_TOKEN], '1000000custapp');

        $this->assertEquals($card[Card::GLOBAL_CARD_ID], $token[Token::CARD_ID]);

        $this->assertEquals($payment[Payment::GLOBAL_CUSTOMER_ID], '10000gcustomer');

        $this->assertEquals($token[Token::USED_COUNT], 2);

        $this->assertNotEquals($token[Token::USED_AT], null);
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
        $this->assertEquals($payment[Payment::CARD_ID], $card['id']);

        $this->assertNotEquals('card_'.$token[Token::CARD_ID], $card['id']);

        $this->assertEquals($card[Card::GLOBAL_CARD_ID], $token[Token::CARD_ID]);

        $this->assertEquals($payment[Payment::APP_TOKEN], '1000000custapp');

        $this->assertEquals('token_'.$payment[Payment::GLOBAL_TOKEN_ID], $token['id']);

        $this->assertEquals($token[Token::USED_COUNT], 1);

        $this->assertNotEquals($token[Token::USED_AT], null);

        // create another payment using new token and fetch entities
        $this->payment[Payment::TOKEN] = $token[Payment::TOKEN];

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $card = $this->getLastEntity('card', true);

        $token = $this->getLastEntity('token', true);

        // validations
        $this->assertEquals('token_'.$payment[Payment::GLOBAL_TOKEN_ID], $token['id']);

        $this->assertEquals($payment[Payment::APP_TOKEN], '1000000custapp');

        $this->assertEquals($card[Card::GLOBAL_CARD_ID], $token[Token::CARD_ID]);

        $this->assertEquals($payment[Payment::GLOBAL_CUSTOMER_ID], '10000gcustomer');

        $this->assertEquals($token[Token::USED_COUNT], 2);

        $this->assertNotEquals($token[Token::USED_AT], null);
    }

    /**
     * test card multiple payments with save card local, only one card should be saved
     */
    public function testMultiplePaymentsCreateAndSaveCardLocal()
    {
        // create payment data
        $this->payment = $this->getDefaultPaymentArray();

        $this->payment['save'] = 1;

        $this->payment[Payment::CARD]['number'] = '4000400000000004';

        $this->payment[Payment::CARD]['expiry_year'] = '20';

        $this->payment[Payment::CUSTOMER_ID] = 'cust_100000customer';

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
     * test card payment creation using a local saved card and token without cvv
     */
    public function testLocalSavedCardPaymentCreateNoCvv()
    {
        // set payment data using token
        $this->payment = $this->getDefaultPaymentArray();

        $this->payment[Payment::CARD] = [];

        $this->payment[Payment::TOKEN] = '10000cardtoken';

        $this->payment[Payment::CUSTOMER_ID] = 'cust_100000customer';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function()
        {
            $this->doAuthPayment($this->payment);
        });
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

    /**
     * test card multiple payments with save card local, only one card should be saved
     */
    public function testPaymentsInvalidApp()
    {
        // create payments and fetch on public auth
        $this->testPaymentCreateAndSaveCardGlobal();

        $this->mockSession('capp_ksjdfkjsaf');

        $this->ba->publicAuth();

        $data = [
            'request' => [
                'url' => '/preferences',
                'method' => 'get',
            ],
            'response' => [
                'content' => [
                    'http_status_code' => 200,
                    'version' => 1
                ],
            ],
        ];

        $this->fixtures->merchant->addFeatures(['cardsaving']);

        $this->runRequestResponseFlow($data);

    }

    protected function mockSession($appToken = 'capp_1000000custapp')
    {
        $data = [ 'test_app_token' => $appToken ];

        $this->session($data);
    }
}
