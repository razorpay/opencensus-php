<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;

use Mockery;
use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Services\Mock\BeamService;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingAxisCombinedFileTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected $terminal;

    protected function setUp(): void
    {
        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingAxisCombinedFileTestData.php';

        $this->gateway = 'netbanking_axis';

        parent::setUp();

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_axis_terminal');

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);

        $this->app['rzp.mode'] = Mode::TEST;
        $nbPlusService = Mockery::mock('RZP\Services\Mock\NbPlus\Netbanking', [$this->app])->makePartial();
        $this->app->instance('nbplus.payments', $nbPlusService);
    }

    public function testNetbankingAxisCombinedFile()
    {
        Mail::fake();

        $payment = $this->getDefaultNetbankingPaymentArray('UTIB');

        $payment = $this->doAuthAndCapturePayment($payment);

        $refund = $this->refundPayment($payment['id']);

        $refundEntity = $this->getDbLastEntity('refund');

        // Netbanking Axis refunds have moved to scrooge
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

        $time = Carbon::now(Timezone::IST)->format('Ymd');

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 2,
            'items' => [
                [
                    'type' => 'axis_netbanking_claims',
                    'location' => 'Axis/Claims/Netbanking/IConnect_Claim_RAZORPAY' . '_' . $time . '_' . 'test_1.txt'
                ],
                [
                    'type' => 'axis_netbanking_refund',
                    'location' => 'Axis/Refund/Netbanking/IConnect_Refund_RAZORPAY' . '_' . $time . '_' . 'test_1.txt',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        Mail::assertSent(DailyFileMail::class, function ($mail)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Axis Netbanking claims and refund files for '.$date,
                'amount' => [
                    'claims'  => 500,
                    'refunds' => 500,
                    'total'   => 0
                ],
                'count' => [
                    'claims'  => 1,
                    'refunds' => 1,
                    'total'   => 2
                ],
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->checkRefundsFile($mail->viewData['refundsFile']);

            $this->checkClaimsFile($mail->viewData['claimsFile']);

            $this->assertCount(2, $mail->attachments);

            //
            // Marking netbanking transaction as reconciled after sending in bank file
            //
            $refundTransaction = $this->getLastEntity('transaction', true);

            $this->assertNotNull($refundTransaction['reconciled_at']);

            return true;
        });
    }

    public function testNetbankingAxisCombinedFileWithTermTypeCorp()
    {
        Mail::fake();

        $this->fixtures->create('terminal:shared_netbanking_axis_corp_terminal');
        $this->fixtures->merchant->addFeatures('corporate_banks');

        $testData = $this->testData['testNetbankingAxisCombinedFileForCorporate'];

        $this->axisNbCombinedTestForCorporate($testData);
    }

    public function testNetbankingAxisCombinedFileWithTermTypeBoth()
    {
        Mail::fake();

        $this->fixtures->create(
            'terminal:shared_netbanking_axis_corp_terminal',
            ['corporate' => 2]
        );

        $this->fixtures->merchant->addFeatures('corporate_banks');

        $testData = $this->testData['testNetbankingAxisCombinedFileForCorporate'];

        $this->axisNbCombinedTestForCorporate($testData);
    }

    protected function axisNbCombinedTestForCorporate($testData)
    {
        $payment = $this->getDefaultNetbankingPaymentArray('UTIB_C');

        $this->mockServerContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'verify')
                {
                    $content['type'] = 'corporate';
                }
            });

        $payment = $this->doAuthAndCapturePayment($payment);

        $this->refundPayment($payment['id']);

        $refundEntity = $this->getDbLastEntity('refund');

        // Netbanking Axis refunds have moved to scrooge
        $this->assertEquals(1, $refundEntity['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity]);

        $this->ba->adminAuth();

        $content = $this->startTest($testData);

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $files = $this->getEntities('file_store', [
            'count' => 2
        ], true);

        $time = Carbon::now(Timezone::IST)->format('Ymd');

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 2,
            'items' => [
                [
                    'type' => 'axis_netbanking_claims',
                    'location' => 'Axis/Claims/Netbanking/IConnect_Claim_RAZORPAY_CORP' . '_' . $time . '_' . 'test_1.txt'
                ],
                [
                    'type' => 'axis_netbanking_refund',
                    'location' => 'Axis/Refund/Netbanking/IConnect_Refund_RAZORPAY_CORP' . '_' . $time . '_' . 'test_1.txt',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        Mail::assertSent(DailyFileMail::class, function ($mail)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Corporate Axis Netbanking claims and refund files for '.$date,
                'amount' => [
                    'claims'  => 500,
                    'refunds' => 500,
                    'total'   => 0
                ],
                'count' => [
                    'claims'  => 1,
                    'refunds' => 1,
                    'total'   => 2
                ],
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->checkRefundsFile($mail->viewData['refundsFile']);

            $this->checkClaimsFile($mail->viewData['claimsFile']);

            $this->assertCount(2, $mail->attachments);

            return true;
        });
    }

    protected function checkRefundsFile(array $refundFileData)
    {
        $refundsFileContents = file($refundFileData['url']);

        $this->assertCount(2, $refundsFileContents);

        $refundsFileLine1 = explode('~~', $refundsFileContents[1]);

        $this->assertCount(8, $refundsFileLine1);
    }

    protected function checkClaimsFile(array $claimsFileData)
    {
        $claimsFileContents = file($claimsFileData['url']);

        $this->assertCount(2, $claimsFileContents);

        $claimsFileLine1 = explode('~~', $claimsFileContents[1]);

        $this->assertCount(7, $claimsFileLine1);
    }
}
