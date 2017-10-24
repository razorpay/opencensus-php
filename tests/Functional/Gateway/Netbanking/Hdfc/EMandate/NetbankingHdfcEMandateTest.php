<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Hdfc\EMandate;

use Carbon\Carbon;
use Mail;

use RZP\Constants\Timezone;
use RZP\Mail\Gateway\EMandate\Base as Email;
use RZP\Mail\Gateway\EMandate\Constants as EmailConstants;
use RZP\Models\Customer\Token;
use RZP\Models\Gateway\File;
use RZP\Models\Payment;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

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
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'hdfc_emandate_registration',
            'entity_type' => File\Entity::class,
            'entity_id'   => $content['id'],
            'extension'   => 'xlsx',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        // Mail::assertSent(Email::class);

        Mail::assertSent(Email::class, function ($mail) use ($file)
        {
            $key = Payment\Gateway::NETBANKING_HDFC . '_register';

            $today = Carbon::now(Timezone::IST)->format('d-m-Y');

            $expectedSubj = EmailConstants::SUBJECT_MAP[$key] . $today;

            $this->assertEquals($expectedSubj, $mail->subject);

            $testData = [
                'body'      => EmailConstants::BODY_MAP[$key],
                'file_name' => "HDFC_EMandate_Registration_test_$today.xlsx",
            ];

            $this->assertNotNull($mail->viewData['file_name']);
            $this->assertNotNull($mail->viewData['signed_url']);
            $this->assertEquals(EmailConstants::BODY_MAP[$key], $mail->viewData['body']);

            $this->assertNotEmpty($mail->attachments);

            return ($mail->hasFrom('emandate@razorpay.com') and
                    ($mail->hasTo(EmailConstants::RECIPIENT_EMAILS_MAP[$key])));
        });
    }

    protected function getNetbankingHdfcEmandateArray(): array
    {
        $payment = $this->getNetbankingRecurringPaymentArray('HDFC');

        $payment['account_number'] = '0123456789';

        return $payment;
    }
}