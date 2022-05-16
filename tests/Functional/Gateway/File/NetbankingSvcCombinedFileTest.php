<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;

use phpseclib\Crypt\AES;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Models\Payment\Refund;
use RZP\Gateway\Base\AESCrypto;
use RZP\Models\Payment\Entity as Payment;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NetbankingSvcCombinedFileTest extends NbPlusPaymentServiceNetbankingTest
{
    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingSvcCombinedFileTestData.php';

        parent::setUp();

        $this->bank = 'SVCB';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_svc_terminal');
    }

    public function testNetbankingSvcCombinedFile()
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

        $this->assertNotNull(File\Entity::SENT_AT);

        $this->assertNull($content[File\Entity::FAILED_AT]);

        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity(ConstantsEntity::FILE_STORE, true);

        $this->assertRefundFileContents($file, $refunds);

        $files = $this->getEntities('file_store', ['count' => 1], true);

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 1,
            'items' => [
                [
                    'type' => 'svc_netbanking_refund',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        Mail::assertSent(DailyFileMail::class, function ($mail) {
            $today = Carbon::now(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Svc Netbanking claims and refund files for '.$today,
                'amount' => [
                    'claims'  =>  "1500.00",
                    'refunds' =>  "1100.00",
                    'total'   =>  "400.00"
                ],
                'count' => [
                    'claims'  => 3,
                    'refunds' => 3,
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

                $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

                $this->fixtures->edit('transaction', $payment['transaction_id'], [
                    'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
                ]);

                $this->assertEquals($payment[Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);

                return $this->getDbLastRefund();
            },
            [50000, 50000, 10000]
        );
    }

    protected function assertRefundFileContents($file, $refundEntities)
    {
        $filePath = storage_path('files/filestore') . '/' . $file['location'];

        $this->assertTrue(file_exists($filePath));

        $encryptedData = file_get_contents($filePath);

        $config = $this->config['gateway.netbanking_svc'];

        $key = $config['encryption_key'];

        $iv = $config['encryption_iv'];

        $masterKey = hex2bin(md5($key)); // nosemgrep :  php.lang.security.weak-crypto.weak-crypto

        $aes = new AESCrypto(AES::MODE_CBC, $masterKey, base64_decode($iv));

        $decryptedString = $aes->decryptString(hex2bin($encryptedData));

        $str = explode("\r\n", $decryptedString);

        for ($i = 0; $i < 3; $i++)
        {
            $row = str_getcsv($str[$i], '^');
            $this->assertEquals((int)number_format($row[3] * 100, 0, '', ''), $refundEntities[$i]['amount']);
            $this->assertEquals($row[1], $refundEntities[$i]['payment_id']);
        }

        unlink($filePath);
    }
}
