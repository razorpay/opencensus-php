<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Hdfc\EMandate;

use Mail;
use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Models\Customer\Token;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Gateway\EMandate\Base as Email;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Mail\Gateway\EMandate\Constants as EmailConstants;

class NetbankingHdfcEMandateTest extends TestCase
{
    use PaymentTrait;

    protected $payment;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/NetbankingHdfcEMandateTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:shared_netbanking_hdfc_recurring_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->merchant->addFeatures(['charge_at_will', 'e_mandate']);

        $this->payment = $this->getNetbankingHdfcEmandateArray();

        $this->mockTokenex();
    }

     /**
      * The following is a test case for the E Mandate Registration payment for HDFC.
      * The HDFC E Mandate Registration payment is just a normal authorization payment.
      */
    public function testEMandateInitialPayment()
    {
        $payment = $this->payment;

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $data = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($data, $payment);

        $token = $this->getLastEntity('token', true);

        $this->assertEquals($payment[Payment\Entity::TOKEN_ID], $token[Token\Entity::ID]);

        $this->assertEquals($this->payment['account_number'], $token[Token\Entity::ACCOUNT_NUMBER]);

        $this->assertTestResponse($token, 'matchInitiatedToken');
    }

    public function testEMandateRegistration()
    {
        Mail::fake();

        $this->testEMandateInitialPayment();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull($content[File\Entity::SENT_AT]);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'hdfc_emandate_register',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'xlsx',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        Mail::assertSent(Email::class, function ($mail) use ($file)
        {
            $key = Payment\Gateway::NETBANKING_HDFC . '_register';

            $today = Carbon::now(Timezone::IST)->format('d-m-Y');

            $expectedSubj = EmailConstants::SUBJECT_MAP[$key] . $today;

            $this->assertEquals($expectedSubj, $mail->subject);

            $testData = [
                'body'      => EmailConstants::BODY_MAP[$key],
                'file_name' => "HDFC_EMandate_Register_test_$today.xlsx",
            ];

            $this->assertNotNull($mail->viewData['file_name']);
            $this->assertNotNull($mail->viewData['signed_url']);
            $this->assertEquals(EmailConstants::BODY_MAP[$key], $mail->viewData['body']);

            $this->assertNotEmpty($mail->attachments);

            return ($mail->hasFrom('emandate@razorpay.com') and
                    ($mail->hasTo(EmailConstants::RECIPIENT_EMAILS_MAP[$key])));
        });
    }

    public function testEMandateDebit()
    {
        $payment = $this->payment;

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenId = $paymentEntity[Payment\Entity::TOKEN_ID];

        $this->fixtures->edit(
            'token',
            $tokenId,
            [
                Token\Entity::RECURRING => 1,
                Token\Entity::RECURRING_STATUS => Token\RecurringStatus::CONFIRMED
            ]);

        $payment[Payment\Entity::TOKEN] = $tokenId;

        // Second recurring payment request
        $this->doS2SRecurringPayment($payment);

        $debitPayment = $this->getLastEntity('payment', true);

        $this->ba->appAuth();

        Mail::fake();

        $content = $this->startTest();

        // Verify response
        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull($content[File\Entity::SENT_AT]);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        // Verify file_store entity
        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'hdfc_emandate_debit',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'xlsx',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        // Verify gateway payment entity
        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'matchAuthGatewayPayment');

        $debitPaymentId = substr($debitPayment['id'], 4);

        $this->assertEquals($gatewayPayment['payment_id'], $debitPaymentId);
        $this->assertEquals($gatewayPayment['amount'], $debitPayment['amount']);

        // Verify email
        Mail::assertSent(Email::class, function ($mail) use ($file)
        {
            $key = Payment\Gateway::NETBANKING_HDFC . '_debit';

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

    /**
     * It tests that scenario in which a debit file is requested to be sent for
     * a payment for which a netbanking entity has already been created.
     * The expectation is that it will skip the creation of netbanking entity
     * and continue with writing to file, and then sending it.
     */
    public function testEMandateDebitOnRetry()
    {
        $payment = $this->payment;

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenId = $paymentEntity[Payment\Entity::TOKEN_ID];

        $this->fixtures->edit(
            'token',
            $tokenId,
            [
                Token\Entity::RECURRING => 1,
                Token\Entity::RECURRING_STATUS => Token\RecurringStatus::CONFIRMED
            ]);

        $payment[Payment\Entity::TOKEN] = $tokenId;

        // Second recurring payment request
        $paymentId = $this->doS2SRecurringPayment($payment);

        $debitPayment = $this->getLastEntity('payment', true);

        $debitPaymentId = substr($debitPayment['id'], 4);

        $this->ba->appAuth();

        // Email send will throw exception, but file, and gateway-entity will still be created
        Mail::shouldReceive('send')->andThrow(new \Exception('mail_send_exceptiopn'));

        $testData = $this->testData['testEMandateDebitCreateFileFailure'];

        $content = $this->startTest($testData);

        // Verify response after file-create fails
        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNull($content[File\Entity::SENT_AT]);
        $this->assertNotNull($content[File\Entity::FAILED_AT]);

        $gatewayPayment = $this->getLastEntity('netbanking', true);
        $this->assertEquals($gatewayPayment['payment_id'], $debitPaymentId);

        // Verify file_store entity
        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'hdfc_emandate_debit',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'xlsx',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        // Retry
        Mail::fake();

        // Email send should succeed
        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull($content[File\Entity::SENT_AT]);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        // Verify gateway payment entity
        $gatewayPaymentLast = $this->getLastEntity('netbanking', true);

        $this->assertEquals($gatewayPaymentLast['id'], $gatewayPayment['id']);

        // Verify email
        Mail::assertSent(Email::class, function ($mail) use ($file)
        {
            $key = Payment\Gateway::NETBANKING_HDFC . '_debit';

            $this->assertNotNull($mail->viewData['file_name']);
            $this->assertNotNull($mail->viewData['signed_url']);

            $this->assertNotEmpty($mail->attachments);

            return true;
        });
    }

    protected function getNetbankingHdfcEmandateArray(): array
    {
        $payment = $this->getNetbankingRecurringPaymentArray('HDFC');

        $payment['account_number'] = '0123456789';

        return $payment;
    }
}
