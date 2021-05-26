<?php
namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Excel;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Mail\Gateway\DailyFile;
use RZP\Models\Transaction\Statement\Entity;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingIndusindCombinedFileTest extends NbPlusPaymentServiceNetbankingTest
{
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

        $refundEntity1 = $this->getDbLastEntity('refund');

        $paymentEntity1 = $this->getDbLastPayment();

        $transaction1 = $this->getDbLastEntityToArray('transaction');

        $this->doAuthCaptureAndRefundPayment($this->payment, 500);

        $refundEntity2 = $this->getDbLastEntity('refund');

        $paymentEntity2 = $this->getDbLastPayment();

        $transaction2 = $this->getDbLastEntityToArray('transaction');

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

        $tran1 = $this->getDbEntityById('transaction', $transaction1['id']);
        $tran2 = $this->getDbEntityById('transaction', $transaction2['id']);

        $this->assertNotNull($tran1[Entity::RECONCILED_AT]);
        $this->assertNotNull($tran2[Entity::RECONCILED_AT]);
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

            //
            // Marking netbanking transaction as reconciled after sending in bank file
            //
            $refundTransaction = $this->getLastEntity('transaction', true);

            $this->assertNotNull($refundTransaction['reconciled_at']);

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
}
