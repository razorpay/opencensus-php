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

    protected function setUp(): void
    {
        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/IsgRefundFileTestData.php';

        parent::setUp();

        $this->gateway = 'isg';

        $this->fixtures->create('terminal:bharat_qr_isg_terminal');

        $this->fixtures->merchant->addFeatures(['virtual_accounts', 'bharat_qr']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');
    }

    public function testIsgRefundFile()
    {
        $this->markTestSkipped();

        Mail::fake();

        $this->createBharatQrPayment($this->getPaymentContentData());

        $payment1 = $this->getLastEntity('payment', true);

        $fullRefund = $this->refundPayment($payment1['id']);

        $this->createBharatQrPayment($this->getPaymentContentData(257));

        $payment2 = $this->getLastEntity('payment', true);

        $partialRefund = $this->refundPayment($payment2['id'], 100);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $today = Carbon::now(Timezone::IST)->format('dmY');

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 2,
            'items' => [
                [
                    'type' => 'isg_summary',
                    'location' => 'Summary' . '_' . $today . '.txt',
                ],
                [
                    'type' => 'isg_refund',
                    'location' => 'Refund' . '_' . $today . '.csv',
                ],
            ],
        ];

        $files = $this->getEntities('file_store', ['count' => 2], true);

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        Mail::assertQueued(RefundFileMail::class, function ($mail)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $expectedSubject = RefundMailConstants::SUBJECT_MAP[Gateway::ISG] . $date;

            $this->assertEquals($expectedSubject, $mail->subject);

            $testData = [
                'count' => 2,
                'body'  => RefundMailConstants::BODY_MAP[Gateway::ISG],
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->checkRefundsFile($mail->viewData[0]['signed_url']);

            $this->checkSummaryFile($mail->viewData[1]['signed_url']);

            $this->assertCount(2, $mail->attachments);

            return true;
        });
    }

    protected function getPaymentContentData($amount = 100)
    {
         $paymentContent = [
            'PRIMARY_ID'           => 'tobeset',
            'SECONDARY_ID'         => 'reference_id',
            'MERCHANT_PAN'         => '4403844012084006',
            'TXN_ID'               => random_int(1111111111111,9999999999999),
            'TXN_DATE_TIME'        => Carbon:: now()->format('Y-m-d H:i:s'),
            'TXN_AMOUNT'           => $this->formatAmount($amount),
            'AUTH_CODE'            => 'ab3456',
            'RRN'                  => random_int(111111111111,999999999999),
            'CONSUMER_PAN'         => '4012001037141112',
            'STATUS_CODE'          => '00',
            'STATUS_DESC'          => 'Transaction Approved',
         ];

         return $paymentContent;
    }

    protected function checkRefundsFile($filePath)
    {
        $fileContents = \file($filePath);

        foreach ($fileContents as &$txtString)
        {
            $txtString = explode(',', $txtString);
        }

        $this->assertCount(3, $fileContents);

        $this->assertCount(8, $fileContents[0]);
    }

    protected function checkSummaryFile($filePath)
    {
        $fileContent = \file($filePath);

        $expectedFileContent = 'Total Refunds Records : 2';

        $this->assertEquals($expectedFileContent, $fileContent[0]);
    }

    public function formatAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
