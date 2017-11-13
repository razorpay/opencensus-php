<?php

namespace RZP\Tests\Functional\Payment;

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

        $content = $this->doS2sRecurringPayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals($paymentEntity[Payment::TERMINAL_ID], '2RecurringTerm');

        $this->assertEquals($paymentEntity[Payment::TWO_FACTOR_AUTH], 'skipped');
    }

    public function testRecurringPaymentCreatePrivateAuth()
    {
        $this->markTestSkipped('Mark skipped. Fix it');

        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures([Feature::RECURRING, Feature::S2S]);

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

        $content = $this->doS2sRecurringPayment($payment);

        $payment[Payment::CARD] = [];

        $content = $this->doS2sRecurringPayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals($paymentEntity[Payment::TERMINAL_ID], '2RecurringTerm');

        $this->assertEquals($paymentEntity[Payment::TWO_FACTOR_AUTH], 'skipped');
    }
}
