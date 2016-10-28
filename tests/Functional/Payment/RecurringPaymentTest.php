<?php

namespace RZP\Tests\Functional\Payment;

use Carbon\Carbon;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Card\Entity as Card;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Customer\Token\Entity as Token;

class RecurringPaymentTest extends TestCase
{
    use PaymentTrait;

    protected $recurringPlan;

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

        $this->fixtures->merchant->editFeatures('recurring');

        $payment = $this->getDefaultRecurringPaymentArray();

        $this->doAuthAndCapturePayment($payment);
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

        $this->fixtures->merchant->editFeatures('recurring');

        $payment = $this->getDefaultRecurringPaymentArray();

        $this->doAuthAndCapturePayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenEntity   = $this->getLastEntity('token', true);

        $this->assertEquals($paymentEntity[Payment::TERMINAL_ID], '1000CybrsTrmnl');

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

    public function testRecurringSecondPaymentCreatePrivateAuth()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->editFeatures('recurring');

        $payment = $this->getDefaultRecurringPaymentArray();

        $this->doAuthAndCapturePayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenEntity   = $this->getLastEntity('token', true);

        $this->assertEquals($paymentEntity[Payment::TERMINAL_ID], '1000CybrsTrmnl');

        $this->assertEquals(true, $tokenEntity[Token::RECURRING]);

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

        unset($payment[Payment::CARD]);

        $payment[Payment::TOKEN] = $tokenId;

        $this->ba->privateAuth();

        $content = $this->doS2SRecurringPayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals($paymentEntity[Payment::TERMINAL_ID], '2RecurringTerm');
    }

    public function testRecurringPaymentCreatePrivateAuth()
    {
         $this->ba->privateAuth();

        $this->fixtures->merchant->editFeatures('recurring');

        $this->fixtures->merchant->editFeatures('s2s');

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

        $this->fixtures->merchant->editFeatures('recurring');

        $payment[Payment::CARD]['number'] = '4000000000000002';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->doAuthPayment($payment);
        });
    }

    public function testRecurringPaymentAmexCardNotSupported()
    {
        $payment = $this->getDefaultRecurringPaymentArray();

        $this->fixtures->merchant->editFeatures('recurring');

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

        $this->fixtures->merchant->editFeatures('recurring');

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

        $this->fixtures->merchant->editFeatures('recurring');

        unset($payment[Payment::CARD]);

        $this->fixtures->base->editEntity('card', '100000000lcard', ["type" => 'credit']);

        $this->fixtures->base->editEntity('token', '100000custcard', ["recurring" => true]);

        $content = $this->doS2SRecurringPayment($payment);

        $payment[Payment::CARD] = [];

        $content = $this->doS2SRecurringPayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals($paymentEntity[Payment::TERMINAL_ID], '2RecurringTerm');
    }
}
