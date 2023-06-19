<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Excel;
use Queue;
use Mockery;
use Carbon\Carbon;

use RZP\Jobs\BeamJob;
use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Encryption\PGPEncryption;
use RZP\Tests\Functional\TestCase;
use RZP\Excel\Import as ExcelImport;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingPnbCombinedFileTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected $terminal;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingPnbCombinedFileTestData.php';

        parent::setUp();

        $this->bank = 'PUNB_R';

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_pnb_terminal');

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);
    }

    public function testNetbankingPnbCombinedFile()
    {
        Mail::fake();

        Queue::fake();

        $this->app['rzp.mode'] = Mode::TEST;
        $nbPlusService = Mockery::mock('RZP\Services\Mock\NbPlus\Netbanking', [$this->app])->makePartial();
        $this->app->instance('nbplus.payments', $nbPlusService);

        $paymentArray = $this->getDefaultNetbankingPaymentArray($this->bank);

        // Payment to fully refunded
        $payment1 = $this->doAuthAndCapturePayment($paymentArray);

        $transaction = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        //Payment to be refunded completely with 2 partial refunds on same day of payment
        $payment2 = $this->doAuthAndCapturePayment($paymentArray);

        $transaction = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        //Payment refunded partially on same day of payment and partially the next day before file generation
        $payment3 = $this->doAuthAndCapturePayment($paymentArray);

        $transaction = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        //Partial refund payment on same day of payment
        $payment4 = $this->doAuthAndCapturePayment($paymentArray);

        $transaction = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        //Payment created 6 days ago but refunded today
        $payment5 = $this->doAuthAndCapturePayment($paymentArray);

        $transaction = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->subDays(4)->timestamp
        ]);

        $payment = $this->getLastEntity('payment', true);

        $this->fixtures->edit('payment', $payment['id'], [
            'authorized_at' => Carbon::yesterday(Timezone::IST)->subDays(5)->timestamp
        ]);

        // full refund
        $refundFull = $this->refundPayment($payment1['id']);
        $refundEntity1 = $this->getDbLastEntity('refund');

        //partial refund
        $refundPartial1 = $this->refundPayment($payment2['id'], 25000);
        $refundEntity2 = $this->getDbLastEntity('refund');

        $refundPartial2 = $this->refundPayment($payment2['id'], 25000);
        $refundEntity3 = $this->getDbLastEntity('refund');

        $refundPartial3 = $this->refundPayment($payment3['id'], 25000);
        $refundEntity4 = $this->getDbLastEntity('refund');

        $refundPartial4 = $this->refundPayment($payment3['id'], 25000);
        $refundEntity5 = $this->getDbLastEntity('refund');

        $createdAt = Carbon::tomorrow(Timezone::IST)->addHours(6)->timestamp;

        $this->fixtures->edit('refund', $refundEntity5['id'], ['created_at' => $createdAt]);

        $refundPartial5 = $this->refundPayment($payment4['id'], 25000);
        $refundEntity6 = $this->getDbLastEntity('refund');

        $refundFullOld = $this->refundPayment($payment5['id']);
        $refundEntity7 = $this->getDbLastEntity('refund');

        $this->ba->adminAuth();

        $this->assertEquals(1, $refundEntity1['is_scrooge']);
        $this->assertEquals(1, $refundEntity2['is_scrooge']);
        $this->assertEquals(1, $refundEntity3['is_scrooge']);
        $this->assertEquals(1, $refundEntity4['is_scrooge']);
        $this->assertEquals(1, $refundEntity6['is_scrooge']);
        $this->assertEquals(1, $refundEntity7['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity1, $refundEntity2, $refundEntity3, $refundEntity4 ,$refundEntity6, $refundEntity7]);

        $refundsToBeAsserted = [
            str_replace('rfnd_','',$refundFull['id']) => [
                'payment_id' =>str_replace('pay_','',$refundFull['payment_id']),
                'amount' =>$refundFull['amount'],
            ],
            str_replace('rfnd_','',$refundPartial1['id']) => [
                'payment_id' =>str_replace('pay_','',$refundPartial1['payment_id']),
                'amount' =>$refundPartial1['amount'],
            ],
            str_replace('rfnd_','',$refundPartial2['id']) => [
                'payment_id' =>str_replace('pay_','',$refundPartial2['payment_id']),
                'amount' =>$refundPartial2['amount'],
            ],
            str_replace('rfnd_','',$refundPartial3['id']) => [
                'payment_id' =>str_replace('pay_','',$refundPartial3['payment_id']),
                'amount' =>$refundPartial3['amount'],
            ],
            str_replace('rfnd_','',$refundPartial5['id']) => [
                'payment_id' =>str_replace('pay_','',$refundPartial5['payment_id']),
                'amount' =>$refundPartial5['amount'],
            ],
            str_replace('rfnd_','',$refundFullOld['id']) => [
                'payment_id' =>str_replace('pay_','',$refundFullOld['payment_id']),
                'amount' =>$refundFullOld['amount'],
            ],
        ];

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);

        $this->assertNotNull($content[File\Entity::SENT_AT]);

        $this->assertNull($content[File\Entity::FAILED_AT]);

        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $files = $this->getEntities('file_store', [
            'count' => 2
        ], true);

        $claimfilePath = storage_path('files/filestore') . '/' . $files['items']['0']['location'];

        $refundfilePath = storage_path('files/filestore') . '/' . $files['items']['1']['location'];

        $this->assertClaimFileContents($claimfilePath, [$payment1, $payment2, $payment3, $payment4]);

        $this->assertRefundFileContents($refundfilePath, $refundsToBeAsserted);

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 2,
            'items' => [
                [
                    'type' => 'pnb_netbanking_claims',
                ],
                [
                    'type' => 'pnb_netbanking_refund',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        Mail::assertSent(DailyFileMail::class, function ($mail) use ($payment1, $payment2) {
            $today = Carbon::now(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Pnb Netbanking claims and refund files for '.$today,
                'amount' => [
                    'claims'  =>  "2000.00",
                    'refunds' =>  "2000.00",
                    'total'   =>  "0.00"
                ]
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->assertCount(0, $mail->attachments);

            $refundTransaction = $this->getLastEntity('transaction', true);

            $this->assertNotNull($refundTransaction['reconciled_at']);

            return true;
        });

        Queue::assertPushed(BeamJob::class, 1);

        Queue::assertPushedOn('beam_test', BeamJob::class);
    }

    protected function assertRefundFileContents($filePath, $refundsToBeAsserted): void
    {
        $this->assertTrue(file_exists($filePath));

        $fileData = file_get_contents($filePath);

        $str = explode("\r\n", $fileData);

        for ($i = 0; $i < 6; $i++)
        {
            $row = str_getcsv($str[$i], '|');

            $this->assertTrue(array_key_exists($row[6],$refundsToBeAsserted));
            $this->assertEquals((int)number_format($row[2] * 100, 0, '', ''), $refundsToBeAsserted[$row[6]]['amount']);
            $this->assertEquals($row[0], $refundsToBeAsserted[$row[6]]['payment_id']);
        }
    }

    protected function assertClaimFileContents($filePath, $payments): void
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

        foreach ($claimSheet as $i => $claim)
        {
            $this->assertEquals(ltrim($payments[$i]['id'], 'pay_'), $claim['aggregator_refernce_no']);
            $this->assertEquals($this->getFormattedAmount($payments[$i]['amount']), $claim['amount']);
            $this->assertEquals('successful', $claim['status']);
        }
    }

    protected function getFormattedAmount($amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
