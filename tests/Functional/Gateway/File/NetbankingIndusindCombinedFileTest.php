<?php

namespace RZP\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingIndusindCombinedFileTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/NebtankingIndusindCombinedFileTestData.php';

        $this->markTestSkipped('this flow is depricated and is moved to nbplus service');

        parent::setUp();

        $this->fixtures->create('terminal:shared_netbanking_indusind_terminal');

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);
    }

    public function testGenerateCombinedFile()
    {
        Mail::fake();

        $payment = $this->getDefaultNetbankingPaymentArray('INDB');

        $payment = $this->doAuthAndCapturePayment($payment);

        $refund = $this->refundPayment($payment['id']);

        $refundEntity = $this->getDbLastEntity('refund');

        // Netbanking Indusind refunds have moved to scrooge
        $this->assertEquals(1, $refundEntity['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity]);

        $this->ba->adminAuth();

        $content = $this->startTest();
        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $files = $this->getEntities('file_store', [
            'count' => 2
        ], true);

        $time = Carbon::now(Timezone::IST)->format('dmY');

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 2,
            'items' => [
                [
                    'type' => 'indusind_netbanking_claim',
                    'location' => 'Indusind/Claims/Netbanking/PGClaimRazorpay' . $time . 'test.txt'
                ],
                [
                    'type' => 'indusind_netbanking_refund',
                    'location' => 'Indusind/Refund/Netbanking/PGRefundRAZORPAY' . $time . 'test.txt',
                ],
            ]
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        Mail::assertSent(DailyFileMail::class, function ($mail)
        {
            $testData = [
                'count' => [
                    'claims'  => 1,
                    'refunds' => 1
                ]
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->checkRefundsFile($mail->viewData['refundsFile']);

            $this->assertCount(2, $mail->attachments);

            //
            // Marking netbanking transaction as reconciled after sending in bank file
            //
            $refundTransaction = $this->getLastEntity('transaction',true);

            $this->assertNotNull($refundTransaction['reconciled_at']);

            return true;
        });
    }

    protected function checkRefundsFile(array $refundsFileData)
    {
        $this->assertFileExists($refundsFileData['url']);

        $refundsFileContents = file($refundsFileData['url']);

        $refundsFileRow = explode('|', $refundsFileContents[0]);

        $this->assertCount(6, $refundsFileRow);

        $this->assertEquals(trim($refundsFileRow[5]), '9999999999');

        $this->assertEquals($refundsFileRow[4], '500.00');
    }
}
