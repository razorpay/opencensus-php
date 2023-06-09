<?php

namespace RZP\Tests\Functional\Payment;

use Redis;
use Mockery;

use RZP\Constants\Entity as E;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Exception;
use RZP\Models\Bank\IFSC;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\RecurringType;
use RZP\Models\Payment\TwoFactorAuth;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Terminal\Entity as Terminal;
use RZP\Models\Feature\Constants as Feature;
use RZP\Models\Customer\Token\Entity as Token;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class RecurringPaymentTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected $mandateHQ;
    protected $mandateConfirm;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/RecurringPaymentTestData.php';

        parent::setUp();

        $this->payment = $this->getDefaultPaymentArray();

        $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal');

        $this->fixtures->create('terminal:shared_cybersource_hdfc_recurring_terminals');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->mockCardVault();

        $this->mandateHqTerminal = $this->fixtures->create('terminal:shared_mandate_hq_terminal');
    }

    public function testRecurringFirstPaymentCreatePublicAuth()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);

        $payment = $this->getDefaultRecurringPaymentArray();

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastPayment(true);

        $this->assertEquals('initial', $payment['recurring_type']);
    }

    public function testCardRecurringAutoPaymentIfTokenIsPaused()
    {

        $this->testRecurringFirstPaymentCreatePublicAuth();

        $token = $this->getDbLastEntity(E::TOKEN);
        $token->setRecurringStatus('paused');
        $token->saveOrFail();

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__]['request']['content']['token'] = $token->getPublicId();
        $this->testData[__FUNCTION__]['request']['content']['customer_id'] = 'cust_' . $token->getCustomerId();

        $this->startTest();
    }

    public function testCardRecurringAutoPaymentIfTokenIsCancelled()
    {
        $this->testRecurringFirstPaymentCreatePublicAuth();

        $token = $this->getDbLastEntity(E::TOKEN);
        $token->setRecurringStatus('cancelled');
        $token->saveOrFail();

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__]['request']['content']['token'] = $token->getPublicId();
        $this->testData[__FUNCTION__]['request']['content']['customer_id'] = 'cust_' . $token->getCustomerId();

        $this->startTest();
    }

    public function testCardRecurringAutoPaymentIfTokenIsExpired()
    {
        $this->testRecurringFirstPaymentCreatePublicAuth();

        $token = $this->getDbLastEntity(E::TOKEN);
        $token->setExpiredAt(1632826568);
        $token->saveOrFail();

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__]['request']['content']['token'] = $token->getPublicId();
        $this->testData[__FUNCTION__]['request']['content']['customer_id'] = 'cust_' . $token->getCustomerId();

        $this->startTest();
    }

    public function testCardRecurringAutoPaymentIfTokenStatusIsDeactivated()
    {

        $this->testRecurringFirstPaymentCreatePublicAuth();

        $this->allowAllTerminalRazorx();

        $token = $this->getDbLastEntity(E::TOKEN);
        $token->setStatus('deactivated');

        $card = $token->card;
        $card->setVault('visa');

        $token->saveOrFail();
        $card->saveOrFail();

        $this->mockCardVaultWithCryptogram();

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__]['request']['content']['token'] = $token->getPublicId();
        $this->testData[__FUNCTION__]['request']['content']['customer_id'] = 'cust_' . $token->getCustomerId();

        $this->startTest();
    }

    public function testCardRecurringAutoPaymentIfTokenStatusIsSuspended()
    {

        $this->testRecurringFirstPaymentCreatePublicAuth();

        $this->allowAllTerminalRazorx();

        $token = $this->getDbLastEntity(E::TOKEN);
        $token->setStatus('suspended');

        $card = $token->card;
        $card->setVault('visa');

        $token->saveOrFail();
        $card->saveOrFail();

        $this->mockCardVaultWithCryptogram();

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__]['request']['content']['token'] = $token->getPublicId();
        $this->testData[__FUNCTION__]['request']['content']['customer_id'] = 'cust_' . $token->getCustomerId();

        $this->startTest();
    }

    public function testRecurringFirstPaymentCreatePrivateAuth()
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);

        $payment = $this->getDefaultRecurringPaymentArray();

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastPayment(true);

        $this->assertEquals('initial', $payment['recurring_type']);
    }

    public function testRecurringDomesticCardPaymentSubscriptionRegistration()
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);

        $this->fixtures->iin->create([
            'iin'       => '526731',
            'country'   => 'IN',
            'type'      => 'credit',
            'recurring' => 1,
        ]);

        $payment = $this->getDefaultRecurringPaymentArray();
        $payment['card']['number'] = '5267318187975449';
        $payment['amount'] = 100000;

        $subr = $this->fixtures->create('subscription_registration',
            ['method' => 'card', 'max_amount' => 400000, 'expire_at' => 4091958776, 'notes' => []]);

        $order = $this->fixtures->create('order',
            ['amount' => 100000]);

        $this->fixtures->create('invoice',
            ['entity_type' => 'subscription_registration', 'entity_id' => $subr->id, 'order_id' => $order->id]);

        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $updatedSubr = $this->getDbEntity('subscription_registration', ['id' => $subr->id]);
        $token = $this->getDbEntity('token', ['id' => $updatedSubr->token_id]);

        $this->assertEquals($subr->max_amount, $token->max_amount);
        $this->assertEquals($subr->expire_at, $token->expired_at);
    }

    public function testRecurringDomesticCardPaymentSubscriptionRegistrationMaxAmountNull()
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);

        $this->fixtures->iin->create([
            'iin'       => '526731',
            'country'   => 'IN',
            'type'      => 'credit',
            'recurring' => 1,
        ]);

        $payment = $this->getDefaultRecurringPaymentArray();
        $payment['card']['number'] = '5267318187975449';
        $payment['amount'] = 100000;

        $subr = $this->fixtures->create('subscription_registration',
            ['method' => 'card', 'max_amount' => null, 'expire_at' => 4091958776, 'notes' => []]);

        $order = $this->fixtures->create('order',
            ['amount' => 100000]);

        $this->fixtures->create('invoice',
            ['entity_type' => 'subscription_registration', 'entity_id' => $subr->id, 'order_id' => $order->id]);

        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $updatedSubr = $this->getDbEntity('subscription_registration', ['id' => $subr->id]);
        $token = $this->getDbEntity('token', ['id' => $updatedSubr->token_id]);

        $this->assertEquals(1500000, $token->max_amount);
        $this->assertEquals($subr->expire_at, $token->expired_at);
    }

    public function testRecurringInternationalCardPaymentSubscriptionRegistrationMaxAmountNull()
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);

        $this->fixtures->iin->create([
            'iin'       => '555555',
            'country'   => 'US',
            'type'      => 'credit',
            'recurring' => 1,
        ]);

        $payment = $this->getDefaultRecurringPaymentArray();
        $payment['card']['number'] = '5555555555554444';
        $payment['amount'] = 100000;

        $subr = $this->fixtures->create('subscription_registration',
            ['method' => 'card', 'max_amount' => null, 'expire_at' => 4091958776, 'notes' => []]);

        $order = $this->fixtures->create('order',
            ['amount' => 100000]);

        $this->fixtures->create('invoice',
            ['entity_type' => 'subscription_registration', 'entity_id' => $subr->id, 'order_id' => $order->id]);

        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $updatedSubr = $this->getDbEntity('subscription_registration', ['id' => $subr->id]);
        $token = $this->getDbEntity('token', ['id' => $updatedSubr->token_id]);

        $this->assertEquals(9999900, $token->max_amount);
        $this->assertEquals($subr->expire_at, $token->expired_at);
    }

    public function testRecurringDomesticCardPaymentSubscriptionRegistrationMaxAmountLimitRelaxation()
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);

        $this->fixtures->iin->create([
            'iin'       => '526731',
            'country'   => 'IN',
            'type'      => 'credit',
            'recurring' => 1,
        ]);

        $payment = $this->getDefaultRecurringPaymentArray();
        $payment['card']['number'] = '5267318187975449';
        $payment['amount'] = 100000;

        $subr = $this->fixtures->create('subscription_registration',
            ['method' => 'card', 'max_amount' => 1600000, 'expire_at' => 4091958776, 'notes' => []]);

        $order = $this->fixtures->create('order',
            ['amount' => 100000]);

        $this->fixtures->create('invoice',
            ['entity_type' => 'subscription_registration', 'entity_id' => $subr->id, 'order_id' => $order->id]);

        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $updatedSubr = $this->getDbEntity('subscription_registration', ['id' => $subr->id]);
        $token = $this->getDbEntity('token', ['id' => $updatedSubr->token_id]);

        $this->assertEquals(1600000, $token->max_amount);
        $this->assertEquals($subr->expire_at, $token->expired_at);
    }

    public function testDebitCardRecurringFirstPaymentCreatePublicAuth()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);
        $this->fixtures->iin->create([
            'iin' => '402790',
            'country' => 'IN',
            'network' => 'Visa',
            'type' => 'debit',
            'issuer' => 'KKBK',
            'recurring' => 1,
        ]);

        $payment = $this->getDefaultRecurringPaymentArray();
        $payment['card']['number'] = '4027902780181358';

        $this->doAuthPayment($payment);

        $payment = $this->getLastPayment(true);

        $this->assertEquals('initial', $payment['recurring_type']);
    }

    /**
     * - create recurring auth payment with debit card, without hitachi terminal. should fail.
     * - create hitachi terminal. create recurring auth payment with debit card. should succeed.
     * - remove hitachi terminal. add allow_all_dc_recurring feature. create recurring auth
     *   payment with debit card. should succeed.
     */
    public function testDebitCardRecurringTerminal()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);

        $this->fixtures->iin->create([
            'iin' => '402400',
            'country' => 'IN',
            'network' => 'Visa',
            'type' => 'debit',
            'issuer' => 'KKBK',
            'recurring' => 1,
        ]);

        $payment = $this->getDefaultRecurringPaymentArray();
        $payment['card']['number'] = '4024001104457538';

        $this->fixtures->create('terminal:hitachi_recurring_terminal_with_both_recurring_types', ['merchant_id' => '10000000000000']);

        $this->doAuthPayment($payment);

        $this->fixtures->terminal->disableTerminal('HitcRcg3DSN3DS');

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getLastPayment(true);

        $this->assertEquals('initial', $paymentEntity['recurring_type']);
    }

    public function testRecurringInternationalPaymentWhenAllowed()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->enableInternational();

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);

        $payment = $this->getDefaultRecurringPaymentArray();
        // international card
        $payment['card']['number'] = '4012010000000007';

        $this->doAuthAndCapturePayment($payment);

        $paymentEntity = $this->getLastPayment(true);

        $this->assertEquals('initial', $paymentEntity['recurring_type']);

        $this->fixtures->merchant->disableInternational();
    }

    public function testRecurringInternationalPaymentWhenNotAllowed()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->enableInternational();

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL, Feature::BLOCK_INTERNATIONAL_RECURRING]);

        $payment = $this->getDefaultRecurringPaymentArray();
        // international card
        $payment['card']['number'] = '4012010000000007';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $this->fixtures->merchant->disableInternational();
    }

    public function testRecurringPaymentCreateFeatureDisabled()
    {
        $this->ba->publicAuth();

        $payment = $this->getDefaultRecurringPaymentArray();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testRecurringSecondPaymentCreatePublicAuth()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);

        $payment = $this->getDefaultRecurringPaymentArray();

        $this->doAuthAndCapturePayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenEntity   = $this->getLastEntity('token', true);

        $this->assertEquals('initial', $paymentEntity['recurring_type']);

        $this->assertEquals('1000CybrsTrmnl', $paymentEntity[Payment::TERMINAL_ID]);

        $this->assertEquals(true, $tokenEntity[Token::RECURRING]);

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

        unset($payment[Payment::CARD]);

        $payment[Payment::TOKEN] = $tokenId;

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals('initial', $paymentEntity['recurring_type']);
        $this->assertEquals('1000CybrsTrmnl', $paymentEntity[Payment::TERMINAL_ID]);
        $this->assertEquals(true, $tokenEntity[Token::RECURRING]);
    }

    public function testRecurringPaymentWithNewCustomer()
    {
        $this->ba->privateAuth();

        $request = [
            'url' => '/customers',
            'method' => 'post',
            'content' => [
                'name'    => 'testc',
                'email'   => 'test@razorpay.com',
                'contact' => '1234567899',
                ],
        ];



        $response = $this->makeRequestAndGetContent($request);

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);

        $payment = $this->getDefaultRecurringPaymentArray();

        $payment['customer_id'] = $response['id'];

        $this->ba->publicAuth();

        $this->doAuthAndCapturePayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenEntity   = $this->getLastEntity('token', true);

        $this->assertEquals($paymentEntity[Payment::TERMINAL_ID], '1000CybrsTrmnl');
        $this->assertEquals('initial', $paymentEntity['recurring_type']);
        $this->assertEquals(true, $tokenEntity[Token::RECURRING]);

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

        unset($payment[Payment::CARD]);
        unset($payment[Payment::BANK]);

        $payment[Payment::TOKEN] = $tokenId;

        $this->ba->privateAuth();

        $content = $this->doS2SRecurringPayment($payment);

        $url = $this->testData[__FUNCTION__]['request']['url'];
        $this->testData[__FUNCTION__]['request']['url'] = sprintf($url, substr($content['razorpay_payment_id'], 4));
        $this->ba->reminderAppAuth();

        $this->startTest();

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals($paymentEntity[Payment::TERMINAL_ID], '2RecurringTerm');
        $this->assertEquals('auto', $paymentEntity['recurring_type']);
        $this->assertEquals($paymentEntity[Payment::TWO_FACTOR_AUTH], 'skipped');
    }

    public function testRecurringSecondPaymentCreatePublicAuthWithCard()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);

        $payment = $this->getDefaultRecurringPaymentArray();

        $this->doAuthAndCapturePayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenEntity   = $this->getLastEntity('token', true);

        $this->assertEquals('initial', $paymentEntity['recurring_type']);
        $this->assertEquals('1000CybrsTrmnl', $paymentEntity[Payment::TERMINAL_ID]);
        $this->assertEquals(true, $tokenEntity[Token::RECURRING]);

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals('initial', $paymentEntity['recurring_type']);
        $this->assertEquals('1000CybrsTrmnl', $paymentEntity[Payment::TERMINAL_ID]);
        $this->assertEquals(true, $tokenEntity[Token::RECURRING]);
    }

    public function testRecurringSecondPaymentWithCardOnPrivateAuth()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);

        $payment = $this->getDefaultRecurringPaymentArray();

        $this->doAuthAndCapturePayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenEntity   = $this->getLastEntity('token', true);

        $this->assertEquals('initial', $paymentEntity['recurring_type']);
        $this->assertEquals('1000CybrsTrmnl', $paymentEntity[Payment::TERMINAL_ID]);
        $this->assertEquals(true, $tokenEntity[Token::RECURRING]);

        $this->ba->privateAuth();
        $this->doAuthPayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals('initial', $paymentEntity['recurring_type']);
        $this->assertEquals('1000CybrsTrmnl', $paymentEntity[Payment::TERMINAL_ID]);
        $this->assertEquals(true, $tokenEntity[Token::RECURRING]);
    }

    public function testRecurringSecondPaymentCreatePrivateAuth()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);



        $payment = $this->getDefaultRecurringPaymentArray();

        $this->doAuthAndCapturePayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenEntity   = $this->getLastEntity('token', true);

        $this->assertEquals($paymentEntity[Payment::TERMINAL_ID], '1000CybrsTrmnl');
        $this->assertEquals('initial', $paymentEntity['recurring_type']);
        $this->assertEquals(true, $tokenEntity[Token::RECURRING]);

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

        unset($payment[Payment::CARD]);
        unset($payment[Payment::BANK]);

        $payment[Payment::TOKEN] = $tokenId;

        $this->ba->privateAuth();

        $content = $this->doS2SRecurringPayment($payment);

        $url = $this->testData[__FUNCTION__]['request']['url'];
        $this->testData[__FUNCTION__]['request']['url'] = sprintf($url, substr($content['razorpay_payment_id'], 4));
        $this->ba->reminderAppAuth();

        $this->startTest();

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals($paymentEntity[Payment::TERMINAL_ID], '2RecurringTerm');
        $this->assertEquals('auto', $paymentEntity['recurring_type']);
        $this->assertEquals($paymentEntity[Payment::TWO_FACTOR_AUTH], 'skipped');
    }

    public function testRecurringSecondPaymentCreatePrivateAuthHitachi()
    {
        $this->ba->publicAuth();

        $this->fixtures->terminal->disableTerminal('1RecurringTerm');
        $this->fixtures->terminal->disableTerminal('1000CybrsTrmnl');
        $this->fixtures->terminal->disableTerminal('3RecurringTerm');

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'recurring' => 1,
            'flows'   => [
                '3ds'          => '1',
                'headless_otp' => '1',
            ]
        ]);


        $terminal = $this->fixtures->create('terminal:hitachi_recurring_terminal_with_both_recurring_types');

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);



        $payment = $this->getDefaultRecurringPaymentArray();

        $payment['card']['number'] = '5567630000002004';

        $this->doAuthAndCapturePayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenEntity   = $this->getLastEntity('token', true);

        $this->assertEquals($paymentEntity[Payment::TERMINAL_ID], 'HitcRcg3DSN3DS');
        $this->assertEquals('initial', $paymentEntity['recurring_type']);
        $this->assertEquals(true, $tokenEntity[Token::RECURRING]);

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

        unset($payment[Payment::CARD]);
        unset($payment[Payment::BANK]);

        $payment[Payment::TOKEN] = $tokenId;

        $terminal2 = $this->fixtures->create('terminal:direct_hitachi_recurring_terminal_with_both_recurring_types');

        $this->ba->privateAuth();

        $content = $this->doS2SRecurringPayment($payment);

        $url = $this->testData[__FUNCTION__]['request']['url'];
        $this->testData[__FUNCTION__]['request']['url'] = sprintf($url, substr($content['razorpay_payment_id'], 4));
        $this->ba->reminderAppAuth();

        $this->startTest();

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals($paymentEntity[Payment::TERMINAL_ID], '100HitaDirTmnl');
        $this->assertEquals('auto', $paymentEntity['recurring_type']);
        $this->assertEquals($paymentEntity[Payment::TWO_FACTOR_AUTH], 'skipped');
    }

    public function testRecurringOtpFix()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);
        $this->fixtures->merchant->addFeatures(['axis_express_pay', 'otp_auth_default']);

        $this->fixtures->iin->create([
            'iin'     => '402400',
            'country' => 'IN',
            'issuer'  => 'UTIB',
            'network' => 'Visa',
            'type'    => 'debit',
            'recurring' => 1,
            'flows'   => [
                '3ds' => '1',
            ]
        ]);



        $payment = $this->getDefaultRecurringPaymentArray();
        $payment['card']['number'] = '4024001104457538';

        $terminal = $this->fixtures->create('terminal:hitachi_recurring_terminal_with_both_recurring_types', ['merchant_id' => '10000000000000']);

        $this->doAuthPayment($payment);

        $this->fixtures->terminal->disableTerminal('HitcRcg3DSN3DS');

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getLastPayment(true);

        $this->assertEquals('initial', $paymentEntity['recurring_type']);

        $this->fixtures->edit('iin', 402400, ['flows' => [
            '3ds' => '1',
            'otp' => '1',
        ]]);

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenEntity   = $this->getLastEntity('token', true);

        $this->assertEquals(true, $tokenEntity[Token::RECURRING]);

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

        unset($payment[Payment::CARD]);
        unset($payment[Payment::BANK]);

        $payment[Payment::TOKEN] = $tokenId;

        $this->ba->privateAuth();

        $this->fixtures->terminal->edit($terminal->getId(), ['type' => ['recurring_non_3ds' => 1]]);

        $content = $this->doS2SRecurringPayment($payment);

        $url = $this->testData[__FUNCTION__]['request']['url'];
        $this->testData[__FUNCTION__]['request']['url'] = sprintf($url, substr($content['razorpay_payment_id'], 4));
        $this->ba->reminderAppAuth();

        $this->startTest();

        $paymentEntity = $this->getLastEntity('payment', true);
        $this->assertNull($paymentEntity[Payment::AUTH_TYPE]);
        $this->assertEquals($paymentEntity[Payment::TERMINAL_ID], '2RecurringTerm');
        $this->assertEquals('auto', $paymentEntity['recurring_type']);
        $this->assertEquals($paymentEntity[Payment::TWO_FACTOR_AUTH], 'skipped');
    }

    public function testRecurringSecondPaymentCreatePublicAuthWithoutRecurringToken()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);

        $payment = $this->getDefaultRecurringPaymentArray();

        $payment['save'] = 1;
        unset($payment['recurring']);

        $this->doAuthAndCapturePayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenEntity   = $this->getLastEntity('token', true);

        $this->assertEquals($paymentEntity[Payment::TERMINAL_ID], '1000CybrsTrmnl');
        $this->assertEquals(false, $tokenEntity[Token::RECURRING]);

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

        unset($payment[Payment::CARD]);
        unset($payment[Payment::BANK]);

        $payment['recurring'] = 1;

        $payment[Payment::TOKEN] = $tokenId;

        $this->doAuthAndCapturePayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals('initial', $paymentEntity['recurring_type']);
    }

    public function testRecurringSecondPaymentCreatePrivateAuthWithoutRecurringToken()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL, Feature::S2S]);

        $payment = $this->getDefaultRecurringPaymentArray();

        $payment['save'] = 1;
        unset($payment['recurring']);

        $this->doAuthAndCapturePayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenEntity   = $this->getLastEntity('token', true);

        $this->assertEquals($paymentEntity[Payment::TERMINAL_ID], '1000CybrsTrmnl');
        $this->assertEquals(false, $tokenEntity[Token::RECURRING]);

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

        unset($payment[Payment::CARD]);
        unset($payment[Payment::BANK]);

        $payment['recurring'] = 1;

        $this->ba->privateAuth();

        $payment[Payment::TOKEN] = $tokenId;

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals('initial', $paymentEntity['recurring_type']);
        $this->assertTrue($paymentEntity['recurring']);
    }

    /**
     * A test to ensure that 2nd recurring payments can pass through a terminal
     * even if it isn't assigned to the merchant, as long as the first recurring
     * payment went through a terminal that was assigned to the merchant
     * Uses gateway token relations.
     */
    public function testRecurringSecondPaymentUnassignedTerminal()
    {
        // A pair of recurring terminals exist, but they aren't yours
        $this->fixtures->create('terminal:direct_first_data_recurring_terminals', [
            'merchant_id' => '1ApiFeeAccount',
        ]);

        // Okay now the first one is yours
        $response = $this->assignSubMerchant('FDRcrDTrmnl3DS', '10000000000000');

        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);



        $payment = $this->getDefaultRecurringPaymentArray();

        $this->doAuthAndCapturePayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);
        $this->assertEquals('initial', $paymentEntity['recurring_type']);
        $tokenEntity   = $this->getLastEntity('token', true);

        // Looks like the first one really is yours
        $this->assertEquals('FDRcrDTrmnl3DS', $paymentEntity[Payment::TERMINAL_ID]);

        $this->assertEquals(true, $tokenEntity[Token::RECURRING]);

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

        unset($payment[Payment::CARD]);

        $payment[Payment::TOKEN] = $tokenId;

        $this->ba->privateAuth();

        $content = $this->doS2SRecurringPayment($payment);

        $url = $this->testData[__FUNCTION__]['request']['url'];
        $this->testData[__FUNCTION__]['request']['url'] = sprintf($url, substr($content['razorpay_payment_id'], 4));
        $this->ba->reminderAppAuth();

        $this->startTest();

        $paymentEntity = $this->getLastEntity('payment', true);

        // OMG the second one is yours too, what is this sorcery
        $this->assertEquals('FDRcrDTrmlN3DS', $paymentEntity[Payment::TERMINAL_ID]);
        $this->assertEquals('auto', $paymentEntity['recurring_type']);
        $this->assertEquals('skipped', $paymentEntity[Payment::TWO_FACTOR_AUTH]);
    }

    public function testRecurringPaymentCreatePrivateAuth()
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures([Feature::S2S, Feature::CHARGE_AT_WILL]);

        $payment = $this->getDefaultRecurringPaymentArray();

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals('initial', $paymentEntity['recurring_type']);
        $this->assertTrue($paymentEntity['recurring']);
    }

    public function testRecurringPaymentCreatePrivateAuthS2SDisabled()
    {
        $this->ba->privateAuth();

        $payment = $this->getDefaultRecurringPaymentArray();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testRecurringPaymentFailedCardNotSupported()
    {
        $payment = $this->getDefaultRecurringPaymentArray();

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);

        $payment[Payment::CARD]['number'] = '4245126853998870';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->doAuthPayment($payment);
        });
    }

    public function hitachi_subtestRecurringPaymentAmexCardNotSupported()
    {
        $payment = $this->getDefaultRecurringPaymentArray();

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);

        $payment[Payment::CARD]['number'] = '341111111111111';
        $payment[Payment::CARD]['cvv'] = '8888';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->doAuthPayment($payment);
        });
    }

    public function testRecurringPaymentUsingSavedCardTokenNotRecurring()
    {
        $payment = $this->getDefaultRecurringPaymentArray();

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);

        $payment[Payment::TOKEN] = '10000cardtoken';

        $this->fixtures->iin->edit('411111',[
            'recurring' => 0
        ]);

        unset($payment[Payment::CARD]);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->doAuthPayment($payment);
        });
    }

    public function testRecurringPaymentUsingSavedCardTokenRecurring()
    {
        $payment = $this->getDefaultRecurringPaymentArray();

        $payment[Payment::TOKEN] = '10000cardtoken';

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);

        unset($payment[Payment::CARD]);

        $this->fixtures->base->editEntity('card', '100000000lcard', ['type' => 'credit']);

        $this->fixtures->base->editEntity('token', '100000custcard',
            [
                'recurring'   => true,
                'terminal_id' => '1000CybrsTrmnl',
                'recurring_status' => 'confirmed',
            ]);

        $this->fixtures->create('gateway_token',
            [
                'token_id' => '100000custcard',
                'terminal_id' => '1000CybrsTrmnl'
            ]);

        $content = $this->doS2SRecurringPayment($payment);

        $payment[Payment::CARD] = [];

        $content = $this->doS2SRecurringPayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals($paymentEntity[Payment::TERMINAL_ID], '2RecurringTerm');
        $this->assertEquals('auto', $paymentEntity['recurring_type']);
        $this->assertEquals($paymentEntity[Payment::TWO_FACTOR_AUTH], 'skipped');
    }

    public function testRecurringPaymentsWithMultipleGatewayTokensForOneToken()
    {
        // - Create a first recurring payment. Ensure it goes via
        //   FirstData terminal. Check that gateway token is created.

        // - Disable first data terminal. Enable
        //   axis migs recurring terminal of type 6.

        // - Create second recurring payment. It should not fail.
        //   It should go through axis migs terminal successfully.
        //   Check that two gateway tokens are created. Only one token
        //   is present. Token's terminal is now axis_migs'.

        // - Create third recurring payment. It should go through axis
        //   migs properly. There should still be only two gateway tokens.
        //   One gateway token of first data and another of axis migs.

        // - Disable axis migs terminal. Enable first data terminal.

        // - Create 4th recurring payment. It should go through first data
        //   terminal. Only two gateway tokens should be present. Token's
        //   terminal should change to first data's.

        // - Disable both axis migs terminal
        //   and first data terminals.

        // - Attempt to create 5th recurring payment.
        //   Payment should fail with no terminal found.

        // - Enable both axis migs and first data terminals.

        // - Create 5th recurring payment. Payment should go through
        //   axis migs. There should be only two gateway tokens.

        // - Create and enable migs shared terminal of type 6.
        //   Disable first data terminal

        // - Attempt to create 6th recurring payment. It should fail
        //   with `no terminal found` error.

        // For some reason, we create shared Cybersource terminal with recurring 3DS
        $this->fixtures->terminal->disableTerminal('1000CybrsTrmnl');
        $this->fixtures->terminal->disableTerminal('1RecurringTerm');
        $this->fixtures->terminal->disableTerminal('3RecurringTerm');

        list($firstDataTerminal1, $firstDataTerminal2) = $this->fixtures->create('terminal:shared_first_data_recurring_terminals');

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);



        $payment = $this->getDefaultRecurringPaymentArray();

        $response = $this->doAuthPayment($payment);

        $paymentId = $response['razorpay_payment_id'];

        $firstDataGatewayToken = $this->getLastEntity('gateway_token', true);

        $this->assertEquals($firstDataTerminal1['id'], $firstDataGatewayToken['terminal_id']);

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);

        $this->assertEquals('initial', $paymentEntity['recurring_type']);

        $this->fixtures->terminal->disableTerminal($firstDataTerminal2['id']);

        $this->fixtures->create('terminal:migs_recurring_terminal_with_both_recurring_types', ['merchant_id' => '10000000000000']);

        // Switch to private auth for second recurring payment
        $this->ba->privateAuth();

        // Set payment for second recurring payment
        unset($payment['card']);
        $payment['token'] = $paymentEntity['token_id'];

        $response = $this->doS2sRecurringPayment($payment);

        $paymentId = $response['razorpay_payment_id'];

        $url = $this->testData[__FUNCTION__]['request']['url'];
        $this->testData[__FUNCTION__]['request']['url'] = sprintf($url, substr($paymentId, 4));
        $this->ba->reminderAppAuth();

        $this->startTest();

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);

        $this->assertEquals('auto', $paymentEntity['recurring_type']);
        $this->assertNotNull($paymentEntity['token_id']);
        $this->assertEquals(true, $paymentEntity['recurring']);
        $this->assertEquals('MiGSRcg3DSN3DS', $paymentEntity['terminal_id']);

        $gatewayTokens = $this->getEntities('gateway_token', [], true);
        // There should be two gateway_tokens created for the two recurring payments
        // since the second recurring payment went through a different gateway.
        $this->assertEquals(2, $gatewayTokens['count']);

        $tokens = $this->getEntities('token', ['recurring' => 1], true);
        // There should be only one token created even though second
        // recurring payment went through different terminal and gateway.
        $this->assertEquals(1, $tokens['count']);

        $token = $this->getEntityById('token', $paymentEntity['token_id'], true);
        // The terminal should have gotten updated with the latest one.
        // We don't use this terminal anywhere. So doesn't really matter.
        $this->assertEquals('MiGSRcg3DSN3DS', $token['terminal_id']);
        $this->assertEquals(2, $token['used_count']);
        $this->assertEquals(true, $token['recurring']);

        // Switch to private auth for third recurring payment
        $this->ba->privateAuth();

        // Set payment for third recurring payment
        unset($payment['card']);
        $payment['token'] = $paymentEntity['token_id'];

        $response = $this->doS2sRecurringPayment($payment);

        $paymentId = $response['razorpay_payment_id'];

        $this->testData[__FUNCTION__]['request']['url'] = sprintf($url, substr($paymentId, 4));
        $this->ba->reminderAppAuth();

        $this->startTest();

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);

        $this->assertNotNull($paymentEntity['token_id']);
        $this->assertEquals(true, $paymentEntity['recurring']);
        $this->assertEquals('MiGSRcg3DSN3DS', $paymentEntity['terminal_id']);
        $this->assertEquals('auto', $paymentEntity['recurring_type']);

        $gatewayTokens = $this->getEntities('gateway_token', [], true);
        // There should be two gateway_tokens created for the two recurring payments
        // since the second recurring payment went through a different gateway.
        $this->assertEquals(2, $gatewayTokens['count']);

        $tokens = $this->getEntities('token', ['recurring' => 1], true);
        // There should be only one token created even though second
        // recurring payment went through different terminal and gateway.
        $this->assertEquals(1, $tokens['count']);

        $token = $this->getEntityById('token', $paymentEntity['token_id'], true);
        // The terminal should have gotten updated with the latest one.
        // We don't use this terminal anywhere. So doesn't really matter.
        $this->assertEquals('MiGSRcg3DSN3DS', $token['terminal_id']);
        $this->assertEquals(3, $token['used_count']);
        $this->assertEquals(true, $token['recurring']);

        $this->fixtures->terminal->disableTerminal('MiGSRcg3DSN3DS');
        $this->fixtures->terminal->enableTerminal($firstDataTerminal2['id']);

        // Switch to private auth for fourth recurring payment
        $this->ba->privateAuth();

        // Set payment for fourth recurring payment
        unset($payment['card']);
        $payment['token'] = $paymentEntity['token_id'];

        $response = $this->doS2sRecurringPayment($payment);

        $paymentId = $response['razorpay_payment_id'];

        $this->testData[__FUNCTION__]['request']['url'] = sprintf($url, substr($paymentId, 4));
        $this->ba->reminderAppAuth();

        $this->startTest();

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);

        $this->assertNotNull($paymentEntity['token_id']);
        $this->assertEquals(true, $paymentEntity['recurring']);
        $this->assertEquals($firstDataTerminal2['id'], $paymentEntity['terminal_id']);
        $this->assertEquals('auto', $paymentEntity['recurring_type']);

        $gatewayTokens = $this->getEntities('gateway_token', [], true);
        // There should be two gateway_tokens created for the two recurring payments
        // since the second recurring payment went through a different gateway.
        $this->assertEquals(2, $gatewayTokens['count']);

        $tokens = $this->getEntities('token', ['recurring' => 1], true);
        // There should be only one token created even though second
        // recurring payment went through different terminal and gateway.
        $this->assertEquals(1, $tokens['count']);

        $token = $this->getEntityById('token', $paymentEntity['token_id'], true);
        // The terminal should have gotten updated with the latest one.
        // We don't use this terminal anywhere. So doesn't really matter.
        $this->assertEquals($firstDataTerminal2['id'], $token['terminal_id']);
        $this->assertEquals(4, $token['used_count']);
        $this->assertEquals(true, $token['recurring']);

        $this->fixtures->terminal->disableTerminal($firstDataTerminal2['id']);

        // Switch to private auth for fifth recurring payment
        $this->ba->privateAuth();

        // Set payment for fifth recurring payment
        unset($payment['card']);
        $payment['token'] = $paymentEntity['token_id'];

        $ex = false;
        try
        {
            $this->doS2SRecurringPayment($payment);
        }
        catch (\Exception $e)
        {
            $this->assertEquals('Terminal should not be null', $e->getMessage());

            $ex = true;
        }
        $this->assertTrue($ex);

        $this->fixtures->terminal->enableTerminal($firstDataTerminal2['id']);
        $this->fixtures->terminal->enableTerminal('MiGSRcg3DSN3DS');

        // Switch to private auth for fifth recurring payment
        $this->ba->privateAuth();

        // Set payment for fifth recurring payment
        unset($payment['card']);
        $payment['token'] = $paymentEntity['token_id'];

        $response = $this->doS2sRecurringPayment($payment);

        $paymentId = $response['razorpay_payment_id'];

        $this->testData[__FUNCTION__]['request']['url'] = sprintf($url, substr($paymentId, 4));
        $this->ba->reminderAppAuth();

        $this->startTest();

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);

        $this->assertNotNull($paymentEntity['token_id']);
        $this->assertEquals(true, $paymentEntity['recurring']);

        // The terminal gets selected based on the priority rules and stuff here.
        $this->assertEquals('MiGSRcg3DSN3DS', $paymentEntity['terminal_id']);

        $gatewayTokens = $this->getEntities('gateway_token', [], true);
        // There should be two gateway_tokens created for the two recurring payments
        // since the second recurring payment went through a different gateway.
        $this->assertEquals(2, $gatewayTokens['count']);

        $tokens = $this->getEntities('token', ['recurring' => 1], true);
        // There should be only one token created even though second
        // recurring payment went through different terminal and gateway.
        $this->assertEquals(1, $tokens['count']);

        $token = $this->getEntityById('token', $paymentEntity['token_id'], true);
        // The terminal should have gotten updated with the latest one.
        // We don't use this terminal anywhere. So doesn't really matter.
        $this->assertEquals('MiGSRcg3DSN3DS', $token['terminal_id']);
        $this->assertEquals(5, $token['used_count']);
        $this->assertEquals(true, $token['recurring']);

        $this->fixtures->terminal->disableTerminal($firstDataTerminal2['id']);
        $this->fixtures->terminal->disableTerminal('MiGSRcg3DSN3DS');
        $this->fixtures->create('terminal:migs_recurring_terminal_with_both_recurring_types', [
                                                                        'id' => 'MiGSRcS3DSN3DS',
                                                                        'merchant_id' => '100000Razorpay']);

        // Switch to private auth for sixth recurring payment
        $this->ba->privateAuth();

        // Set payment for sixth recurring payment
        unset($payment['card']);
        $payment['token'] = $paymentEntity['token_id'];

        $ex = false;
        try
        {
            $this->doS2SRecurringPayment($payment);
        }
        catch (\Exception $e)
        {
            $this->assertEquals('Terminal should not be null', $e->getMessage());

            $ex = true;
        }
        $this->assertTrue($ex);

        $this->ba->publicAuth();
    }

    public function testRecurringPaymentsWithMultipleNormalAndFallbackTerminals()
    {
        $this->markTestSkipped('Not de-prioritizing fallback terminals for now');

        // - Create first data recurring terminals
        // - First payment to go via first data recurring terminal
        // - Create Axis recurring terminal with type 6
        // - Prioritize Axis over all other gateways
        // - Second recurring payment to go via first data recurring terminal
        //   - The above happens because the fallback sorter ensures that terminals with gateway
        //     tokens is prioritized over terminals without gateway tokens (fallback terminals)

        // For some reason, we create shared Cybersource terminal with recurring 3DS
        $this->fixtures->terminal->disableTerminal('1000CybrsTrmnl');
        $this->fixtures->terminal->disableTerminal('1RecurringTerm');
        $this->fixtures->terminal->disableTerminal('3RecurringTerm');

        list($firstDataTerminal1, $firstDataTerminal2) = $this->fixtures->create('terminal:shared_first_data_recurring_terminals');

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);

        $payment = $this->getDefaultRecurringPaymentArray();

        $response = $this->doAuthPayment($payment);

        $paymentId = $response['razorpay_payment_id'];

        $firstDataGatewayToken = $this->getLastEntity('gateway_token', true);

        $this->assertEquals($firstDataTerminal1['id'], $firstDataGatewayToken['terminal_id']);

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);

        $this->fixtures->create('terminal:migs_recurring_terminal_with_both_recurring_types', ['merchant_id' => '10000000000000']);

        $this->ba->adminAuth();

        Redis::shouldReceive('zadd')
             ->once()
             ->andReturnUsing(function ()
             {
                 return 5;
             });

        $data = $this->testData['testSaveGatewayPriority'];

        $this->startTest($data);

        // Switch to private auth for second recurring payment
        $this->ba->privateAuth();

        // Set payment for second recurring payment
        unset($payment['card']);
        $payment['token'] = $paymentEntity['token_id'];

        $response = $this->doS2SRecurringPayment($payment);

        $paymentId = $response['razorpay_payment_id'];

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);

        $this->assertNotNull($paymentEntity['token_id']);
        $this->assertEquals(true, $paymentEntity['recurring']);
        $this->assertEquals($firstDataTerminal2['id'], $paymentEntity['terminal_id']);

        $gatewayTokens = $this->getEntities('gateway_token', [], true);
        // There should be only one gateway_token created for the two recurring payments
        // since the second recurring payment went through the same gateway and terminal (first data).
        $this->assertEquals(1, $gatewayTokens['count']);

        $tokens = $this->getEntities('token', ['recurring' => 1], true);
        $this->assertEquals(1, $tokens['count']);

        $token = $this->getEntityById('token', $paymentEntity['token_id'], true);
        // The terminal should have gotten updated with the latest one.
        // We don't use this terminal anywhere. So doesn't really matter.
        $this->assertEquals($firstDataTerminal2['id'], $token['terminal_id']);
        $this->assertEquals(2, $token['used_count']);
        $this->assertEquals(true, $token['recurring']);
    }

    public function testRecurringPaymentCardNetworkNotSupported()
    {
        // Create hitachi recurring terminal
        // Create Visa recurring payment
        // It should fail saying no terminal found

        // For some reason, we create shared Cybersource terminal with recurring 3DS
        $this->fixtures->terminal->disableTerminal('1000CybrsTrmnl');
        $this->fixtures->terminal->disableTerminal('1RecurringTerm');
        $this->fixtures->terminal->disableTerminal('3RecurringTerm');

        $this->fixtures->create('terminal:hitachi_recurring_terminal_with_both_recurring_types', ['merchant_id' => '10000000000000']);

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);

        $payment = $this->getDefaultRecurringPaymentArray();

        $payment['card']['number'] = '5893163050216758';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testRecurringEmandatePaymentWithExpiredToken()
    {
        $this->fixtures->create('terminal:shared_emandate_hdfc_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->fixtures->merchant->enableEmandate();

        $this->mockCardVault();

        $payment = $this->getEmandateNetbankingRecurringPaymentArray('HDFC');

        $payment['bank_account'] = [
            'account_number'    => '0123456789',
            'ifsc'              => 'HDFC0000186',
            'name'              => 'Test Account',
            'account_type'      => 'savings',
        ];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);

        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

        $this->fixtures->edit(
            'token',
            $tokenId,
            [
                Payment::RECURRING => 1,
                Token::RECURRING_STATUS => 'confirmed',
                Token::EXPIRED_AT => 1551931831,
            ]);

        $payment[Payment::TOKEN] = $tokenId;

        $payment['amount'] = 4000;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);

        $payment['order_id'] = $order->getPublicId();

        // Second recurring payment request

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->doS2SRecurringPayment($payment);
            },
            Exception\BadRequestException::class,
            'Token has expired and cannot be used for recurring payments');
    }

    public function testRecurringCardPaymentWithExpiredToken()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);

        $payment = $this->getDefaultRecurringPaymentArray();

        $this->doAuthAndCapturePayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

        $this->fixtures->edit(
            'token',
            $tokenId,
            [
                Payment::RECURRING => 1,
                Token::RECURRING_STATUS => 'confirmed',
                Token::EXPIRED_AT => 1551931831,
            ]);

        unset($payment[Payment::CARD]);

        unset($payment[Payment::BANK]);

        $payment[Payment::TOKEN] = $tokenId;

        $this->ba->privateAuth();

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->doS2SRecurringPayment($payment);
            },
            Exception\BadRequestException::class,
            'Token has expired and cannot be used for recurring payments');
    }

    public function testRecurringPaymentWithCardMandate()
    {
        $this->ba->publicAuth();

        $this->mandateHQ = Mockery::mock('RZP\Services\MandateHQ', [$this->app]);

        $this->app->instance('mandateHQ', $this->mandateHQ);
        $this->mandateConfirm = 'true';
        $this->mockRegisterMandate();
        $this->mockCheckBin();
        $this->mockShouldSkipSummaryPage(false);

        $this->mockReportPayment();

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);
        $this->fixtures->merchant->addFeatures([Feature::CARD_MANDATE_SKIP_PAGE]);

        $this->fixtures->merchant->addFeatures(['recurring_card_mandate']);
        $this->fixtures->create('iin', [
            'iin' => '400018',
            'type' => 'credit',
            'recurring' => 1,
            'issuer' => IFSC::RATN,
        ]);

        $payment = $this->getDefaultRecurringPaymentArray();
        $payment['bank'] = IFSC::RATN;
        $payment['card']['number'] = '4000184186218826';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $payment,
        ];

        $response = $this->makeRequestAndGetContent($request);
        $payment = $this->getDbLastEntity(E::PAYMENT);

        $this->assertEquals('authorized', $payment->getStatus());
        $this->assertEquals('initial', $payment->getRecurringType());
        $this->assertNotNull($payment->getTokenId());

        $token = $payment->localToken;
        $this->assertNotEmpty($token);
        $this->assertEquals('confirmed', $token->getRecurringStatus());

        $cardMandate = $this->getDbLastEntity(E::CARD_MANDATE);
        $this->assertNotEmpty($cardMandate);
        $this->assertNotEmpty($cardMandate->getMandateSummaryUrl());
        $this->assertEquals('active', $cardMandate->getStatus());
        $this->assertEquals('ratn_PP3VC146gmBVGG', $cardMandate->getMandateId());
    }

    public function enableCPS($terminal){

        $this->enableCpsConfig();
        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();

        $this->app->instance('card.payments', $cardService);

        $cardService->shouldReceive('sendRequest')
            ->with('POST', Mockery::type('string'), Mockery::type('array'))
            ->andReturnUsing(function (string $method, string $url, array $input) use ($terminal)
            {
                return [
                    'data' => [
                        'acquirer' => [
                            'reference2' => 'test12',
                        ],
                    ],
                    'payment' => [
                        'auth_type' => null,
                        'terminal_id'  => $terminal->getId(),
                        'authentication_gateway' => 'checkout_dot_com'
                    ],
                ];
            });
    }

    public function testInitialRecurringPaymentCheckoutDotCom()
    {
        $this->ba->publicAuth();

        $terminal = $this->fixtures->create("terminal:checkout_dot_com_terminal_all_types");
        $this->enableCPS($terminal);

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);
        $this->fixtures->merchant->addFeatures([Feature::RECURRING_CHECKOUT_DOT_COM]);
        $this->fixtures->merchant->enableInternational();

        $payment = $this->getDefaultRecurringPaymentArray();

        $payment['card']['number'] = '4212345678901237';

        $this->fixtures->iin->create([
            'iin'     => '421234',
            'country' => 'US',
            'network' => 'MasterCard',
            'recurring' => 1,
            'flows'   => [
                '3ds'          => '1',
                'headless_otp' => '1',
            ]
        ]);

        $content = $this->doAuthPayment($payment);
        $this->assertArrayHasKey('razorpay_payment_id', $content);

        $paymentEntity = $this->getDbLastEntity(E::PAYMENT);
        $tokenEntity   = $this->getDbLastEntity(E::TOKEN);

        $this->assertEquals($paymentEntity[Payment::TERMINAL_ID], $terminal[Terminal::ID]);
        $this->assertEquals(RecurringType::INITIAL, $paymentEntity['recurring_type']);
        $this->assertEquals(true, $tokenEntity[Token::RECURRING]);
    }

    public function testAutoRecurringPaymentCheckoutDotCom()
    {
        $this->ba->publicAuth();
        $terminal = $this->fixtures->create("terminal:checkout_dot_com_terminal_all_types");
        $this->enableCPS($terminal);

        $this->fixtures->iin->create([
            'iin'     => '421234',
            'country' => 'US',
            'network' => 'MasterCard',
            'recurring' => 1,
            'flows'   => [
                '3ds'          => '1',
                'headless_otp' => '1',
            ]
        ]);

        $terminal = $this->fixtures->create("terminal:checkout_dot_com_terminal_all_types");

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);
        $this->fixtures->merchant->addFeatures([Feature::RECURRING_CHECKOUT_DOT_COM]);

        $payment = $this->getDefaultRecurringPaymentArray();

        $payment['card']['number'] = '4212345678901237';

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getDbLastEntity(E::PAYMENT);
        $tokenEntity   = $this->getDbLastEntity(E::TOKEN);

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

        unset($payment[Payment::CARD]);
        unset($payment[Payment::BANK]);

        $payment[Payment::TOKEN] = 'token_'.$tokenId;

        $this->ba->privateAuth();

        $content = $this->doS2SRecurringPayment($payment);

        $paymentEntity = $this->getDbLastEntity(E::PAYMENT);

        $this->assertEquals($paymentEntity[Payment::GATEWAY], $terminal[Terminal::GATEWAY]);
        $this->assertEquals(RecurringType::AUTO, $paymentEntity['recurring_type']);
    }

    public function testRecurringPaymentCheckoutDotComNotActivated()
    {
        $this->ba->publicAuth();

        $this->fixtures->create("terminal:checkout_dot_com_terminal_all_types");

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);
        $this->fixtures->merchant->enableInternational();

        $payment = $this->getDefaultRecurringPaymentArray();

        $payment['card']['number'] = '4212345678901237';

        $this->fixtures->iin->create([
            'iin'     => '421234',
            'country' => 'US',
            'network' => 'MasterCard',
            'recurring' => 1,
            'flows'   => [

            ]
        ]);

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $response = $this->doAuthPayment($payment);
                $error = $response['error'];

                self::assertEquals(Gateway::CHECKOUT_DOT_COM, $error['data']['gateway']);
                self::assertEquals(PublicErrorCode::BAD_REQUEST_ERROR, $error['code']);

                self::assertEquals(ErrorCode::BAD_REQUEST_PAYMENT_INTERNATIONAL_RECURRING_NOT_ALLOWED_FOR_MERCHANT,
                    $error['internal_error_code']);
            },
            Exception\BadRequestException::class,
            PublicErrorDescription::BAD_REQUEST_PAYMENT_INTERNATIONAL_RECURRING_NOT_ALLOWED_FOR_MERCHANT);

    }

    protected function assignSubMerchant(string $tid, string $mid)
    {
        $url = '/terminals/' . $tid . '/merchants/' . $mid;

        $request = [
            'url'    => $url,
            'method' => 'PUT',
        ];

        $this->ba->adminAuth();

        $this->ba->getAdmin()->merchants()->attach('10000000000000');

        return $this->makeRequestAndGetContent($request);
    }

    protected function mockRazorx($param, $value)
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode) use($param, $value)
                {
                    if ($feature === $param)
                    {
                        return $value;
                    }

                    return 'control';
                }));
    }

    protected function runPaymentCallbackFlowNetbanking($response, &$callback = null, $gateway)
    {
        if (strpos($response->getContent(), 'Mandate') != null)
        {
            list ($url, $method, $values) = $this->getDataForGatewayRequest($response, $callback);
            $data = $this->makeFirstGatewayPaymentMockRequest($url, $method, $values);
            return $this->submitPaymentCallbackRedirect($data);
        }

        return $this->runPaymentCallbackFlowForNbplusGateway($response, $gateway, $callback);
    }
}
