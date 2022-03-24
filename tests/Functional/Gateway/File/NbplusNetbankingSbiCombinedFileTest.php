<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingSbiCombinedFileTest extends NbPlusPaymentServiceNetbankingTest
{
    protected function setUp(): void
    {
        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingSbiCombinedFileTestData.php';

        parent::setUp();

        $this->setMockGatewayTrue();

        $this->bank = "SBIN";

        $this->gateway = 'netbanking_sbi';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_sbi_terminal');

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);
    }

    public function testGenerateCombinedFile()
    {
        Mail::fake();

        $this->createClaimAndRefundPayment($this->bank);

        $payment = $this->getDbLastPayment();

        $this->assertEquals(3, $payment->getCpsRoute());

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

        $payment = $this->doAuthPayment($this->payment);

        $this->updateAuthorizedAtOfPayment($payment['razorpay_payment_id']);

        $refund = $this->refundPayment($payment['razorpay_payment_id']);

        $this->updateCreatedAtOfRefund($refund['id']);

        $refund = $this->getDbLastRefund();

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refund]);

        $payment = $this->getDbLastPayment();

        $this->assertEquals(3, $payment->getCpsRoute());

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
        $payments    = [];
        $refunds     = [];
        $seqNoList   = [];
        $expected    = [];

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

    protected function checkRefundsFile(array $refundsFileData)
    {
        $this->assertFileExists($refundsFileData['url']);

        $refundsFileContents = file($refundsFileData['url']);

        $this->assertCount(1, $refundsFileContents);

        $refundsFileRow = explode('|', $refundsFileContents[0]);

        $this->assertCount(6, $refundsFileRow);

        $this->assertEquals($refundsFileRow[4], 500);
    }

    protected function createClaimAndRefundPayment($bank = "SBIN")
    {
        $payment = $this->getDefaultNetbankingPaymentArray($bank);

        $payment = $this->doAuthAndCapturePayment($payment);

        $this->updateAuthorizedAtOfPayment($payment['id']);

        $this->refundPayment($payment['id']);

        $refund =  $this->getDbLastRefund();

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refund]);

        $this->updateCreatedAtOfRefund($refund['id']);
    }

    protected function generateFiles()
    {
        $this->ba->adminAuth();

        $content = $this->startTest();

        return $content['items'][0];
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
