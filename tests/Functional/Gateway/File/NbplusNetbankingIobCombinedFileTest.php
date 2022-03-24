<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Excel;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Models\Payment\Refund;
use RZP\Excel\Import as ExcelImport;
use RZP\Models\Payment\Entity as Payment;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingIobCombinedFileTest extends NbPlusPaymentServiceNetbankingTest
{
    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingIobCombinedFileTestData.php';

        parent::setUp();

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_iob_terminal');

        $this->bank = 'IOBA';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }

    public function testNetbankingIobCombinedFile()
    {
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

        $date = Carbon::now(Timezone::IST)->format('Ymd');

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 1,
            'items' => [
                [
                    'type' => 'iob_netbanking_refund',
                    'location' => 'Iob/Refund/Netbanking/Batch_RAZOR_IOB_Refund_' . $date . '.xls',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        Mail::assertSent(DailyFileMail::class, function ($mail)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Iob Netbanking claims and refund files for '.$date,
                'amount' => [
                    'claims'  =>  "1500.00",
                    'refunds' =>  "1100.00",
                    'total'   =>  "400.00"
                ],
                'count' => [
                    'claims'  => 3,
                    'refunds' => 3,
                    'total'   => 6
                ],
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->assertCount(1, $mail->attachments);

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

        $row = (new ExcelImport)->toArray($filePath)[0];

        for ($i = 0; $i < 3; $i++)
        {
            $this->assertEquals((int)number_format($row[$i]['refund_amount_rs_ps'] * 100, 0, '.', ''), $refundEntities[$i]['amount']);
            $this->assertEquals($row[$i]['pgi_reference_no'], $refundEntities[$i]['payment_id']);
            $this->assertEquals($row[$i]['refund_id'], $refundEntities[$i]['id']);
        }
    }}
