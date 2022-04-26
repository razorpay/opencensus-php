<?php

namespace RZP\Tests\Functional\Payment;

use Mail;
use Mockery;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Jobs\CardVaultMigrationJob;
use RZP\Mail\Payment\CardSaved as CardSavedMail;
use RZP\Models\Merchant\FeeBearer;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;
use RZP\Models\Card\Entity as Card;
use RZP\Models\Card\Vault;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Customer\Token\Entity as Token;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class SavedCardsPaymentCreateTest extends TestCase
{
    use InteractsWithSession;
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/SavedCardsPaymentTestData.php';

        parent::setUp();

        $this->ba->publicAuth();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->emiPlan = $this->fixtures->create('emi_plan:default_emi_plans');

        $this->mockCardVault();
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

        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        // validations
        $this->assertEquals($payment[Payment::TOKEN_ID], 'token_100000custcard');

        $this->assertEquals($payment[Payment::GLOBAL_TOKEN_ID], null);

        $this->assertEquals($payment[Payment::CUSTOMER_ID], 'cust_100000customer');

        $this->assertEquals($payment[Payment::GLOBAL_CUSTOMER_ID], null);
    }

    /**
     * test card payment creation using a local saved card and token
     */
    public function testLocalSavedCardPaymentCreateWithoutMethodAndWithTokenId()
    {
        // set payment data using token
        $this->payment = $this->getDefaultPaymentArray();

        $this->payment[Payment::CARD] = array('cvv'  => 111);

        $this->payment[Payment::TOKEN] = 'token_100000custcard';

        $this->payment[Payment::CUSTOMER_ID] = 'cust_100000customer';

        unset($this->payment['method']);

        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        // validations
        $this->assertEquals($payment[Payment::TOKEN_ID], 'token_100000custcard');

        $this->assertEquals($payment[Payment::GLOBAL_TOKEN_ID], null);

        $this->assertEquals($payment[Payment::CUSTOMER_ID], 'cust_100000customer');

        $this->assertEquals($payment[Payment::GLOBAL_CUSTOMER_ID], null);
    }

    /**
     * test card payment creation using a local saved card and token
     */
    public function testLocalSavedCardPaymentCreateWithoutMethodAndWithToken()
    {
        // set payment data using token
        $this->payment = $this->getDefaultPaymentArray();

        $this->payment[Payment::CARD] = array('cvv'  => 111);

        $this->payment[Payment::TOKEN] = '10000cardtoken';

        $this->payment[Payment::CUSTOMER_ID] = 'cust_100000customer';

        unset($this->payment['method']);

        $this->doAuthAndCapturePayment($this->payment);

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

        $this->payment[Payment::CARD] = ['cvv'  => 111];

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

        // validations
        $this->assertEquals($payment[Payment::GLOBAL_TOKEN_ID], '10000custgcard');

        $this->assertEquals($payment[Payment::APP_TOKEN], '1000000custapp');

        $this->assertEquals($card[Card::GLOBAL_CARD_ID], '100000000gcard');

        $this->assertEquals($payment[Payment::GLOBAL_CUSTOMER_ID], '10000gcustomer');

        //assert card expiry month , year and last 4
        $this->assertEquals('2100', $card['expiry_year']);
        $this->assertEquals('12',   $card['expiry_month']);
        $this->assertEquals('1111', $card['last4']);
        $this->assertNull($card['token_expiry_year']);
        $this->assertNull(  $card['token_expiry_month']);
        $this->assertNull($card['token_last4']);

    }

    public function testGlobalSavedCardWithCookieDisabled()
    {
        $this->ba->publicAuth();

        $this->mockRaven();
        $this->withSession(['test_checkcookie' => '0']);

        // send OTP
        $response = $this->sendOtp('9988776655');

        // verify OTP
        $content = $this->verifyOtp('9988776655', 'abc@razorpay.com', '233443', '123');

        $this->assertArrayHasKey('session_id', $content);

        $this->flushSession();
        $this->app['session']->regenerate();

        $payment = $this->getDefaultPaymentArray();
        $payment[Payment::CARD] = ['cvv' => 111];
        $payment[Payment::TOKEN] = '1000gcardtoken';

        $headers = [
            'HTTP_X_RAZORPAY_SESSIONID' => $content['session_id'],
        ];

        $response = $this->doAuthPayment($payment, $headers);

        $this->assertArrayHasKey('razorpay_payment_id', $response);
    }

    public function testGlobalSavedCardCookieDisabledWithInvalidData()
    {
        $this->ba->publicAuth();

        $this->mockRaven();
        $this->withSession(['test_checkcookie' => '0']);

        // send OTP
        $response = $this->sendOtp('9988776655');

        // verify OTP
        $content = $this->verifyOtp('9988776655', 'abc@razorpay.com', '233443', '123');

        $this->assertArrayHasKey('session_id', $content);

        $this->flushSession();
        $this->withSession(['test_checkcookie' => '0']);

        $payment = $this->getDefaultPaymentArray();
        $payment[Payment::CARD] = ['cvv' => 111];
        $payment[Payment::TOKEN] = '1000gcardtoken';

        $headers = [
            'HTTP_X_RAZORPAY_SESSIONID' => $content['session_id'],
        ];

        $store = \Cache::store();

        \Cache::shouldReceive('store')->andReturn($store);

        \Cache::shouldReceive('get')
                ->once()
                ->with('temp_session:' . $content['session_id'])
                ->andReturn([]);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment, $headers)
        {
            $this->doAuthPayment($payment, $headers);
        });
    }

    public function testGlobalSavedCardPaymentCreateWithoutMethod()
    {
        $this->mockSession();

        $this->payment = $this->getDefaultPaymentArray();

        $this->payment[Payment::CARD] = ['cvv' => 111];

        $this->payment[Payment::TOKEN] = '1000gcardtoken';

        unset($this->payment[Payment::METHOD]);

        $this->doAuthAndCapturePayment($this->payment);
    }

    /**
     * test card payment creation using global saved card and token_id
     */
    public function testGlobalSavedCardPaymentCreateWithTokenId()
    {
        $this->mockSession();

        // create payment data
        $this->payment = $this->getDefaultPaymentArray();

        $this->payment[Payment::CARD] = ['cvv' => 111];

        $this->payment[Payment::TOKEN] = 'token_10000custgcard';

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
        $card =  $this->getLastEntity('card', true);


        $this->assertEquals('2024', $card['expiry_year']);
        $this->assertEquals('12', $card['expiry_month']);
        $this->assertEquals('0004', $card['last4']);
        $this->assertNull($card['token_expiry_year']);
        $this->assertNull($card['token_expiry_month']);

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
        Mail::fake();

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


        $this->assertEquals('2024', $card['expiry_year']);
        $this->assertEquals('12', $card['expiry_month']);
        $this->assertEquals('0004', $card['last4']);


        // create another payment with new token and fetch entities
        $this->payment[Payment::CARD] = array('cvv'  => 111);

        $this->payment[Payment::TOKEN] = $token[Payment::TOKEN];

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $card = $this->getLastEntity('card', true);

        $token = $this->getLastEntity('token', true);


        $this->assertEquals('2024', $card['expiry_year']);
        $this->assertEquals('12', $card['expiry_month']);
        $this->assertEquals('0004', $card['last4']);
        $this->assertNull($card['token_expiry_year']);
        $this->assertNull($card['token_expiry_month']);
        // validations
        $this->assertEquals('token_'.$payment[Payment::GLOBAL_TOKEN_ID], $token['id']);

        $this->assertEquals($payment[Payment::APP_TOKEN], '1000000custapp');

        $this->assertEquals($card[Card::GLOBAL_CARD_ID], $token[Token::CARD_ID]);

        $this->assertEquals($payment[Payment::GLOBAL_CUSTOMER_ID], '10000gcustomer');

        $this->assertEquals($token[Token::USED_COUNT], 2);

        $this->assertNotEquals($token[Token::USED_AT], null);

        Mail::assertQueued(CardSavedMail::class);
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
        $this->markTestSkipped(
            'Not using existing card now, new card entity will be created.'
        );

        // create payment data
        $this->payment = $this->getDefaultPaymentArray();

        $this->payment['save'] = 1;

        $this->payment[Payment::CARD]['number'] = '4000400000000004';

        $this->payment[Payment::CARD]['expiry_year'] = '25';

        $this->payment[Payment::CUSTOMER_ID] = 'cust_100000customer';

        // create payment 1
        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment1 = $this->getLastEntity('payment', true);

        // create payment 2
        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment2 = $this->getLastEntity('payment', true);

        $this->assertEquals($payment1['card_id'], $payment2['card_id']);
    }

    public function testCardVaultStripSpacesCheck()
    {
        $this->mockCardVaultStripSpaces();

        $this->payment = $this->getDefaultPaymentArray();

        $this->payment['save'] = 1;

        $this->payment[Payment::CARD]['number'] = '40004 000 0000 0004';

        $this->payment[Payment::CARD]['expiry_year'] = '25';

        $this->payment[Payment::CUSTOMER_ID] = 'cust_100000customer';

        // create payment 1
        $content = $this->doAuthAndCapturePayment($this->payment);
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

        $this->mockSession('capp_ad32ksjdfkjsaf');

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
                'content' => [
                    'currency' => 'INR'
                ]
            ],
            'response' => [
                'content' => [
                    // 'http_status_code' => 200,
                    'version' => 1
                ],
            ],
        ];

        $this->fixtures->merchant->addFeatures(['cardsaving']);

        $this->runRequestResponseFlow($data);

    }

    public function testCreateCardVaultToken()
    {
        // create payment data
        $this->payment = $this->getDefaultPaymentArray();

        $this->payment['save'] = 1;

        $this->payment[Payment::CARD]['number'] = '4000400000000004';

        $this->payment[Payment::CARD]['expiry_year'] = '25';

        $this->payment[Payment::CUSTOMER_ID] = 'cust_100000customer';

        // create payment 1
        $content = $this->doAuthAndCapturePayment($this->payment);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals(Vault::RZP_VAULT, $card['vault']);
    }

    public function testCardVaultMigrationJobAuthorize()
    {
        $cardVault = Mockery::mock('RZP\Services\CardVault')->makePartial();

        $this->app->instance('card.cardVault', $cardVault);

        $this->count = 0;

        $cardVault->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', Mockery::type('array'))
            ->andReturnUsing(function ($route, $method, $input)
            {
                $response = [
                    'error' => '',
                    'success' => true,
                ];

                switch ($route)
                {
                    case 'tokenize':
                        $this->count += 1;
                        $this->assertEquals('4000400000000004', $input['secret']);
                        $response['token'] = base64_encode($input['secret']);
                        $response['fingerprint'] = '';
                        $response['scheme'] = '1';

                        break;

                    case 'detokenize':
                        $this->assertEquals('NDAwMDQwMDAwMDAwMDAwNA==', $input['secret']);
                        $response['value'] = base64_decode($input['token']);
                        break;

                    case 'validate':
                        if ($input['token'] === 'fail')
                        {
                            $response['success'] = false;
                        }
                        break;

                    case 'token':
                        $this->assertEquals('NDAwMDQwMDAwMDAwMDAwNA==', $input['token']);
                        break;

                    case 'token/delete':
                        $this->assertEquals('NDAwMDQwMDAwMDAwMDAwNA==', $input['token']);
                        break;

                    case 'token/migrate':
                        $this->assertEquals('NDAwMDQwMDAwMDAwMDAwNA==', $input['token']);
                        $response['token'] = strrev($input['token']);
                        $response['fingerprint'] = strrev($input['token']);
                    break;
                }
                return $response;
            });

        $this->app->instance('card.cardVault', $cardVault);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setMethods(['getTreatment', 'getCachedTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn('on');

        $paymentInput = $this->getDefaultPaymentArray();
        $paymentInput['save'] = 1;
        $paymentInput[Payment::CUSTOMER_ID] = 'cust_100000customer';
        $paymentInput[Payment::CARD]['number'] = '4000400000000004';

        $this->doAuthPayment($paymentInput);

        $payment = $this->getLastEntity('payment', true);
        $card    = $this->getDbLastEntity('card');
        $firstToken  = $this->getDbLastEntity('token');

        $this->assertEquals('rzpvault', $card['vault']);

        $this->doAuthPayment($paymentInput);

        $payment = $this->getLastEntity('payment', true);
        $card    = $this->getDbLastEntity('card');
        $lastToken  = $this->getDbLastEntity('token');


        $this->assertEquals('==ANwADMwADMwADMwQDMwADN', $card->getVaultToken());
        $this->assertEquals('==ANwADMwADMwADMwQDMwADN', $card->getGlobalFingerPrint());

        $this->assertEquals('rzpvault', $card['vault']);

        $this->assertEquals('token_' . $firstToken['id'], $payment['token_id']);

        $this->assertLessThan(Carbon::now()->getTimestamp(), $lastToken['expired_at'] - 10);
    }

    public function testCardVaultMigrationJobAuthorizeSaveCardGlobal()
    {
        $cardVault = Mockery::mock('RZP\Services\CardVault')->makePartial();

        $this->app->instance('card.cardVault', $cardVault);

        $cardVault->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', Mockery::type('array'))
            ->andReturnUsing(function ($route, $method, $input)
            {
                $response = [
                    'error' => '',
                    'success' => true,
                ];

                switch ($route)
                {
                    case 'tokenize':
                        $this->assertEquals('4000400000000004', $input['secret']);
                        $response['token'] = base64_encode($input['secret']);
                        $response['fingerprint'] = "";
                        $response['scheme'] = "1";

                        break;

                    case 'detokenize':
                        $this->assertEquals('NDAwMDQwMDAwMDAwMDAwNA==', $input['secret']);
                        $response['value'] = base64_decode($input['token']);
                        break;

                    case 'validate':
                        if ($input['token'] === 'fail')
                        {
                            $response['success'] = false;
                        }
                        break;

                    case 'delete':
                        break;

                    case 'token/delete':
                        $this->assertEquals('NDAwMDQwMDAwMDAwMDAwNA==', $input['token']);
                        break;

                    case 'token/migrate':
                        $this->assertEquals('NDAwMDQwMDAwMDAwMDAwNA==', $input['token']);
                        $response['token'] = strrev($input['token']);
                        $response['fingerprint'] = strrev($input['token']);
                    break;
                }
                return $response;
            });

        $this->app->instance('card.cardVault', $cardVault);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setMethods(['getTreatment', 'getCachedTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn('on');

        $this->makeSaveCardGlobalPayment();

        $payment = $this->getLastEntity('payment', true);
        $card    = $this->getDbLastEntity('card');

        $firstToken  = $this->getDbLastEntity('token');

        $this->assertEquals('rzpvault', $card['vault']);

        $this->makeSaveCardGlobalPayment();

        $payment = $this->getLastEntity('payment', true);
        $card    = $this->getDbLastEntity('card');
        $lastToken  = $this->getDbLastEntity('token');

        $this->assertEquals('==ANwADMwADMwADMwQDMwADN', $card->getVaultToken());
        $this->assertEquals('==ANwADMwADMwADMwQDMwADN', $card->getGlobalFingerPrint());

        $this->assertEquals('rzpvault', $card['vault']);

        $this->assertEquals($firstToken['id'], $payment['global_token_id']);

        $this->assertLessThan(Carbon::now()->getTimestamp(), $lastToken['expired_at'] - 10);
    }

    public function testCardVaultMigrationJobCapture()
    {
        $cardVault = Mockery::mock('RZP\Services\CardVault')->makePartial();

        $this->app->instance('card.cardVault', $cardVault);

        $cardVault->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', Mockery::type('array'))
            ->andReturnUsing(function ($route, $method, $input)
            {
                $response = [
                    'error' => '',
                    'success' => true,
                ];

                switch ($route)
                {
                    case 'tokenize':
                        $this->assertEquals('4000400000000004', $input['secret']);
                        $response['token'] = base64_encode($input['secret']);
                        $response['fingerprint'] = "";
                        $response['scheme'] = "1";

                        break;

                    case 'detokenize':
                        $this->assertEquals('NDAwMDQwMDAwMDAwMDAwNA==', $input['secret']);
                        $response['value'] = base64_decode($input['token']);
                        break;

                    case 'validate':
                        if ($input['token'] === 'fail')
                        {
                            $response['success'] = false;
                        }
                        break;

                    case 'delete':
                        break;

                    case 'token/delete':
                        $this->assertEquals('NDAwMDQwMDAwMDAwMDAwNA==', $input['token']);
                        break;

                    case 'token/migrate':
                        $this->assertEquals('NDAwMDQwMDAwMDAwMDAwNA==', $input['token']);
                        $response['token'] = strrev($input['token']);
                        $response['fingerprint'] = strrev($input['token']);
                    break;
                }
                return $response;
            });

        $this->app->instance('card.cardVault', $cardVault);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setMethods(['getTreatment', 'getCachedTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn('on');

        $payment = $this->getDefaultPaymentArray();
        $payment['save'] = 1;
        $payment[Payment::CUSTOMER_ID] = 'cust_100000customer';
        $payment[Payment::CARD]['number'] = '4000400000000004';

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);
        $card    = $this->getDbLastEntity('card');

        $token = $card->getVaultToken();

        $this->assertEquals('==ANwADMwADMwADMwQDMwADN', $card->getVaultToken());
        $this->assertEquals('==ANwADMwADMwADMwQDMwADN', $card->getGlobalFingerPrint());

        $this->assertEquals('rzpvault', $card['vault']);
    }

    public function testCardVaultMigrationJobCron()
    {
        $this->fixtures->merchant->addFeatures(['s2s']);

        $cardVault = Mockery::mock('RZP\Services\CardVault')->makePartial();

        $this->app->instance('card.cardVault', $cardVault);

        $cardVault->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', Mockery::type('array'))
            ->andReturnUsing(function ($route, $method, $input)
            {
                $response = [
                    'error' => '',
                    'success' => true,
                ];

                switch ($route)
                {
                    case 'tokenize':
                        $this->assertEquals('4000400000000004', $input['secret']);
                        $response['token'] = base64_encode($input['secret']);
                        $response['fingerprint'] = "";
                        $response['scheme'] = "1";

                        break;

                    case 'detokenize':
                        $this->assertEquals('NDAwMDQwMDAwMDAwMDAwNA==', $input['token']);
                        $response['value'] = base64_decode($input['token']);
                        break;

                    case 'validate':
                        if ($input['token'] === 'fail')
                        {
                            $response['success'] = false;
                        }
                        break;

                    case 'delete':
                        break;

                    case 'token/delete':
                        $this->assertEquals('NDAwMDQwMDAwMDAwMDAwNA==', $input['token']);
                        break;

                    case 'token/migrate':
                        $this->assertEquals('NDAwMDQwMDAwMDAwMDAwNA==', $input['token']);
                        $response['token'] = strrev($input['token']);
                        $response['fingerprint'] = strrev($input['token']);
                    break;
                }
                return $response;
            });

        $this->app->instance('card.cardVault', $cardVault);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setMethods(['getTreatment', 'getCachedTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn('on');

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4000400000000004';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/redirect',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $payment = $this->getLastEntity('payment', true);

        $card    = $this->getDbLastEntity('card');

        $this->assertEquals('created', $payment['status']);

        $this->assertEquals('rzpencryption', $card['vault']);

        $card2 = $this->fixtures->create('card', [
                    'vault'       => 'rzpencryption',
                    'vault_token' => 'NDAwMDQwMDAwMDAwMDAwNA==',
                    'created_at'  => Carbon::now()->getTimestamp() - 3600,
                ]);

        $this->fixtures->base->editEntity('payment', $payment['id'], ['created_at' => Carbon::now()->getTimestamp() - 3600]);

        $this->ba->cronAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/cards',
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('1', $response['payments_count']);
        $this->assertEquals('1', $response['cards_count']);

        $card = $this->getDbLastEntity('card');
        $card2 = $this->getDbEntityById('card', $card2->getId());

        $this->assertEquals('==ANwADMwADMwADMwQDMwADN', $card->getVaultToken());
        $this->assertEquals('==ANwADMwADMwADMwQDMwADN', $card->getGlobalFingerPrint());

        $this->assertEquals('rzpvault', $card['vault']);

        $this->assertEquals('==ANwADMwADMwADMwQDMwADN', $card2->getVaultToken());
        $this->assertEquals('==ANwADMwADMwADMwQDMwADN', $card2->getGlobalFingerPrint());

        $this->assertEquals('rzpvault', $card2['vault']);
    }

    public function testMissingFingerprintCardVaultMigration()
    {
        $cardVault = Mockery::mock('RZP\Services\CardVault')->makePartial();

        $this->app->instance('card.cardVault', $cardVault);

        $cardVault->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', Mockery::type('array'))
            ->andReturnUsing(function ($route, $method, $input)
            {
                $response = [
                    'error' => '',
                    'success' => true,
                ];

                switch ($route)
                {
                    case 'tokenize':
                        $this->assertEquals('4000400000000004', $input['secret']);
                        $response['token'] = base64_encode($input['secret']);
                        $response['fingerprint'] = "";
                        $response['scheme'] = "1";

                        break;

                    case 'detokenize':
                        $this->assertEquals('NDAwMDQwMDAwMDAwMDAwNA==', $input['token']);
                        $response['value'] = base64_decode($input['token']);
                        break;

                    case 'validate':
                        if ($input['token'] === 'fail')
                        {
                            $response['success'] = false;
                        }
                        break;

                    case 'delete':
                        break;

                    case 'token/delete':
                        $this->assertEquals('NDAwMDQwMDAwMDAwMDAwNA==', $input['token']);
                        break;

                    case 'token/migrate':
                        $this->assertEquals('NDAwMDQwMDAwMDAwMDAwNA==', $input['token']);
                        $response['token'] = strrev($input['token']);
                        $response['fingerprint'] = strrev($input['token']);
                        break;
                }
                return $response;
            });

        $this->app->instance('card.cardVault', $cardVault);

        $created_at = $this->app['cache']->get('fingerprint_migration', 1546300800);

        $card = $this->fixtures->create('card', [
            'vault'       => 'rzpvault',
            'vault_token' => 'NDAwMDQwMDAwMDAwMDAwNA==',
            'created_at'  => $created_at + 3600,
        ]);

        $this->ba->cronAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/cards',
            'content' => [
                'limit' => 1,
                'migrate_missing_fingerprint_cards' => true,
            ],
        ];

        $response = $this->makeRequestAndGetContent($request);

        $card = $this->getDbEntityById('card', $card->getId());

        $this->assertEquals('0', $response['payments_count']);
        $this->assertEquals('1', $response['cards_count']);
        $this->assertNotEmpty($card->getGlobalFingerPrint());
    }

    public function testProcessFeesTransaction()
    {
        $this->markTestSkipped(
            'Not using existing card now, new card entity will be created.'
        );

        $this->fixtures->merchant->enableConvenienceFeeModel();

        $this->fixtures->merchant->addFeatures(['s2s']);

        $cardVault = Mockery::mock('RZP\Services\CardVault')->makePartial();

        $this->app->instance('card.cardVault', $cardVault);

        $this->fixtures->pricing->editDefaultPlan(['fee_bearer' => FeeBearer::CUSTOMER]);

        $cardVault->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', Mockery::type('array'))
            ->andReturnUsing(function ($route, $method, $input)
            {
                $response = [
                    'error' => '',
                    'success' => true,
                ];

                switch ($route)
                {
                    case 'tokenize':
                        $this->assertEquals('4000400000000004', $input['secret']);
                        $response['token'] = base64_encode($input['secret']);
                        $response['fingerprint'] = "";
                        $response['scheme'] = "1";

                        break;

                    case 'detokenize':
                        $this->assertEquals('NDAwMDQwMDAwMDAwMDAwNA==', $input['token']);
                        $response['value'] = base64_decode($input['token']);
                        break;

                    case 'validate':
                        if ($input['token'] === 'fail')
                        {
                            $response['success'] = false;
                        }
                        break;

                    case 'delete':
                        break;

                    case 'token/delete':
                        $this->assertEquals('NDAwMDQwMDAwMDAwMDAwNA==', $input['token']);
                        break;

                    case 'token/migrate':
                        $this->assertEquals('NDAwMDQwMDAwMDAwMDAwNA==', $input['token']);
                        $response['token'] = strrev($input['token']);
                        $response['fingerprint'] = strrev($input['token']);
                    break;
                }
                return $response;
            });

        $this->app->instance('card.cardVault', $cardVault);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setMethods(['getTreatment', 'getCachedTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn('on');

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4000400000000004';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/redirect',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestParent($request);

        $card    = $this->getDbLastEntity('card');

        $this->assertEquals('10000000rucard', $card->getId());
    }

    /**
     * test payment and save new card entity on every request
     */
    public function testPaymentNewCardCreation()
    {
        $this->mockSession();

        $this->payment = $this->getDefaultPaymentArray();

        $this->doAuthPaymentViaAjaxRoute($this->payment);
        $card1 = $this->getLastEntity('card', true);

        $this->doAuthPaymentViaAjaxRoute($this->payment);
        $card2 = $this->getLastEntity('card', true);

        $this->assertNotEquals($card1['id'], $card2['id']);
    }

    public function testPaymentWithNewCardAndUserConsentTokenisation()
    {
        $this->mockSession();

        $this->payment = $this->getDefaultPaymentArray();

        $this->payment['save'] = 1;

        $this->payment['_']['library'] = 'checkoutjs';

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $token = $this->getLastEntity('token', true);

        $this->assertNotNull($token['acknowledged_at']);
    }

    public function testPaymentWithSavedCardAndUserConsentTokenisation()
    {
        $this->mockSession();

        $this->payment = $this->getDefaultPaymentArray();

        $token = $this->getEntityById('token', '10000custgcard', true);

        $this->payment[Payment::CARD] = array('cvv'  => 111);

        $this->payment[Payment::TOKEN] = $token[Payment::TOKEN];

        $this->payment['user_consent_for_tokenisation'] = 1;

        $this->payment['_']['library'] = 'checkoutjs';

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $token = $this->getEntityById('token', '10000custgcard', true);

        $this->assertNotNull($token['acknowledged_at']);
    }

    public function testPaymentWithLocalSavedCardAndUserConsentTokenisation()
    {
        $this->payment = $this->getDefaultPaymentArray();

        $this->payment[Payment::CARD] = [];

        $this->payment[Payment::TOKEN] = '10000cardtoken';

        $this->payment[Payment::CARD] = array('cvv'  => 111);

        $this->payment[Payment::CUSTOMER_ID] = 'cust_100000customer';

        $this->payment['user_consent_for_tokenisation'] = 1;

        $this->payment['_']['library'] = 'checkoutjs';

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $token = $this->getEntityById('token', '100000custcard', true);

        $this->assertNotNull($token['acknowledged_at']);
    }

    public function testPaymentWithLocalNewCardAndUserConsentTokenisation()
    {
        $this->payment = $this->getDefaultPaymentArray();

        $this->payment[Payment::CUSTOMER_ID] = 'cust_100000customer';

        $this->payment['save'] = 1;

        $this->payment['_']['library'] = 'checkoutjs';

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $token = $this->getLastEntity('token', true);

        $this->assertNotNull($token['acknowledged_at']);
    }

    public function testPaymentWithUserConsentTokenisationWithInvalidLibrary()
    {
        $this->mockSession();

        $this->payment = $this->getDefaultPaymentArray();

        $this->payment['save'] = 1;

        $this->payment['_']['library'] = 'direct';

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $token = $this->getLastEntity('token', true);

        $this->assertNull($token['acknowledged_at']);
    }

    public function testPaymentWithInvalidUserConsentTokenisation()
    {
        $this->mockSession();

        $this->payment = $this->getDefaultPaymentArray();

        $this->payment['save'] = 1;

        $this->payment['user_consent_for_tokenisation'] = 2;

        $this->expectException('RZP\Exception\BadRequestValidationFailureException');

        $this->expectExceptionMessage('The selected user consent for tokenisation is invalid.');

        $this->doAuthPaymentViaAjaxRoute($this->payment);
    }

    public function testVerifyOtpResponseWithoutUserConsentTokenisation()
    {
        $this->ba->publicAuth();

        $this->withSession(['test_checkcookie' => '0']);

        $content = $this->verifyOtp('9988776655', 'abc@razorpay.com', '233443', '123');

        $this->assertArrayHasKey('tokens', $content);

        $this->assertArrayHasKey('items', $content['tokens']);

        $this->assertArrayHasKey('card', $content['tokens']['items'][0]);

        $this->assertArrayHasKey('consent_taken', $content['tokens']['items'][0]);

        $this->assertEquals(false, $content['tokens']['items'][0]['consent_taken']);
    }

    public function testVerifyOtpResponseWithUserConsentTokenisation()
    {
        $this->ba->publicAuth();

        $this->withSession(['test_checkcookie' => '0']);

        $this->fixtures->edit('token', '10000custgcard', ['acknowledged_at' => Carbon::now()->timestamp]);

        $content = $this->verifyOtp('9988776655', 'abc@razorpay.com', '233443', '123');

        $this->assertArrayHasKey('tokens', $content);

        $this->assertArrayHasKey('items', $content['tokens']);

        $this->assertArrayHasKey('card', $content['tokens']['items'][0]);

        $this->assertArrayHasKey('consent_taken', $content['tokens']['items'][0]);

        $this->assertEquals(true, $content['tokens']['items'][0]['consent_taken']);
    }

    public function testS2SPaymentCreateAndSaveCardWithoutCustomer()
    {
        $this->ba->privateAuth();

        $this->mockCardVault();

        $payment = $this->getDefaultPaymentArray();

        $payment['save'] = 1;

        $this->fixtures->merchant->addFeatures(['s2s']);

        $response = $this->doS2SPrivateAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getLastEntity('payment', true);

        $card = $this->getLastEntity('card', true);

        $token = $this->getLastEntity('token', true);

        $this->assertEquals($payment['id'], $response['razorpay_payment_id']);

        $this->assertEquals('authorized', $payment['status']);

        $this->assertTrue($this->redirectToAuthorize);

        // validations
        $this->assertEquals($payment[Payment::CARD_ID], $card['id']);

        $this->assertTrue($payment['save']);

        $this->assertEquals('card_'.$token[Token::CARD_ID], $card['id']);

        $this->assertNull($payment[Payment::GLOBAL_TOKEN_ID]);

        $this->assertEquals($token[Token::USED_COUNT], 1);

        $this->assertNotEquals($token[Token::USED_AT], null);

        // create another payment with new token and fetch entities

        $this->payment = $this->getDefaultPaymentArray();
        unset($this->payment[Payment::BANK]);
        unset($this->payment[Payment::NOTES]);
        $this->payment[Payment::CARD] = ['cvv' => 111];
        $this->payment[Payment::TOKEN] = $token[Payment::TOKEN];
        $content = $this->doS2SPrivateAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $card = $this->getLastEntity('card', true);

        $token = $this->getLastEntity('token', true);

        // validations
        $this->assertArrayHasKey('razorpay_payment_id', $content);

        $this->assertEquals($payment[Payment::TOKEN_ID], $token['id']);

        $this->assertFalse($payment['save']);

        $this->assertEquals($card[Card::GLOBAL_CARD_ID], null);

        $this->assertEquals($token[Token::USED_COUNT], 2);

        $this->assertNotEquals($token[Token::USED_AT], null);

    }

    public function testCreateAndSaveCardTwiceWithoutCustomer()
    {
        $this->ba->privateAuth();

        $this->mockCardVault();

        $payment = $this->getDefaultPaymentArray();

        $payment['save'] = 1;

        $this->fixtures->merchant->addFeatures(['s2s']);

        $response = $this->doS2SPrivateAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getLastEntity('payment', true);

        $card = $this->getLastEntity('card', true);

        $token1 = $this->getLastEntity('token', true);

        $this->assertEquals($payment['id'], $response['razorpay_payment_id']);

        $this->assertEquals('authorized', $payment['status']);

        $this->assertTrue($this->redirectToAuthorize);

        // validations
        $this->assertEquals($payment[Payment::CARD_ID], $card['id']);

        $this->assertTrue($payment['save']);

        $this->assertEquals('card_'.$token1[Token::CARD_ID], $card['id']);

        $this->assertNull($payment[Payment::GLOBAL_TOKEN_ID]);

        $this->assertEquals($token1[Token::USED_COUNT], 1);

        $this->assertNotEquals($token1[Token::USED_AT], null);

        // create another payment with new token and fetch entities

        $this->payment = $this->getDefaultPaymentArray();

        $this->payment['save'] = 1;

        $content = $this->doS2SPrivateAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $card = $this->getLastEntity('card', true);

        $token2 = $this->getLastEntity('token', true);

        // validations
        $this->assertArrayHasKey('razorpay_payment_id', $content);

        $this->assertEquals($payment[Payment::TOKEN_ID], $token2['id']);

        $this->assertTrue($payment['save']);

        $this->assertEquals($card[Card::GLOBAL_CARD_ID], null);

        $this->assertEquals($token2[Token::USED_COUNT], 2);

        $this->assertNotEquals($token2[Token::USED_AT], null);

        $this->assertEquals($token1['id'], $token2['id']);
    }


    protected function mockSession($appToken = 'capp_1000000custapp')
    {
        $data = [ 'test_app_token' => $appToken ];

        $this->session($data);
    }

    protected function sendOtp($contact)
    {
        $request = array(
            'url' => '/otp/create',
            'method' => 'post',
            'content' => [
                'contact' => $contact
            ],
        );

        $response = $this->sendRequest($request);

        return $response;
    }


    protected function verifyOtp($contact, $email, $otp, $deviceToken = null, $metadata = false)
    {
        $content = [
            'contact' => $contact,
            'email' => $email,
            'otp' => '0007',
        ];

        if ($deviceToken !== null)
        {
            $content['device_token'] = $deviceToken;
        }

        if ($metadata)
        {
            $content['_']['platform'] = 'android';
            $content['_']['library'] = 'checkoutjs';
            $content['_']['version'] = '1.0.0';
        }

        $request = array(
            'url' => '/otp/verify',
            'method' => 'post',
            'content' => $content
        );

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function mockCardVaultStripSpaces()
    {
        $cardVault = Mockery::mock('RZP\Services\CardVault')->makePartial();

        $this->app->instance('card.cardVault', $cardVault);

        $cardVault->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', Mockery::type('array'))
            ->andReturnUsing(function ($route, $method, $input)
            {
                $response = [
                    'error' => '',
                    'success' => true,
                ];

                switch ($route)
                {
                    case 'tokenize':
                        $this->assertEquals('4000400000000004', $input['secret']);

                        $response['token'] = base64_encode($input['secret']);
                        $response['fingerprint'] = base64_encode($input['secret']);
                        $response['scheme'] = "0";
                        break;

                    case 'detokenize':
                        $response['value'] = base64_decode($input['token']);
                        break;

                    case 'validate':
                        if ($input['token'] === 'fail')
                        {
                            $response['success'] = false;
                        }
                        break;

                    case 'delete':
                        break;
                }
                return $response;
            });

        $this->app->instance('card.cardVault', $cardVault);
    }

    protected function makeSaveCardGlobalPayment()
    {
         Mail::fake();

        // set payment data
        $this->mockSession();

        $payment = $this->getDefaultPaymentArray();

        $payment['save'] = 1;

        $payment[Payment::CARD]['number'] = '4000400000000004';

        // create payment and fetch entities
        $content = $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $card = $this->getLastEntity('card', true);

        $token = $this->getLastEntity('token', true);

        // validations
        $this->assertEquals($payment[Payment::CARD_ID], $card['id']);

        $this->assertNotEquals('card_'.$token[Token::CARD_ID], $card['id']);

        $this->assertEquals($card[Card::GLOBAL_CARD_ID], $token[Token::CARD_ID]);

        $this->assertEquals($payment[Payment::APP_TOKEN], '1000000custapp');
    }

    protected function mockRaven()
    {
        $raven = Mockery::mock('RZP\Services\Raven')->makePartial();

        $this->app->instance('raven', $raven);

        $raven->shouldReceive('sendRequest')
              ->with(Mockery::type('string'), 'post', Mockery::type('array'))
              ->andReturnUsing(function ($route, $method, $input)
                {
                    $response = array(
                        'success' => true,
                    );

                    return $response;
                });

        $this->app->instance('raven', $raven);
    }
}
