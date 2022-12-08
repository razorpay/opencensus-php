<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingBobCombinedFileTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected $terminal;

    protected $bank = 'BARB_R';

    protected function setUp(): void
    {
        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingBobCombinedFileTestData.php';

        parent::setUp();

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_bob_terminal');

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);

        $this->markTestSkipped('this flow is deprecated and is moved to nbplus service');
    }

    public function testNetbankingBobCombinedFile()
    {
        Mail::fake();

        $payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $payment = $this->doAuthAndCapturePayment($payment);

        $refund = $this->refundPayment($payment['id']);

        $refundEntity = $this->getDbLastEntity('refund');

        // Netbanking Bob refunds have moved to scrooge
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

        $date = Carbon::now(Timezone::IST)->format('d-m-Y');

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 2,
            'items' => [
                [
                    'type' => 'bob_netbanking_claims',
                    'location' => 'Bob/Claims/Netbanking/BOB_Netbanking_Claims_test' . '_' . $date . '.txt'
                ],
                [
                    'type' => 'bob_netbanking_refund',
                    'location' => 'Bob/Refund/Netbanking/BOB_Netbanking_Refunds_test' . '_' . $date . '.txt'
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        Mail::assertSent(DailyFileMail::class, function ($mail)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Bob Netbanking claims and refund files for '.$date,
                'amount' => [
                    'claims'  => 0,
                    'refunds' => '500.00',
                    'total'   => '-500.00'
                ],
                'count' => [
                    'claims'  => 0,
                    'refunds' => 1,
                    'total'   => 1
                ],
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->checkRefundsFile($mail->viewData['refundsFile']);

            // $this->checkClaimsFile($mail->viewData['claimsFile']);

            $this->assertCount(1, $mail->attachments);

            //
            // Marking netbanking transaction as reconciled after sending in bank file
            //
            $refundTransaction = $this->getLastEntity('transaction', true);

            $this->assertNotNull($refundTransaction['reconciled_at']);

            return true;
        });
    }


    protected function checkRefundsFile(array $refundFileData)
    {
        $refundsFileContents = file($refundFileData['url']);

        $this->assertCount(2, $refundsFileContents);

        // Since there is no delimiter and the number of characters of the payment id remains the same,
        // we can check the total character count in the refund file's line
        $this->assertEquals(63, strlen($refundsFileContents[1]));
    }

    protected function checkClaimsFile(array $claimsFileData)
    {
        $claimsFileContents = file($claimsFileData['url']);

        $this->assertCount(2, $claimsFileContents);

        $claimsFileLine1 = explode('|', $claimsFileContents[1]);

        $this->assertCount(4, $claimsFileLine1);
    }
}
