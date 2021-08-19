<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Encryption\PGPEncryption;
use RZP\Excel\Import as ExcelImport;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingPnbCombinedFileTest extends NbPlusPaymentServiceNetbankingTest
{

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingPnbCombinedFileTestData.php';

        parent::setUp();

        $this->bank = 'PUNB_R';

        /**
         * @var array
         */
        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_pnb_terminal');

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }

    public function testNetbankingPnbCombinedFile()
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
            'count' => 2
        ], true);

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 2,
            'items' => [
                [
                    'type' => 'pnb_netbanking_claims',
                    'extension' => 'gpg',
                ],
                [
                    'type' => 'pnb_netbanking_refund',
                    'extension' => 'txt',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $file);

        Mail::assertSent(DailyFileMail::class, function ($mail) use ($transaction1, $transaction2, $refundTransaction1, $refundTransaction2)
        {
            $today = Carbon::now(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject'  => 'Pnb Netbanking claims and refund files for '.$today,
                'bankName' => 'Pnb',
                'amount' => [
                    'claims'  => '1000.00',
                    'refunds' => '505.00',
                    'total'   => '495.00'
                ],
            ];

            $files = $this->getEntities('file_store', [
                'count' => 2
            ], true);

            $claimfilePath = storage_path('files/filestore') . '/' . $files['items']['0']['location'];

            $refundfilePath = storage_path('files/filestore') . '/' . $files['items']['1']['location'];

            $this->assertClaimFileContents($claimfilePath, [$transaction1,$transaction2]);

            $this->assertRefundFileContents($refundfilePath, [$refundTransaction1, $refundTransaction2], [$transaction1,$transaction2]);

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            return true;
        });
    }

    protected function assertRefundFileContents($filePath,$refundTransaction,$transaction)
    {
        $this->assertTrue(file_exists($filePath));

        $fileData = file_get_contents($filePath);

        $str = explode("\r\n", $fileData);

        for ($i = 0; $i < 2; $i++)
        {
            $row = str_getcsv($str[$i], '|');

            $this->assertEquals($row[4], $this->getFormattedDate($refundTransaction[$i]['created_at'], 'Ymd') );

            $this->assertEquals($refundTransaction[$i]['amount'], (int)($row[2]*100));

            $this->assertEquals($row[5],$this->getFormattedAmount($transaction[$i]['amount']));

            $this->assertCount(7, $row);

            $transactionID = substr($refundTransaction[$i]['entity_id'], 5);
            $this->assertEquals($row[6], $transactionID);
        }

        $refundTransaction = $this->getLastEntity('transaction', true);
        $this->assertNotNull($refundTransaction['reconciled_at']);
    }

    protected function assertClaimFileContents($filePath, $transaction)
    {
        $this->assertTrue(file_exists($filePath));

        $fileData = file_get_contents($filePath);

        $config = $this->config['gateway.netbanking_pnb'];

        $pgpConfig = [
            PGPEncryption::PRIVATE_KEY  => trim(str_replace('\n', "\n", $config['recon_key'])),
            PGPEncryption::PASSPHRASE  => $config['recon_passphrase'],
        ];

        $res = new PGPEncryption($pgpConfig);

        $decryptedText = $res->decrypt($fileData);

        file_put_contents($filePath, $decryptedText);

        $claimSheet = (new ExcelImport)->toArray($filePath, null, \Maatwebsite\Excel\Excel::XLSX)[0];

        $i=0;
        foreach ($claimSheet as $claim)
        {
            $this->assertEquals((int)($claim['amount']*100), $transaction[$i]['amount']);

            $this->assertEquals($this->getFormattedDate($claim['date'],'Ymd'),$this->getFormattedDate($transaction[$i]['created_at'],'Ymd'));

            $this->assertCount(7, $claim);

            $transactionID = substr($transaction[$i]['entity_id'], 4);
            $this->assertEquals($transactionID, $claim['aggregator_refernce_no']);

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
}
