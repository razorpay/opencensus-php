<?php

namespace RZP\Tests\Functional\Payment;

use Mail;
use Mockery;

use RZP\Exception;
use RZP\Mail\Payment\CardSaved as CardSavedMail;
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

        \Cache::shouldReceive('get')
                ->once()
                ->with($content['session_id'])
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

    public function testTokenexStripSpacesCheck()
    {
        $this->mockTokenexStripSpaces();

        $this->payment = $this->getDefaultPaymentArray();

        $this->payment['save'] = 1;

        $this->payment[Payment::CARD]['number'] = '40004 000 0000 0004';

        $this->payment[Payment::CARD]['expiry_year'] = '20';

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

        $this->payment[Payment::CARD]['expiry_year'] = '20';

        $this->payment[Payment::CUSTOMER_ID] = 'cust_100000customer';

        // create payment 1
        $content = $this->doAuthAndCapturePayment($this->payment);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals(Vault::RZP_VAULT, $card['vault']);
    }

    public function testUpdateExistingTokenexToken()
    {
        // create payment data
        $this->payment = $this->getDefaultPaymentArray();

        $this->payment['save'] = 1;

        $this->payment[Payment::CARD]['number'] = '4000400000000004';

        $this->payment[Payment::CARD]['expiry_year'] = '20';

        $this->payment[Payment::CUSTOMER_ID] = 'cust_100000customer';

        // create payment 1
        $content = $this->doAuthAndCapturePayment($this->payment);

        $card = $this->getLastEntity('card', true);

        $this->fixtures->edit('card', $card['id'], ['vault' => 'tokenex']);

        $newCard = $this->getLastEntity('card', true);

        $token = $card['vault_token'];

        $this->assertEquals($card['id'], $newCard['id']);
        $this->assertEquals(Vault::TOKENEX, $newCard['vault']);

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
                        $response['token'] = strrev(base64_encode($input['secret']));
                        break;

                    case 'detokenize':
                        $response['value'] = base64_decode(strrev($input['token']));
                        break;

                    case 'validate':
                        if ($input['token'] === 'fail')
                        {
                            $response['success'] = false;
                        }
                        break;
                    case 'tokenex_token';
                        $response['tokenex_token'] = strrev($input['token']);
                        break;

                    case 'delete':
                        break;
                }
                return $response;
            });

        $this->app->instance('card.cardVault', $cardVault);

        $this->payment = $this->getDefaultPaymentArray();

        $this->payment['save'] = 1;

        $this->payment[Payment::CARD]['number'] = '4000400000000004';

        $this->payment[Payment::CARD]['expiry_year'] = '20';

        $this->payment[Payment::CUSTOMER_ID] = 'cust_100000customer';

        // create payment 1
        $content = $this->doAuthAndCapturePayment($this->payment);

        $cardVaultCard = $this->getLastEntity('card', true);

        $this->assertEquals($newCard['id'], $cardVaultCard['id']);
        $this->assertEquals(Vault::RZP_VAULT, $cardVaultCard['vault']);
        $this->assertEquals(strrev($token), $cardVaultCard['vault_token']);
    }

    public function testUpdateExistingTokenexTokenCron()
    {
        $this->ba->cronAuth();

        $content = [
            'limit' => 500
        ];

        $request = [
            'url' => '/card/migrate/tokenex',
            'method' => 'post',
            'content' => $content,
        ];

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
                        $response['token'] = strrev(base64_encode($input['secret']));
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
                    case 'tokenex_token';
                        $response['tokenex_token'] = strrev($input['token']);
                        break;

                    case 'delete':
                        break;
                }
                return $response;
            });

        $token = base64_encode('4111111121111111');
        $attributes = [
                    'id'                =>  '100000011lcard',
                    'merchant_id'       =>  '10000000000000',
                    'name'              =>  'test',
                    'expiry_month'      =>  '12',
                    'expiry_year'       =>  '2100',
                    'iin'               =>  '411111',
                    'last4'             =>  '1111',
                    'vault_token'       =>  $token,
                    'vault'             => 'tokenex',
                ];

        $card = $this->fixtures->create('card', $attributes);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(0, count($response['failed_tokens']));

        $card = $this->getDbEntityById('card', '100000011lcard')->toArray();

        $this->assertEquals(strrev($token), $card['vault_token']);
        $this->assertEquals('rzpvault', $card['vault']);
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

    public function testCardDetokenizeMigration()
    {
        $this->ba->adminAuth();

        $content = [
            'tokens' => ['abc']
        ];

        $request = [
            'url' => '/card/migration/detokenize',
            'method' => 'post',
            'content' => $content,
        ];

        $cardVault = Mockery::mock('RZP\Services\CardVault')->makePartial();
        $this->app->instance('card.tokenex', $cardVault);
        $cardVault->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', Mockery::type('array'))
            ->andReturnUsing(function ($route, $method, $input)
            {
                 $response = [
                    'error' => '',
                    'success' => true,
                ];

                $this->assertEquals('abc', $input['token']);
                $response['value'] = base64_decode(strrev($input['token']));

                return $response;
            });

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEmpty(0, $response['failed_tokens']);
        $this->assertEquals(0, $response['count']);
        $this->assertEquals(1, $response['total_count']);

        $cardVault = Mockery::mock('RZP\Services\CardVault')->makePartial();
        $this->app->instance('card.tokenex', $cardVault);
        $cardVault->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', Mockery::type('array'))
            ->andReturnUsing(function ($route, $method, $input)
            {
                 throw new Exception\RuntimeException('card vault request failed');
            });

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(['abc'], $response['failed_tokens']);
        $this->assertEquals(1, $response['count']);
        $this->assertEquals(1, $response['total_count']);
    }

    protected function mockTokenexStripSpaces()
    {
        $tokenex = Mockery::mock('RZP\Services\TokenEx')->makePartial();

        $this->app->instance('card.tokenex', $tokenex);

        $tokenex->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', Mockery::type('array'))
            ->andReturnUsing(function ($route, $method, $input)
            {
                $response = [
                    'Error' => '',
                    'ReferenceNumber' => '15102913382030662954',
                    'Success' => true,
                ];


                switch ($route)
                {
                    case 'REST/Tokenize':

                        $this->assertEquals('4000400000000004', $input['Data']);

                        $response['Token'] = base64_encode($input['Data']);
                        break;

                    case 'REST/Detokenize':
                        $response['Value'] = base64_decode($input['Token']);
                        break;

                    case 'REST/ValidateToken':
                        $response['Valid'] = true;
                        break;

                    case 'REST/DeleteToken':
                        break;
                }
                return $response;
            });

        $this->app->instance('card.tokenex', $tokenex);
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
