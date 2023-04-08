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
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingUbiCombinedFileTest extends NbPlusPaymentServiceNetbankingTest
{
    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingUbiCombinedFileTestData.php';

        parent::setUp();

        $this->bank = 'UBIN';

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_ubi_terminal');

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }

    public function testNetbankingUbiCombinedFile()
    {
        Queue::fake();

        Mail::fake();

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

        $file = $this->getLastEntity(ConstantsEntity::FILE_STORE, true);

        $this->checkRefundsFile($file, $refunds);

        $files = $this->getEntities('file_store', [
            'count' => 1
        ], true);

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 1,
            'items' => [
                [
                    'type' => 'ubi_netbanking_refund',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        Queue::assertPushed(BeamJob::class, 1);

        Queue::assertPushedOn('beam_test', BeamJob::class);

        Mail::assertSent(DailyFileMail::class, function ($mail) {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Ubi Netbanking claims and refund files for '.$date,
                'amount' => [
                    'claims'  =>  '1500.00',
                    'refunds' =>  '1100.00',
                    'total'   =>  '400.00'
                ],
                'count' => [
                    'claims'  => 3,
                    'refunds' => 3
                ],
            ];
            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->assertCount(0, $mail->attachments);

            return true;
        });
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

    protected function checkRefundsFile(array $refundFileData, $refundEntities)
    {
        $filePath = storage_path('files/filestore') . '/' . $refundFileData['location'];

        $this->assertTrue(file_exists($filePath));

        $refundsFileContents = file($filePath);

        $refund1 = substr($refundsFileContents[0],88,19);
        $refund2 = substr($refundsFileContents[1],88,19);
        $refund3 = substr($refundsFileContents[2],88,19);
        $refund4 = substr($refundsFileContents[3],88,19);

        $totalRefundAmount = $refund1 + $refund2 + $refund3;

        $this->assertEquals((int)number_format($refund1 , 0, '', ''), $refundEntities[0]['amount']);
        $this->assertEquals((int)number_format($refund2 , 0, '', ''), $refundEntities[1]['amount']);
        $this->assertEquals((int)number_format($refund3 , 0, '', ''), $refundEntities[2]['amount']);
        $this->assertEquals((int)number_format($refund4 , 0, '', ''), $totalRefundAmount);
    }
}
