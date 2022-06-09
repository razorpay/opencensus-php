<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;
use RZP\Services\Scrooge;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Excel\Import as ExcelImport;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingDbsCombinedFileTest extends NbPlusPaymentServiceNetbankingTest
{

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingDbsCombinedFileTestData.php';

        parent::setUp();

        $this->bank = 'DBSS';

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_dbs_terminal');

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }

    public function testNetbankingDbsCombinedFile()
    {
        Mail::fake();

        $this->doAuthAndCapturePayment($this->payment);

        $transaction1 = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction1['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $this->refundPayment($transaction1['entity_id']);

        $refundTransaction1 = $this->getLastEntity('transaction', true);

        $paymentEntity1 = $this->getDbLastPayment();

        $refundEntity1 = $this->getDbLastRefund();

        $this->doAuthAndCapturePayment($this->payment);

        $transaction2 = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction2['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $this->refundPayment($transaction2['entity_id'], 500);

        $refundTransaction2 = $this->getLastEntity('transaction', true);

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

        $file = $this->getEntities('file_store', [
            'count' => 1
        ], true);

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 1,
            'items' => [
                [
                    'type' => 'dbs_netbanking_combined_unencrypted',
                ]
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $file);

        Mail::assertSent(DailyFileMail::class, function ($mail) use ($transaction1, $transaction2, $refundTransaction1, $refundTransaction2)
        {
            $today = Carbon::now(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject'  => 'Dbs Netbanking claims and refund files for '.$today,
                'bankName' => 'Dbs',
                'amount' => [
                    'claims'  => '1000.00',
                    'refunds' => '505.00',
                    'total'   => '495.00'
                ],
            ];

            $files = $this->getEntities('file_store', [
                'count' => 4
            ], true);

            $claimfilePath = storage_path('files/filestore') . '/' . $files['items']['2']['location'];

            $refundfilePath = storage_path('files/filestore') . '/' . $files['items']['3']['location'];

            $this->assertClaimFileContents($claimfilePath, [$transaction1,$transaction2]);

            $this->assertRefundFileContents($refundfilePath, [$refundTransaction1, $refundTransaction2], [$transaction1,$transaction2]);

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            return true;
        });
    }

    protected function assertRefundFileContents($filePath,$refundTransaction,$transaction)
    {
        $this->assertTrue(file_exists($filePath));

        $claimSheet = (new ExcelImport)->toArray($filePath, null, \Maatwebsite\Excel\Excel::XLSX)[0];

        $i=0;
        foreach ($claimSheet as $refund)
        {
            $this->assertEquals($refundTransaction[$i]['amount'], (int)($refund['transactionamount']*100));

            $transactionID = substr($refundTransaction[$i]['entity_id'], 5);
            $this->assertEquals($transactionID, $refund['merchant_order_id_razorpay_tran_ref_no']);

            $i++;
        }
    }

    protected function assertClaimFileContents($filePath, $transaction)
    {
        $this->assertTrue(file_exists($filePath));

        $claimSheet = (new ExcelImport)->toArray($filePath, null, \Maatwebsite\Excel\Excel::XLSX)[0];

        $i=0;
        foreach ($claimSheet as $claim)
        {
            $this->assertEquals((int)($claim['transactionamount']*100), $transaction[$i]['amount']);

            $this->assertCount(9, $claim);

            $transactionID = substr($transaction[$i]['entity_id'], 4);
            $this->assertEquals($transactionID, $claim['merchant_order_id_razorpay_tran_ref_no']);

            $i++;
        }
    }

    protected function getFormattedDate($date, $format)
    {
        return Carbon::createFromTimestamp($date, Timezone::IST)->format($format);
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function setFetchFileBasedRefundsFromScroogeMockResponse(array $refundEntities)
    {
        $scroogeResponse = [
            'code'     => 200,
            'body'     => [
                'data' => [],
            ],
        ];

        $scroogeResponseForRef1Update = json_decode('{
            "api_failed_count": 0,
            "api_failures": [],
            "scrooge_failed_count": 0,
            "scrooge_failures": [],
            "success_count": 1,
            "time_taken": 0.24121499061584473
        }', true);

        foreach ($refundEntities as $refundEntity)
        {
            $scroogeResponse['body']['data'][] = [
                'id'                => $refundEntity['id'],
                'amount'            => $refundEntity['amount'],
                'base_amount'       => $refundEntity['base_amount'],
                'payment_id'        => $refundEntity['payment_id'],
                'bank'              => $refundEntity->payment['bank'],
                'gateway'           => $refundEntity['gateway'],
                'currency'          => $refundEntity['currency'],
                'gateway_amount'    => $refundEntity['gateway_amount'],
                'gateway_currency'  => $refundEntity['gateway_currency'],
                'method'            => $refundEntity->payment['method'],
                'created_at'        => $refundEntity['created_at'],
                'reference1'        => $refundEntity['reference1'],
                'status'            => 'processed',
                'processed_source'  => 'GATEWAY_API',
            ];
        }

        $scroogeMock = $this->getMockBuilder(Scrooge::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getRefunds', 'bulkUpdateRefundReference1'])
            ->getMock();

        $this->app->instance('scrooge', $scroogeMock);

        $this->app->scrooge->method('getRefunds')
            ->willReturn($scroogeResponse);

        $this->app->scrooge->method('bulkUpdateRefundReference1')
            ->willReturn($scroogeResponseForRef1Update);
    }
}
