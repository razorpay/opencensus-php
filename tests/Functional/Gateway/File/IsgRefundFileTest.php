<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Models\Payment\Gateway;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;
use RZP\Tests\Functional\Helpers\Payment\PaymentIsgTrait;
use RZP\Mail\Gateway\RefundFile\Constants as RefundMailConstants;

class IsgRefundFileTest extends TestCase
{
    use PaymentTrait;
    use PaymentIsgTrait;

    public function setUp()
    {
        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/IsgRefundFileTestData.php';

        parent::setUp();

        $this->gateway = 'isg';

        $this->fixtures->create('terminal:bharat_qr_isg_terminal');

        $this->fixtures->merchant->addFeatures(['virtual_accounts', 'bharat_qr']);
    }

    public function testBharatQrIsgRefundFile()
    {
        Mail::fake();

        $response = $this->createBharatQrPayment();

        $payment = $this->getLastEntity('payment', true);

        $refund = $this->refundPayment($payment['id']);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $time = Carbon::now(Timezone::IST)->format('dmY');

        $expectedFilesContent = [
            'type' => 'isg_bharatqr_refund',
            'location' => 'Refund' . '_' . $time . '.txt',
        ];

        $file = $this->getLastEntity('file_store', true);

        $this->assertArraySelectiveEquals($expectedFilesContent, $file);

        Mail::assertQueued(RefundFileMail::class, function ($mail)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $expectedSubject = RefundMailConstants::SUBJECT_MAP[Gateway::ISG] . $date;

            $this->assertEquals($expectedSubject, $mail->subject);

            $testData = [
                'count' => 1,
                'body'  => RefundMailConstants::BODY_MAP[Gateway::ISG],
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->checkRefundsFile($mail->viewData['signed_url']);

            $this->assertCount(1, $mail->attachments);

            return true;
        });
    }

    protected function checkRefundsFile($filePath)
    {
        $fileContents = \file($filePath);

        foreach ($fileContents as &$txtString)
        {
            $txtString = explode('|', $txtString);
        }

        $this->assertCount(2, $fileContents);

        $this->assertCount(8, $fileContents[0]);
    }
}
