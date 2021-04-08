<?php

namespace RZP\Tests\Functional\FileStore;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use Mail;
use Mockery;

use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class FileStoreTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/FileStoreTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->ba->privateAuth();
    }

    public function testRefundFile()
    {
        // this route is deprecated. files are now generated using 'gateway/files'
        $this->markTestSkipped();

        Mail::fake();
        // Make 3 test payments
        $this->createTestPayment();
        $this->createTestPayment();
        $this->createTestPayment();

        $payments = $this->getEntities('payment', [], true);

        $refundPayment = $this->refundPayment($payments['items'][2]['id'], 100);
        $refundPayment = $this->refundPayment($payments['items'][2]['id']);

        $this->editPaymentsAndRefunds();

        $content = $this->generateRefundsExcelForKkbkNB();

        $this->validateRefundFile($content);

        $this->assertFileStoreItems();

        $this->mockMail(500);
    }

    protected function createPayments($count)
    {
        foreach (range(0, $count) as $number)
        {
            $this->createTestPayment();
        }
    }

    protected function assertFileStoreItems()
    {
        $fileStoreItems = $this->getEntities('file_store', [], true);

        $fileStoreData = $fileStoreItems['items'][0];
        $expectedOutput = [
            'merchant_id'   => '100000Razorpay',
            'type'          => 'kotak_netbanking_refund',
            'extension'     => 'txt',
            'mime'          => 'text/plain',
            'store'         => 's3',
            'entity'        => 'file_store',
        ];

        $this->assertArraySelectiveEquals($expectedOutput, $fileStoreData);

        $this->assertEquals($fileStoreData['name'].'.'.$fileStoreData['extension'], $fileStoreData['location']);
    }

    protected function generateRefundsExcelForKkbkNB()
    {
        $this->ba->adminAuth();

        $request = array(
            'url' => '/refunds/excel',
            'method' => 'post',
            'content' => [
                'bank'   => 'KKBK',
                'method' => 'netbanking',
            ],
        );

        return $this->makeRequestAndGetContent($request);
    }

    protected function doNetbankingKotakAuthAndCapturePayment()
    {
        $payment = $this->getDefaultNetbankingPaymentArray('KKBK');

        $payment = $this->doAuthAndCapturePayment($payment);

        return $payment;
    }

    protected function createTestPayment()
    {
        $terminal = $this->fixtures->create('terminal:netbanking_kotak_terminal');

        $payment = $this->doNetbankingKotakAuthAndCapturePayment();
    }

    protected function editPaymentsAndRefunds()
    {
        $payments = $this->getEntities('payment', [], true);

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(10)->addMinutes(30)->timestamp;

        // Set payment dates to yesterday
        foreach ($payments['items'] as $payment)
        {
            $this->fixtures->edit('payment',
                                  $payment['id'],
                                  [
                                      'created_at' => $createdAt,
                                      'authorized_at' => $createdAt + 10,
                                      'captured_at' => $createdAt + 20
                                  ]);
        }

        $refunds = $this->getEntities('refund', [], true);

        // Mark refunds as created yesterday
        foreach ($refunds['items'] as $refund)
        {
            $this->fixtures->edit('refund',
                                  $refund['id'],
                                  ['created_at' => $createdAt + 40]);
        }
    }

    protected function mockMail($amount)
    {
        $date = Carbon::today(Timezone::IST)->format('d-m-Y');

        $testData = [
            'subject' => 'Kotak Netbanking claims and refund files for '.$date,
            'amount' => [
                'claims' => 0,
                'refunds' => $amount,
                'total' => ($amount * -1),
            ]
        ];

        Mail::assertQueued(DailyFileMail::class, function ($mail) use ($testData)
        {
            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            return true;
        });
    }

    protected function validateRefundFile($content)
    {
        $refundsFileUrl = $content['netbanking_kotak']['refunds']['nonTpv'];

        $refundsFileContents = file($refundsFileUrl);

        assert(count($refundsFileContents) === 3);

        $refundsFileName = explode('/', $refundsFileUrl);

        $refundsFileLine = explode('|', $refundsFileContents[0]);

        // Refund file should have name in the first line
        $refundsFileNameId = count($refundsFileName) - 1;

        assert($refundsFileName[$refundsFileNameId] === $refundsFileLine[0]);

        $refundsFileLine1 = explode('|', $refundsFileContents[1]);

        assert(count($refundsFileLine1) === 6);
    }
}
