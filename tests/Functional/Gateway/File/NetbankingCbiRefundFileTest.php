<?php

namespace RZP\Tests\Functional\Gateway\File;

use Illuminate\Http\UploadedFile;
use Mail;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Models\Payment\Gateway;
use RZP\Reconciliator\RequestProcessor\Base;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;
use RZP\Mail\Gateway\RefundFile\Constants as RefundFileMailConstants;

class NetbankingCbiRefundFileTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingCbiRefundFileTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:shared_netbanking_cbi_terminal');
    }

    protected function reconcile($gateway, $uploadedFile, $forceAuthorizePayments = [])
    {
        $this->ba->appAuth();

        $input = [
            'manual'           => true,
            'gateway'          => $gateway,
            'attachment-count' => 1,
        ];

        if (empty($forceAuthorizePayments) === false)
        {
            foreach ($forceAuthorizePayments as $forceAuthorizePayment)
            {
                $input[Base::FORCE_AUTHORIZE][] = $forceAuthorizePayment;
            }
        }

        $request = [
            'url'     => '/reconciliate',
            'content' => $input,
            'method'  => 'POST',
            'files'   => [
                Base::ATTACHMENT_HYPHEN_ONE => $uploadedFile,
            ],
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }
    protected function generateFile($bank, $input)
    {
        $gateway = 'netbanking_' . $bank;

        $request = [
            'url'     => '/gateway/mock/reconciliation/' . $gateway,
            'content' => $input,
            'method'  => 'POST'
        ];

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }
    protected function createUploadedFile($file)
    {
        $this->assertFileExists($file);

        $mimeType = "text/plain";

        $uploadedFile = new UploadedFile(
            $file,
            $file,
            $mimeType,
            filesize($file),
            null,
            true
        );

        return $uploadedFile;
    }

    public function testNetbankingCbiRefundFile()
    {
        Mail::fake();

        $payment1 = $this->getDefaultNetbankingPaymentArray('CBIN');

        $payment1 = $this->doAuthAndCapturePayment($payment1);

        $payment2 = $this->getDefaultNetbankingPaymentArray('CBIN');

        $payment2 = $this->doAuthAndCapturePayment($payment2);

        $fileContents = $this->generateFile('cbi', ['gateway' => 'netbanking_cbi']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);
        $this->reconcile('NetbankingCbi', $uploadedFile);
        $refund1 = $this->refundPayment($payment1['id']);
        $refund2 = $this->refundPayment($payment2['id'],100);
        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity('file_store', true);


        $expectedFileContent = [
            'type'        => 'cbi_netbanking_refund',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'txt',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        Mail::assertQueued(RefundFileMail::class, function ($mail) use ($file)
        {
            $today = Carbon::now(Timezone::IST)->format('d-m-Y');

            $expectedSubject = RefundFileMailConstants::SUBJECT_MAP[Gateway::NETBANKING_CBI] . $today;
            $today = Carbon::now(Timezone::IST)->format('dmY');
            $this->assertEquals($expectedSubject, $mail->subject);

            $testData = [
                'body'        => RefundFileMailConstants::BODY_MAP[Gateway::NETBANKING_CBI],
                'file_name'   => "CBIRefund_$today.txt",
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->assertNotEmpty($mail->attachments);

            //
            // Marking netbanking transaction as reconciled after sending in bank file
            //
            $refundTransaction = $this->getLastEntity('transaction',true);

            $this->assertNotNull($refundTransaction['reconciled_at']);

            return ($mail->hasFrom('refunds@razorpay.com') and
                ($mail->hasTo(RefundFileMailConstants::RECIPIENT_EMAILS_MAP[Gateway::NETBANKING_CBI])));
        });
    }
}
