<?php

namespace RZP\Tests\Functional\Gateway\File;

use Excel;
use Queue;
use Carbon\Carbon;
use Mail;

use RZP\Jobs\BeamJob;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Models\Payment\Refund;
use RZP\Models\Payment\Entity as Payment;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;
use RZP\Tests\Functional\Payment\StaticCallbackNbplusGatewayTest;


class NbplusNetbankingUcoCombinedFileTest extends StaticCallbackNbplusGatewayTest
{
    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingUcoCombinedFileTestData.php';

        NbPlusPaymentServiceNetbankingTest::setUp();

        $this->bank = 'UCBA';

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_uco_terminal');

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }

    public function testNetbankingUcoCombinedFile()
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
            'count'  => 1,
            'items'  => [
                [
                    'type' => 'uco_netbanking_refund',
                    'extension' => 'txt',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $file);

        Mail::assertSent(DailyFileMail::class, function ($mail) use ($transaction1, $transaction2, $refundTransaction1, $refundTransaction2) {
            $today = Carbon::now(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject'  => 'Uco Netbanking claims and refund files for ' . $today,
                'bankName' => 'Uco',
                'amount'   => [
                    'claims'  => '1000.00',
                    'refunds' => '505.00',
                    'total'   => '495.00'
                ],
            ];

            $files = $this->getEntities('file_store', [
                'count' => 1
            ], true);


            $refundfilePath = storage_path('files/filestore') . '/' . $files['items']['0']['location'];


            $this->assertRefundFileContents($refundfilePath, [$refundTransaction1, $refundTransaction2], [$transaction1, $transaction2]);

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            return true;
        });
    }

    protected function assertRefundFileContents($filePath, $refundTransaction, $transaction)
    {

        $this->assertTrue(file_exists($filePath));

        $fileData = file_get_contents($filePath);

        $str = explode("\r\n", $fileData);

        for ($i = 0; $i < 1; $i++) {
            $row = str_getcsv($str[$i], ' ');

            $cnt = count($row) - 1;

            $this->assertEquals($row[$cnt], $this->getFormattedDate($refundTransaction[$i]['created_at'], 'd/m/Y'));

            for ($k = 0; $k < count($row); $k++) {
                if ($row[$k] != '') {
                    $row_new[] = $row[$k];
                }
            }

            $mystring = $row_new[2];

            $first = strtok($mystring, 'ref');

            $this->assertEquals($refundTransaction[$i]['amount'], (int)($first * 100));

            $this->assertEquals($first, $this->getFormattedAmount($transaction[$i]['amount']));


        }

        $refundTransaction = $this->getLastEntity('transaction', true);
        $this->assertNotNull($refundTransaction['reconciled_at']);
    }


    protected function getFormattedDate($date, $format)
    {
        return Carbon::createFromTimestamp($date, Timezone::IST)->format($format);
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
