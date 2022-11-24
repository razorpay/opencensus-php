<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Excel;
use Queue;

use Carbon\Carbon;

use RZP\Jobs\BeamJob;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Models\Payment\Refund;
use RZP\Models\Payment\Entity as Payment;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingNsdlCombinedFileTest extends NbPlusPaymentServiceNetbankingTest
{
    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingNsdlCombinedFileTestData.php';

        parent::setUp();

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_nsdl_terminal');

        $this->bank = 'NSPB';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }

    public function testNetbankingNsdlRefundFile()
    {
        Mail::fake();

        Queue::fake();

        $this->mockBeam(function ($pushData, $intervalInfo, $mailInfo, $synchronous)
        {
            return [
                'failed' => null,
                'success' => $pushData['files'],
            ];
        });

        $refunds = $this->createRefundForFileGeneration();

        $this->setFetchFileBasedRefundsFromScroogeMockResponse($refunds);

        $this->assertEquals(1, $refunds[0][Refund\Entity::IS_SCROOGE]);
        $this->assertEquals(1, $refunds[1][Refund\Entity::IS_SCROOGE]);
        $this->assertEquals(1, $refunds[2][Refund\Entity::IS_SCROOGE]);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull($content[File\Entity::SENT_AT]);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $this->checkRefundsFile($refunds);

        $files = $this->getEntities('file_store', [
            'count' => 2
        ], true);

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 2,
            'items' => [
                [
                    'type' => 'nsdl_netbanking_claim',
                ],
                [
                    'type' => 'nsdl_netbanking_refund',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        //Queue::assertPushed(BeamJob::class, 1);

        //Queue::assertPushedOn('beam_test', BeamJob::class);

        $refundTransaction = $this->getLastEntity('transaction', true);

        $this->assertNotNull($refundTransaction['reconciled_at']);
    }

    protected function createRefundForFileGeneration()
    {
        return array_map(
            function($amount)
            {
                $this->doAuthCaptureAndRefundPayment($this->payment, $amount);

                $payment = $this->getDbLastEntityToArray(\RZP\Constants\Entity::PAYMENT);

                $this->fixtures->edit('transaction', $payment['transaction_id'], [
                    'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
                ]);

                $this->assertEquals($payment[Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);

                return $this->getDbLastRefund();
            },
            [50000, 50000, 10000]
        );
    }

    protected function checkRefundsFile($refundEntities)
    {
        $time = Carbon::now(Timezone::IST)->format('dmY');

        $filePath = storage_path('files/filestore') . '/Nsdl/Refund/Netbanking/pgtxnrefund_Et3lZ2p9bx_' . $time . '.txt';

        $this->assertTrue(file_exists($filePath));

        $fileData = file_get_contents($filePath);

        $str = explode("\r\n", $fileData);

        for ($i = 0; $i < 3; $i++)
        {
            $rows = str_getcsv($str[$i], ',');

            $this->assertEquals((int)number_format($rows[4] * 100, 0, '', ''), $refundEntities[$i]['amount']);

            $this->assertEquals($rows[3], $refundEntities[$i]['payment_id']);
        }
    }
}
