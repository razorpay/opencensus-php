<?php

namespace RZP\Tests\Functional\Payment;

use Redis;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Customer\Token\Entity as Token;
use RZP\Models\Feature\Constants as Feature;

class RecurringPaymentTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/RecurringPaymentTestData.php';

        parent::setUp();

        $this->payment = $this->getDefaultPaymentArray();

        $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal');

        $this->fixtures->create('terminal:shared_cybersource_hdfc_recurring_terminals');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->mockTokenex();
    }

    public function testRecurringFirstPaymentCreatePublicAuth()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);

        $payment = $this->getDefaultRecurringPaymentArray();

        $this->doAuthAndCapturePayment($payment);
    }

    public function testDebitCardRecurringFirstPaymentCreatePublicAuth()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);
        $this->fixtures->iin->create([
            'iin' => '402790',
            'country' => 'IN',
            'network' => 'Visa',
            'type' => 'debit'
        ]);

        $payment = $this->getDefaultRecurringPaymentArray();
        $payment['card']['number'] = '4027902780181358';

        $this->makeRequestAndCatchException(function () use ($payment) {
            $this->doAuthPayment($payment);
        }, \RZP\Exception\BadRequestException::class);

        $this->fixtures->merchant->addFeatures([Feature::ALLOW_DC_RECURRING]);

        $this->doAuthPayment($payment);
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
        $this->markTestSkipped('We now allow second recurring on public auth');

        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);

        $payment = $this->getDefaultRecurringPaymentArray();

        $this->doAuthAndCapturePayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenEntity   = $this->getLastEntity('token', true);

        $this->assertEquals('1000CybrsTrmnl', $paymentEntity[Payment::TERMINAL_ID]);

        $this->assertEquals(true, $tokenEntity[Token::RECURRING]);

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

        unset($payment[Payment::CARD]);

        $payment[Payment::TOKEN] = $tokenId;

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testRecurringSecondPaymentCreatePublicAuthWithCard()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);

        $payment = $this->getDefaultRecurringPaymentArray();

        $this->doAuthAndCapturePayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenEntity   = $this->getLastEntity('token', true);

        $this->assertEquals('1000CybrsTrmnl', $paymentEntity[Payment::TERMINAL_ID]);
        $this->assertEquals(true, $tokenEntity[Token::RECURRING]);

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

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

        $this->assertEquals(true, $tokenEntity[Token::RECURRING]);

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

        unset($payment[Payment::CARD]);
        unset($payment[Payment::BANK]);

        $payment[Payment::TOKEN] = $tokenId;

        $this->ba->privateAuth();

        $content = $this->doS2SRecurringPayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals($paymentEntity[Payment::TERMINAL_ID], '2RecurringTerm');

        $this->assertEquals($paymentEntity[Payment::TWO_FACTOR_AUTH], 'skipped');
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

        $tokenEntity   = $this->getLastEntity('token', true);

        // Looks like the first one really is yours
        $this->assertEquals('FDRcrDTrmnl3DS', $paymentEntity[Payment::TERMINAL_ID]);

        $this->assertEquals(true, $tokenEntity[Token::RECURRING]);

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

        unset($payment[Payment::CARD]);

        $payment[Payment::TOKEN] = $tokenId;

        $this->ba->privateAuth();

        $content = $this->doS2SRecurringPayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        // OMG the second one is yours too, what is this sorcery
        $this->assertEquals('FDRcrDTrmlN3DS', $paymentEntity[Payment::TERMINAL_ID]);

        $this->assertEquals('skipped', $paymentEntity[Payment::TWO_FACTOR_AUTH]);
    }

    public function testRecurringPaymentCreatePrivateAuth()
    {
        $this->markTestSkipped('Mark skipped. Fix it');

        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL, Feature::S2S]);

        $payment = $this->getDefaultRecurringPaymentArray();

        $this->doS2SPrivateAuthAndCapturePayment($payment);
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

    public function testRecurringPaymentAmexCardNotSupported()
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

        $this->fixtures->base->editEntity('card', '100000000lcard', ["type" => 'credit']);

        $this->fixtures->base->editEntity('token', '100000custcard',
            [
                'recurring'   => true,
                'terminal_id' => '1000CybrsTrmnl',
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

        $this->fixtures->terminal->disableTerminal($firstDataTerminal2['id']);

        $this->fixtures->create('terminal:migs_recurring_terminal_with_both_recurring_types', ['merchant_id' => '10000000000000']);

        // Switch to private auth for second recurring payment
        $this->ba->privateAuth();

        // Set payment for second recurring payment
        unset($payment['card']);
        $payment['token'] = $paymentEntity['token_id'];

        $response = $this->doS2sRecurringPayment($payment);

        $paymentId = $response['razorpay_payment_id'];

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);

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

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);

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

        $paymentEntity = $this->getEntityById('payment', $paymentId, true);

        $this->assertNotNull($paymentEntity['token_id']);
        $this->assertEquals(true, $paymentEntity['recurring']);
        $this->assertEquals($firstDataTerminal2['id'], $paymentEntity['terminal_id']);

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

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->doS2SRecurringPayment($payment);
        });

        $this->fixtures->terminal->enableTerminal($firstDataTerminal2['id']);
        $this->fixtures->terminal->enableTerminal('MiGSRcg3DSN3DS');

        // Switch to private auth for fifth recurring payment
        $this->ba->privateAuth();

        // Set payment for fifth recurring payment
        unset($payment['card']);
        $payment['token'] = $paymentEntity['token_id'];

        $response = $this->doS2sRecurringPayment($payment);

        $paymentId = $response['razorpay_payment_id'];

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

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->doS2SRecurringPayment($payment);
        });

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

        // Need a mastercard number
        $payment['card']['number'] = '5243730000000008';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
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
}
