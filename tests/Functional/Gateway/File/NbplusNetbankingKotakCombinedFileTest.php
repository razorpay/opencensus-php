<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Mail\Gateway\DailyFile;
use RZP\Tests\Functional\Payment\StaticCallbackNbplusGatewayTest;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingKotakCombinedFileTest extends StaticCallbackNbplusGatewayTest
{

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingKotakCombinedFileTestData.php';

        NbPlusPaymentServiceNetbankingTest::setUp();

        $this->bank = "KKBK";

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_kotak_terminal');

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }

    public function testGenerateKotakCombinedFileForNonTpv()
    {
        Mail::fake();

        $payment = $this->doAuthAndCapturePayment($this->payment);

        $transaction1 = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction1['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $this->refundPayment($payment['id']);

        $paymentEntity1 = $this->getDbLastPayment();

        $refundEntity1 = $this->getDbLastRefund();

        $this->doAuthAndCapturePayment($this->payment);

        $transaction2 = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction2['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $this->refundPayment($transaction2['entity_id'], 500);

        $paymentEntity2 = $this->getDbLastPayment();

        $refundEntity2 = $this->getDbLastRefund();

        $this->assertEquals('refunded', $paymentEntity1['status']);
        $this->assertEquals('captured', $paymentEntity2['status']);
        $this->assertEquals(3, $paymentEntity1['cps_route']);
        $this->assertEquals(3, $paymentEntity2['cps_route']);
        $this->assertEquals('full', $paymentEntity1['refund_status']);
        $this->assertEquals('partial', $paymentEntity2['refund_status']);
        $this->assertEquals(1, $refundEntity1['is_scrooge']);
        $this->assertEquals(1, $refundEntity2['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity1, $refundEntity2]);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull($content[File\Entity::SENT_AT]);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getEntities('file_store', ['count' => 2], true);

        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 2,
            'items' => [
                [
                    'type' => 'kotak_netbanking_claim',
                    'location' => 'Kotak/Claims/Netbanking/Kotak_Netbanking_Claim_OSRAZORPAY_test' . '_' . $time . '.txt',
                ],
                [
                    'type' => 'kotak_netbanking_refund',
                    'location' => 'Kotak/Refund/Netbanking/Kotak_Netbanking_Refund_OSRAZORPAY_test' . '_' . $time . '.txt',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $file);

        Mail::assertSent(DailyFile::class, function ($mail) use ($paymentEntity1, $paymentEntity2, $refundEntity1, $refundEntity2)
        {
            $today = Carbon::now(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject'  => 'Kotak Netbanking claims and refund files for '.$today,
                'bankName' => 'Kotak',
                'amount' => [
                    'claims'  => 1000,
                    'refunds' => 505,
                    'total'   => 495
                ],
            ];

            $files = $this->getEntities('file_store', ['count' => 2], true);

            $claimfilePath = storage_path('files/filestore') . '/' . $files['items']['0']['location'];

            $refundfilePath = storage_path('files/filestore') . '/' . $files['items']['1']['location'];

            $this->assertClaimFileContents($claimfilePath, [$paymentEntity1, $paymentEntity2]);

            $this->assertRefundFileContents($refundfilePath, [$refundEntity1, $refundEntity2], [$paymentEntity1, $paymentEntity2]);

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            return true;
        });
    }
    public function testGenerateKotakCombinedFileForTpv()
    {
        Mail::fake();
        $terminalAttrs = [
            'id'               => 'TpvNbKotakTmnl',
            'network_category' => 'securities',
            'tpv'              => 1
        ];

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_kotak_terminal', $terminalAttrs);

        $payment = $this->makeTpvPayment();

        $transaction1 = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction1['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $this->refundPayment($payment['id']);

        $refundTransaction1 = $this->getLastEntity('transaction', true);

        $paymentEntity1 = $this->getDbLastPayment();

        $refundEntity1 = $this->getDbLastRefund();

        $order = $this->createTpvOrderForBank($this->bank);

        $this->payment['order_id'] = $order['id'];

        $payment = $this->doAuthAndCapturePayment($this->payment);

        $transaction2 = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction2['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $this->refundPayment($payment['id'], 500);

        $refundTransaction2 = $this->getLastEntity('transaction', true);

        $paymentEntity2 = $this->getDbLastPayment();

        $refundEntity2 = $this->getDbLastRefund();

        $this->fixtures->merchant->disableTpv();

        $this->assertEquals('refunded', $paymentEntity1['status']);
        $this->assertEquals('captured', $paymentEntity2['status']);
        $this->assertEquals(3, $paymentEntity1['cps_route']);
        $this->assertEquals(3, $paymentEntity2['cps_route']);
        $this->assertEquals('full', $paymentEntity1['refund_status']);
        $this->assertEquals('partial', $paymentEntity2['refund_status']);
        $this->assertEquals(1, $refundEntity1['is_scrooge']);
        $this->assertEquals(1, $refundEntity2['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity1, $refundEntity2]);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull($content[File\Entity::SENT_AT]);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getEntities('file_store', ['count' => 2], true);

        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 2,
            'items' => [
                [
                    'type' => 'kotak_netbanking_claim',
                    'location' => 'Kotak/Claims/Netbanking/Kotak_Netbanking_Claim_OTRAZORPAY_test' . '_' . $time . '.txt',
                ],
                [
                    'type' => 'kotak_netbanking_refund',
                    'location' => 'Kotak/Refund/Netbanking/Kotak_Netbanking_Refund_OTRAZORPAY_test' . '_' . $time . '.txt',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $file);

        Mail::assertSent(DailyFile::class, function ($mail) use ($paymentEntity1, $paymentEntity2, $refundEntity1, $refundEntity2)
        {
            $today = Carbon::now(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject'  => 'Kotak Netbanking claims and refund files for '.$today,
                'bankName' => 'Kotak',
                'amount' => [
                    'claims'  => 1000,
                    'refunds' => 505,
                    'total'   => 495
                ],
            ];

            $files = $this->getEntities('file_store', ['count' => 2], true);

            $claimfilePath = storage_path('files/filestore') . '/' . $files['items']['0']['location'];

            $refundfilePath = storage_path('files/filestore') . '/' . $files['items']['1']['location'];

            $this->assertClaimFileContents($claimfilePath, [$paymentEntity1, $paymentEntity2]);

            $this->assertRefundFileContents($refundfilePath, [$refundEntity1, $refundEntity2], [$paymentEntity1, $paymentEntity2]);

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            return true;
        });
    }

    protected function assertRefundFileContents($filePath, $refunds, $payments): void
    {
        $this->assertTrue(file_exists($filePath));

        $fileData = file_get_contents($filePath);

        $str = explode("\r\n", $fileData);

        for ($i = 1; $i <= count($refunds); $i++)
        {
            $row = str_getcsv($str[$i], '|');

            $this->assertEquals($row[2], $this->getFormattedDate($payments[$i-1]['authorized_at'], 'd-M-Y') );

            $this->assertEquals($row[3], $payments[$i-1]['id']);

            $this->assertEquals($row[4], (float)$this->getFormattedAmount($refunds[$i-1]['amount']));

            $this->assertNotEmpty($row[5]);

            $this->assertCount(6, $row);
        }

        $refundTransaction = $this->getLastEntity('transaction', true);
        $this->assertNotNull($refundTransaction['reconciled_at']);
    }

    protected function assertClaimFileContents($filePath, $payments): void
    {
        $this->assertTrue(file_exists($filePath));

        $fileData = file_get_contents($filePath);

        $str = explode("\r\n", $fileData);
        for ($i = 0; $i < count($payments); $i++)
        {
            $row = str_getcsv($str[$i], '|');

            $this->assertEquals($row[2], $this->getFormattedDate($payments[$i]['created_at'], 'd-M-Y') );

            $this->assertEquals($row[3], $payments[$i]['id']);

            $this->assertEquals($row[4], (float)$this->getFormattedAmount($payments[$i]['amount']));

            $this->assertNotEmpty($row[5]);

            $this->assertCount(6, $row);
        }
    }

    protected function getFormattedDate($date, $format): string
    {
        return Carbon::createFromTimestamp($date, Timezone::IST)->format($format);
    }

    protected function getFormattedAmount($amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
    protected function makeTpvPayment()
    {
        $this->fixtures->merchant->enableTPV();

        $order = $this->createTpvOrderForBank($this->bank);

        $payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $payment['order_id'] = $order['id'];

        $payment = $this->doAuthAndCapturePayment($payment);

        $this->fixtures->merchant->disableTpv();

        return $payment;
    }

    protected function createTpvOrderForBank($bank)
    {
        $request = [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'netbanking',
                'account_number' => '0040304030403040',
                'bank'           => $bank,
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ];

        $this->ba->privateAuth();

        $content = $this->makeRequestAndGetContent($request);

        $this->ba->publicAuth();

        return $content;
    }
}
