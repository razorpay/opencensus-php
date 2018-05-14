<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Axis\EMandate;

use Carbon\Carbon;
use  Mail;

use RZP\Constants\Entity;
use RZP\Mail\Gateway\EMandate\Base as Email;
use RZP\Constants\Timezone;
use RZP\Gateway\Netbanking\Axis\Emandate;
use RZP\Mail\Gateway\EMandate\Constants as EmailConstants;
use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;
use RZP\Models\Customer\Token;
use RZP\Models\FileStore\Type;
use RZP\Models\Gateway\File;
use RZP\Models\Payment;
use RZP\Models\FileStore\Format;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

class NetbankingAxisEMandateTest extends TestCase
{
    use PaymentTrait;

    protected $payment;

    const ACCOUNT_NUMBER    = '914010009305862';
    const IFSC              = 'UTIB0002766';
    const NAME              = 'Test account';

    public function setUp()
    {
        $this->gateway = 'netbanking_axis';

        $this->testDataFilePath = __DIR__.'/NetbankingAxisEMandateTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:shared_emandate_axis_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->create('customer');

        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->fixtures->merchant->enableEmandate('10000000000000');

        $this->payment = $this->getEmandateNetbankingRecurringPaymentArray('UTIB');

        $this->payment['bank_account'] = [
                                            'account_number'    => self::ACCOUNT_NUMBER,
                                            'ifsc'              => self::IFSC,
                                            'name'              => self::NAME,
                                         ];

        unset($this->payment[Entity::CARD]);
    }

    public function testEmandateInitialPayment()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 0]);
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = 0;

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->assertEquals(0, $payment['amount']);
        $this->assertEquals('captured', $payment['status']);
    }

    public function testRefundEmandateInitialPaymentWithFeeCredit()
    {
        $this->fixtures->create('credits', [
            'type'        => 'fee',
            'value'       => 10000,
        ]);

        $this->fixtures->merchant->editFeeCredits('10000', '10000000000000');

        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 0]);
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = 0;

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->assertEquals(0, $payment['amount']);
        $this->assertEquals('captured', $payment['status']);

        $paymentTxn = $this->getLastEntity(Entity::TRANSACTION, true);

        $this->assertEquals(0, $paymentTxn['credit']);
        $this->assertEquals(1180, $paymentTxn['fee']);
        $this->assertEquals(180, $paymentTxn['tax']);
        $this->assertEquals('payment', $paymentTxn['type']);
        $this->assertEquals('prepaid', $paymentTxn['fee_model']);
        $this->assertEquals('fee', $paymentTxn['credit_type']);

        $merchantBalance = $this->getEntityById(Entity::BALANCE, '10000000000000', true);

        $this->assertEquals(8820, $merchantBalance['fee_credits']);

        $this->refundPayment($payment['id']);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->assertEquals(0, $payment['amount_refunded']);
        $this->assertEquals('refunded', $payment['status']);

        $refundTxn = $this->getLastEntity(Entity::TRANSACTION, true);

        $this->assertEquals(0, $refundTxn['debit']);
        $this->assertEquals(0, $refundTxn['fee']);
        $this->assertEquals(0, $refundTxn['tax']);
        $this->assertEquals('refund', $refundTxn['type']);
        $this->assertEquals('na', $refundTxn['fee_model']);
    }

    //
    // This test is to ensure that amount credit flow is not executed for
    // zero ruppee payments
    //
    public function testRefundEmandateInitialPaymentWithAmountCredit()
    {
        $credit = $this->fixtures->create('credits', [
               'type'        => 'amount',
               'value'       => 10000,
           ]);

        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 0]);
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = 0;

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->assertEquals(0, $payment['amount']);
        $this->assertEquals('captured', $payment['status']);

        $paymentTxn = $this->getLastEntity(Entity::TRANSACTION, true);

        $this->assertEquals(0, $paymentTxn['credit']);
        $this->assertEquals(1180, $paymentTxn['fee']);
        $this->assertEquals(180, $paymentTxn['tax']);
        $this->assertEquals('payment', $paymentTxn['type']);
        $this->assertEquals('prepaid', $paymentTxn['fee_model']);
        $this->assertEquals('default', $paymentTxn['credit_type']);

        $merchantBalance = $this->getEntityById(Entity::BALANCE, '10000000000000', true);

        $this->assertEquals(998820, $merchantBalance['balance']);

        $this->refundPayment($payment['id']);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->assertEquals(0, $payment['amount_refunded']);
        $this->assertEquals('refunded', $payment['status']);

        $refundTxn = $this->getLastEntity(Entity::TRANSACTION, true);

        $this->assertEquals(0, $refundTxn['debit']);
        $this->assertEquals(0, $refundTxn['fee']);
        $this->assertEquals(0, $refundTxn['tax']);
        $this->assertEquals('refund', $refundTxn['type']);
        $this->assertEquals('na', $refundTxn['fee_model']);
    }

    public function testRefundEmandateInitialPaymentWithNormalPricing()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 0]);
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = 0;

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->assertEquals(0, $payment['amount']);
        $this->assertEquals('captured', $payment['status']);

        $paymentTxn = $this->getLastEntity(Entity::TRANSACTION, true);

        $this->assertEquals(0, $paymentTxn['credit']);
        $this->assertEquals(1180, $paymentTxn['fee']);
        $this->assertEquals(180, $paymentTxn['tax']);
        $this->assertEquals('payment', $paymentTxn['type']);
        $this->assertEquals('prepaid', $paymentTxn['fee_model']);
        $this->assertEquals('default', $paymentTxn['credit_type']);

        $merchantBalance = $this->getEntityById(Entity::BALANCE, '10000000000000', true);

        $this->assertEquals(998820, $merchantBalance['balance']);

        $this->refundPayment($payment['id']);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->assertEquals(0, $payment['amount_refunded']);
        $this->assertEquals('refunded', $payment['status']);

        $refundTxn = $this->getLastEntity(Entity::TRANSACTION, true);

        $this->assertEquals(0, $refundTxn['debit']);
        $this->assertEquals(0, $refundTxn['fee']);
        $this->assertEquals(0, $refundTxn['tax']);
        $this->assertEquals('refund', $refundTxn['type']);
        $this->assertEquals('na', $refundTxn['fee_model']);
    }

    public function testEmandateDifferentPricing()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 0]);
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = 0;

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->assertEquals(0, $payment['amount']);
        $this->assertEquals('captured', $payment['status']);

        $paymentTxn = $this->getLastEntity(Entity::TRANSACTION, true);

        $this->assertEquals(0, $paymentTxn['credit']);
        $this->assertEquals(1180, $paymentTxn['fee']);
        $this->assertEquals(180, $paymentTxn['tax']);
        $this->assertEquals('payment', $paymentTxn['type']);
        $this->assertEquals('prepaid', $paymentTxn['fee_model']);
        $this->assertEquals('default', $paymentTxn['credit_type']);

        $merchantBalance = $this->getEntityById(Entity::BALANCE, '10000000000000', true);

        $this->assertEquals(998820, $merchantBalance['balance']);

        $token = $this->getLastEntity('token', true);

        $payment = $this->payment;

        $payment[Payment\Entity::TOKEN] = $token['id'];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 3000]);
        $payment['amount'] = 3000;
        $payment['order_id'] = $order->getPublicId();

        $this->doS2SRecurringPayment($payment);

        $debitPayment = $this->getLastEntity('payment', true);

        $this->assertEquals(3000, $debitPayment['amount']);
        $this->assertEquals('created', $debitPayment['status']);
    }

    public function testEmandateInitialPaymentFailure()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 0]);
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = 0;

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'emandateauth')
            {
                $content[Emandate\ResponseFields::STATUS_CODE] = Emandate\StatusCode::FAILED;
            }
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testPaymentVerify()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 0]);
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = 0;

        $response = $this->doAuthPayment($payment);

        $verify = $this->verifyPayment($response['razorpay_payment_id']);

        assert($verify['payment']['verified'] === 1);

        $verifyResponseContent = $verify['gateway']['verifyResponseContent'];

        $this->assertTestResponse($verifyResponseContent);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertEquals($gatewayPayment[NetbankingEntity::STATUS], Emandate\StatusCode::SUCCESS);

        $this->assertEquals($gatewayPayment[NetbankingEntity::RECEIVED], true);
    }

    public function testPaymentVerifyFailure()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 0]);
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = 0;

        $response = $this->doAuthPayment($payment);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify_emandate')
            {
                $content[Emandate\ResponseFields::STATUS_CODE] = Emandate\StatusCode::FAILED;
            }
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($response)
        {
            $this->verifyPayment($response['razorpay_payment_id']);
        });
    }

    public function testPaymentVerifyAmountMismatch()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 0]);
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = 0;

        $response = $this->doAuthPayment($payment);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify_emandate')
            {
                // Don't need to change status code, since status code would be success from gateway
                $content[Emandate\ResponseFields::AMOUNT] = 12;
            }
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($response)
        {
            $this->verifyPayment($response['razorpay_payment_id']);
        });
    }

    public function testEmandateDebit()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 0]);
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = 0;

        $this->doAuthPayment($payment);

        $token = $this->getLastEntity('token', true);

        $payment[Payment\Entity::TOKEN] = $token['id'];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 3000]);
        $payment['amount'] = 3000;
        $payment['order_id'] = $order->getPublicId();

        $this->doS2SRecurringPayment($payment);

        $debitPayment = $this->getLastEntity('payment', true);

        $this->ba->adminAuth();

        Mail::fake();

        $content = $this->startTest();

        $this->assertEquals(1, count($content['items']));

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => Type::AXIS_EMANDATE_DEBIT,
            'entity_type' => Entity::GATEWAY_FILE,
            'entity_id'   => $content['id'],
            'extension'   => Format::CSV,
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        // Verify gateway payment entity
        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'matchAuthGatewayPayment');

        $debitPaymentId = substr($debitPayment['id'], 4);

        $this->assertEquals($gatewayPayment['payment_id'], $debitPaymentId);
        $this->assertEquals($gatewayPayment['amount'], $debitPayment['amount']);

        Mail::assertQueued(Email::class, function ($mail) use ($file)
        {
            $key = Payment\Gateway::NETBANKING_AXIS . '_debit';

            $today = Carbon::now(Timezone::IST)->format('d-m-Y');

            $expectedSubj = EmailConstants::SUBJECT_MAP[$key] . $today;

            $this->assertEquals($expectedSubj, $mail->subject);

            $this->assertNotNull($mail->viewData['file_name']);
            $this->assertNotNull($mail->viewData['signed_url']);
            $this->assertEquals(EmailConstants::BODY_MAP[$key], $mail->viewData['body']);

            $this->assertNotEmpty($mail->attachments);

            return ($mail->hasFrom('emandate@razorpay.com') and
                ($mail->hasTo(EmailConstants::RECIPIENT_EMAILS_MAP[$key])));
        });
    }

    protected function assertEmandateEntities()
    {
        $netbanking = $this->getLastEntity('netbanking', true);

        $token = $this->getLastEntity('token', true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertNotNull($netbanking['si_token']);

        $this->assertEquals('9999999999', $netbanking['bank_payment_id']);
        $this->assertEquals(Emandate\StatusCode::EMANDATE_REGISTRATION_SUCCESS, $netbanking['si_status']);
        $this->assertEquals(Emandate\StatusCode::SUCCESS, $netbanking['status']);
        $this->assertEquals($payment['id'], 'pay_' . $netbanking['payment_id']);
        $this->assertEquals($token['gateway_token'], $netbanking['si_token']);

        $this->assertEquals($payment['token_id'], $token['id']);

        $this->assertEquals(Token\RecurringStatus::CONFIRMED, $token[Token\Entity::RECURRING_STATUS]);
        $this->assertEquals(self::ACCOUNT_NUMBER, $token[Token\Entity::ACCOUNT_NUMBER]);
    }
}
