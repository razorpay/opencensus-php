<?php
namespace RZP\Tests\Functional\Gateway\Netbanking\Indusind;

use Mail;
use Excel;
use Carbon\Carbon;

use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Mail\Gateway\DailyFile;
use RZP\Reconciliator\RequestProcessor\Base;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Helpers\FileUploadTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingIndusindCombinedFileTest extends NbPlusPaymentServiceNetbankingTest
{
    use ReconTrait;
    use FileUploadTrait;
    /**
     * @var array
     */
    protected $terminal;
    /**
     * @var string
     */
    protected $bank;
    /**
     * @var array
     */
    protected $payment;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NetbankingIndusindGatewayTestData.php';

        parent::setUp();

        $this->bank = 'INDB';

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_indusind_terminal');

        $this->payment = $this->getDefaultNetbankingPaymentArray('INDB');
    }

    public function testNetbankingIndusindCombinedFile()
    {
        Mail::fake();

        $this->doAuthCaptureAndRefundPayment($this->payment);

        $paymentEntity1 = $this->getDbLastPayment();

        $this->fixtures->edit('transaction', $paymentEntity1->transaction->getId(), [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $refundEntity1 = $this->getDbLastRefund();

        $this->doAuthCaptureAndRefundPayment($this->payment, 500);

        $paymentEntity2 = $this->getDbLastPayment();

        $this->fixtures->edit('transaction', $paymentEntity2->transaction->getId(), [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

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

        $this->assertTrue($refundEntity1->transaction->isReconciled());
        $this->assertTrue($refundEntity2->transaction->isReconciled());
        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull($content[File\Entity::SENT_AT]);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $files = $this->getEntities('file_store', [
            'count' => 2
        ], true);

        $time = Carbon::now(Timezone::IST)->format('dmY');

        $expectedFilesContent = [
            'entity' => 'collection',
            'count'  => 2,
            'items'  => [
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

        Mail::assertSent(DailyFileMail::class, function ($mail) use ($paymentEntity2, $refundEntity2, $refundEntity1, $paymentEntity1) {
            $testData = [
                'count' => [
                    'claims'  => 2,
                    'refunds' => 2
                ]
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->checkRefundsFile(
                $mail->viewData['refundsFile'],
                $paymentEntity1,
                $refundEntity1,
                $refundEntity2,
                $paymentEntity2);

            $this->checkClaimFile(
                $mail->viewData['claimsFile'],
                $paymentEntity1,
                $paymentEntity2);

            $this->assertCount(2, $mail->attachments);

            return true;
        });
    }

    protected function checkRefundsFile(array $refundFileData, $payment1, $fullRefund, $partialRefund, $payment2)
    {
        $refundsFileContents = file($refundFileData['url']);

        $this->assertCount(2, $refundsFileContents);

        $fullRefundRowData = explode('|',$refundsFileContents[0]);

        $partialRefundRowData = explode('|',$refundsFileContents[1]);

        $this->assertCount(6, $fullRefundRowData);

        $this->assertEquals($payment1['id'], $fullRefundRowData[1]);

        // validating if partial refund amount is reflected in the file
        $refundAmount = number_format($fullRefund['amount'] / 100, 2, '.', '');
        $this->assertEquals($refundAmount, $fullRefundRowData[4]);

        $this->assertEquals($payment2['id'], $partialRefundRowData[1]);

        $refundAmount = number_format($partialRefund['amount'] / 100, 2, '.', '');
        $this->assertEquals($refundAmount,  $partialRefundRowData[4]);
    }

    protected function checkClaimFile(array $claimData, $payment1, $payment2)
    {
        $claimFileContents = file($claimData['url']);

        $payment1RowData = explode('|',$claimFileContents[0]);

        $payment2RowData = explode('|',$claimFileContents[1]);

        $this->assertCount(3, $payment1RowData);

        $this->assertEquals($payment1['id'], $payment1RowData[1]);

        $this->assertEquals($payment2['id'], $payment2RowData[1]);
    }

    public function testGenerateCombinedFileDirectSettlementTerminal()
    {
        Mail::fake();

        $this->fixtures->terminal->edit($this->terminal->getId(), [
            'type' => [
                'direct_settlement_with_refund' => '1',
            ],
        ]);

        $this->doAuthPayment($this->payment);

        $payment = $this->getDbLastPayment();

        $this->assertEquals(3, $payment->getCpsRoute());

        $reconFile = $this->getReconFile([$payment]);

        $uploadedFile = $this->createUploadedFile($reconFile);

        $this->reconcile($uploadedFile, Base::NETBANKING_INDUSIND);

        $this->ba->adminAuth();

        $content = $this->startTest();
        $content = $content['items'][0];
        $this->assertNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNull($content[File\Entity::SENT_AT]);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNotNull($content[File\Entity::ACKNOWLEDGED_AT]);
    }

    protected function getReconFile($payments)
    {
        $formattedData = '';

        foreach ($payments as $payment)
        {
            $amount  = number_format($payment->getAmount() / 100, '2', '.', '');

            $formattedData = $formattedData . '1234' . '^' . $amount . '^' . '000' . '^' . $payment->getId() . '^' .
                Carbon::createFromTimestamp($payment->getCreatedAt())->format("d-m-y") . "\n";
        }

        $creator = new FileStore\Creator;

        $file = $creator->extension(FileStore\Format::TXT)
                        ->content($formattedData)
                        ->name('Indusind_Netbanking_Reconciliation')
                        ->type(FileStore\Type::MOCK_RECONCILIATION_FILE)
                        ->headers(false)
                        ->save()
                        ->get();

        return $file['local_file_path'];
    }
}
