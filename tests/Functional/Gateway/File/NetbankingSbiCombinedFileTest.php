<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Mockery;
use Carbon\Carbon;

use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Gateway\Netbanking\Sbi\EMandate\EmandateSbiTestTrait;

class NetbankingSbiCombinedFileTest extends TestCase
{
    use PaymentTrait;
    use EmandateSbiTestTrait;
    use DbEntityFetchTrait;

    // For making an e-mandate Payment
    const ACCOUNT_NUMBER    = '12345678901234';
    const IFSC              = 'SBIN0000001';
    const NAME              = 'Test account';
    const ACCOUNT_TYPE      = 'savings';

    protected $terminal;

    protected function setUp(): void
    {
        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingSbiCombinedFileTestData.php';

        parent::setUp();

        $this->setMockGatewayTrue();

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_sbi_terminal');

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);

        $this->app['rzp.mode'] = Mode::TEST;
        $nbPlusService = Mockery::mock('RZP\Services\Mock\NbPlus\Netbanking', [$this->app])->makePartial();
        $this->app->instance('nbplus.payments', $nbPlusService);
    }

    public function testGenerateCombinedFile()
    {
        Mail::fake();

        $this->bank = "SBIN";

        $this->createClaimAndRefundPayment($this->bank);

        $refund = $this->getDbLastRefund();

        $this->assertTrue($refund->getGatewayRefunded());
        $this->assertEquals(1, $refund->getReference3());
        $this->assertEquals('processed', $refund->getStatus());

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refund]);

        $content = $this->generateFiles();

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull($content[File\Entity::SENT_AT]);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $this->performPostFileGenerationAssertions();
    }

    public function testGenerateCombinedFileDirectSettlementTerminal()
    {
        Mail::fake();

        $this->fixtures->terminal->edit($this->terminal->getId(), [
            'type' => [
                'direct_settlement_with_refund' => '1',
            ],
        ]);

        $payment = $this->getDefaultNetbankingPaymentArray('SBIN');

        $payment = $this->doAuthPayment($payment);

        $this->updateAuthorizedAtOfPayment($payment['razorpay_payment_id']);

        $refund = $this->refundPayment($payment['razorpay_payment_id']);

        $this->updateCreatedAtOfRefund($refund['id']);

        $refund = $this->getDbLastRefund();

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refund]);

        $this->assertTrue($refund->getGatewayRefunded());
        $this->assertEquals(1, $refund->getReference3());
        $this->assertEquals('processed', $refund->getStatus());

        $content = $this->generateFiles();

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull($content[File\Entity::SENT_AT]);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $files = $this->getEntities('file_store', ['count' => 1], true);

        $rfDate = Carbon::now(Timezone::IST)->format('d.m.Y');

        $expectedFilesContent = [
            'entity' => 'collection',
            'count'  => 1,
            'items'  => [
                [
                    'type'     => 'sbi_netbanking_refund',
                    'location' => 'Sbi/Refund/Netbanking/RZPY_SBI_Refund' . '_' . $rfDate . '.txt'
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        Mail::assertSent(DailyFileMail::class, function ($mail)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Sbi Netbanking claims and refund files for '. $date,
                'amount' => [
                    'claims'  => 0,
                    'refunds' => 500,
                    'total'   => -500,
                ],
                'count' => [
                    'claims'  => 0,
                    'refunds' => 1,
                ]
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->checkRefundsFile($mail->viewData['refundsFile']);

            $this->assertCount(1, $mail->attachments);

            return true;
        });
    }

    public function testGenerateCombinedFileForSubsidiaryBanks()
    {
        Mail::fake();

        $bank = "SBBJ";

        $this->createClaimAndRefundPayment($bank);

        $refund = $this->getDbLastRefund();

        $this->assertTrue($refund->getGatewayRefunded());
        $this->assertEquals(1, $refund->getReference3());
        $this->assertEquals('processed', $refund->getStatus());

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refund]);

        $content = $this->generateFiles();

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull($content[File\Entity::SENT_AT]);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $this->performPostFileGenerationAssertions();
    }

    public function testMultipleRefundsSeqNo()
    {
        $payments  = [];
        $refunds   = [];
        $seqNoList = [];
        $expected  = [];

        for ($i = 0; $i < 2; $i++)
        {
            $payment = $this->getDefaultNetbankingPaymentArray('SBIN');

            $payments[] = $this->doAuthAndCapturePayment($payment);
        }

        // full refund
        $refunds[] = $this->refundPayment($payments[0]['id']);

        // partial refunds
        $refunds[] = $this->refundPayment($payments[1]['id'], 10000);
        $refunds[] = $this->refundPayment($payments[1]['id'], 10000);

        // expected output
        $expected[$refunds[0]['id']] = 1;
        $expected[$refunds[1]['id']] = 1;
        $expected[$refunds[2]['id']] = 2;

        foreach ($refunds as $refund)
        {
            $seqNoList[$refund['id']] = ($this->getDbEntityById('refund', $refund['id']))->getReference3();
        }

        $this->assertArraySelectiveEquals($expected, $seqNoList);
    }

    public function testClaimFileWithEmandatePayment()
    {
        $this->markTestSkipped('the test would be fixed asap, skipping for now as its affecting developers');

        Mail::fake();

        $this->createClaimAndRefundPayment();

        $this->setUpEmandate();

        $this->createEmandatePayment();

        $content = $this->generateFiles();

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull($content[File\Entity::SENT_AT]);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $this->performPostFileGenerationAssertions();
    }

    protected function checkRefundsFile(array $refundsFileData)
    {
        $this->assertFileExists($refundsFileData['url']);

        $refundsFileContents = file($refundsFileData['url']);

        $this->assertCount(1, $refundsFileContents);

        $refundsFileRow = explode('|', $refundsFileContents[0]);

        $this->assertCount(6, $refundsFileRow);

        $this->assertEquals(500, $refundsFileRow[4]);
    }

    protected function createClaimAndRefundPayment($bank = "SBIN")
    {
        $payment = $this->getDefaultNetbankingPaymentArray($bank);

        $payment = $this->doAuthAndCapturePayment($payment);

        $this->updateAuthorizedAtOfPayment($payment['id']);

        $this->refundPayment($payment['id']);

        $refund = $this->getDbLastRefund();

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refund]);

        $this->updateCreatedAtOfRefund($refund['id']);
    }

    protected function createEmandatePayment()
    {
        $this->payment = $this->getEmandateNetbankingRecurringPaymentArray('SBIN');

        $this->payment['bank_account'] = [
            'account_number'    => self::ACCOUNT_NUMBER,
            'ifsc'              => self::IFSC,
            'name'              => self::NAME,
            'account_type'      => self::ACCOUNT_TYPE,
        ];

        unset($this->payment['card']);

        $registerPayments[] = [
            'payment' => $this->createRegistrationPayment(),
            'status'  => 'SUCCESS',
            'umrn'    => '111111111111111'
        ];

        $this->updateAuthorizedAtOfPayment($registerPayments[0]['payment']['id']);

        $registerSuccessFile = $this->getRegisterSuccessExcel($registerPayments);
        $batch = $this->uploadBatchFile($registerSuccessFile, 'register');
        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('created', $batch['status']);
    }

    protected function generateFiles()
    {
        $this->ba->adminAuth();

        $content = $this->startTest();

        return $content['items'][0];
    }

    protected function setUpEmandate()
    {
        $this->fixtures->create('customer');

        $this->fixtures->merchant->addFeatures([Feature\Constants::CHARGE_AT_WILL]);

        $this->fixtures->merchant->enableEmandate();

        $this->fixtures->create('terminal:shared_emandate_sbi_terminal');
    }

    protected function performPostFileGenerationAssertions()
    {
        $files = $this->getEntities('file_store', ['count' => 2], true);

        $date = Carbon::now(Timezone::IST)->format('dmY');

        $rfDate = Carbon::now(Timezone::IST)->format('d.m.Y');

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 2,
            'items' => [
                [
                    'type' => 'sbi_netbanking_claim',
                    'location' => 'Sbi/Claim/Netbanking/SBI_CLAIM' . '_' . $date . '.txt'
                ],
                [
                    'type' => 'sbi_netbanking_refund',
                    'location' => 'Sbi/Refund/Netbanking/RZPY_SBI_Refund' . '_' . $rfDate . '.txt'
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        Mail::assertSent(DailyFileMail::class, function ($mail)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Sbi Netbanking claims and refund files for '. $date,
                'amount' => [
                    'claims'  => 500,
                    'refunds' => 500,
                    'total'   => 0,
                ],
                'count'   => [
                    'claims'  => 1,
                    'refunds' => 1,
                ]
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->checkRefundsFile($mail->viewData['refundsFile']);

            $this->assertCount(2, $mail->attachments);

            return true;
        });
    }

    protected function updateAuthorizedAtOfPayment($paymentId)
    {
        $this->fixtures->stripSign($paymentId);

        // setting authorized at to 8am. Payments are picked from 8pm to 8pm cycle.
        $authorizedAt = Carbon::today(Timezone::IST)->addHours(8)->getTimestamp();

        $this->fixtures->edit(
            'payment',
            $paymentId,
            [
                'authorized_at' => $authorizedAt,
            ]);

        return $paymentId;
    }

    protected function updateCreatedAtOfRefund($refundId)
    {
        $this->fixtures->stripSign($refundId);

        // setting created at to 8am. refunds are picked from 8pm to 8pm cycle.
        $createdAt = Carbon::today(Timezone::IST)->addHours(8)->getTimestamp();

        $this->fixtures->edit(
            'refund',
            $refundId,
            [
                'created_at' => $createdAt,
            ]);

        return $refundId;
    }
}
