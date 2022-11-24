<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Tests\Functional\TestCase;
use RZP\Reconciliator\RequestProcessor\Base;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Gateway\Mozart\NetbankingCbi\RefundFields;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingCbiCombinedFileTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected $terminal;

    const REFUND_FIELDS = RefundFields::REFUND_FIELDS;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingCbiCombinedFileTestData.php';

        parent::setUp();

        $this->bank = 'CBIN';

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_cbi_terminal');

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);

        $this->markTestSkipped('this flow is depricated and is moved to nbplus service');
    }

    public function testNetbankingCbiCombinedFile()
    {
        Mail::fake();

        $paymentArray = $this->getDefaultNetbankingPaymentArray($this->bank);

        $payment1 = $this->doAuthAndCapturePayment($paymentArray);

        $transaction = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $payment2 = $this->doAuthAndCapturePayment($paymentArray);

        $transaction = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $fileContents = $this->generateFile('cbi', ['gateway' => 'netbanking_cbi']);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingCbi', $uploadedFile);

        // full refund
        $refundFull   = $this->refundPayment($payment1['id']);

        $refundEntity1 = $this->getDbLastEntity('refund');

        //partial refund
        $refundPartial   = $this->refundPayment($payment2['id'], 500);

        $refundEntity2 = $this->getDbLastEntity('refund');

        // Netbanking Cbi refunds have moved to scrooge
        $this->assertEquals(1, $refundEntity1['is_scrooge']);
        $this->assertEquals(1, $refundEntity2['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity1, $refundEntity2]);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);

        $this->assertNotNull(File\Entity::SENT_AT);

        $this->assertNull($content[File\Entity::FAILED_AT]);

        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity('file_store', true);

        $expectedFilesContent = [
            'type'      => 'cbi_netbanking_refund',
            'extension' => 'txt'
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $file);

        Mail::assertSent(DailyFileMail::class, function ($mail) use ($payment1, $payment2, $refundFull, $refundPartial)
        {
            $today = Carbon::now(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject'  => 'Cbi Netbanking claims and refund files for '.$today,
                'bankName' => 'Cbi',
                'amount' => [
                    'claims'  => '1000.00',
                    'refunds' => '505.00',
                    'total'   => '495.00'
                ],
                'count' => [
                    'claims'  => 2,
                    'refunds' => 2,
                ],
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->checkRefundsFile( $mail->viewData['refundsFile'], [$refundFull['amount'], $refundPartial['amount']]);

            $this->assertCount(1, $mail->attachments);

            return true;
        });
    }

    protected function generateFile($bank, $input)
    {
        $gateway = 'netbanking_' . $bank;

        $request = [
            'url'     => '/gateway/mock/reconciliation/' . $gateway,
            'content' => $input,
            'method'  => 'POST'
        ];

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function createUploadedFile($file)
    {
        $this->assertFileExists($file);

        $mimeType = "text/plain";

        $uploadedFile = new UploadedFile(
            $file,
            $file,
            $mimeType,
            null,
            true
        );

        return $uploadedFile;
    }

    protected function reconcile($gateway, $uploadedFile, $forceAuthorizePayments = [])
    {
        $this->ba->cronAuth();

        $input = [
            'manual'           => true,
            'gateway'          => $gateway,
            'attachment-count' => 1,
        ];

        if (empty($forceAuthorizePayments) === false)
        {
            foreach ($forceAuthorizePayments as $forceAuthorizePayment)
            {
                $input[Base::FORCE_AUTHORIZE][] = $forceAuthorizePayment;
            }
        }

        $request = [
            'url'     => '/reconciliate',
            'content' => $input,
            'method'  => 'POST',
            'files'   => [
                Base::ATTACHMENT_HYPHEN_ONE => $uploadedFile,
            ],
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function checkRefundsFile(array $refundFileData, $refundAmts)
    {
        $refundsFileContents = file($refundFileData['url']);

        $this->assertCount(3, $refundsFileContents);

        $refund1 = substr($refundsFileContents[0],19,16);
        $refund2 = substr($refundsFileContents[1],19,16);

        $this->assertEquals($refundAmts[0], (int)$refund1);
        $this->assertEquals($refundAmts[1], (int)$refund2);

        //
        // Marking netbanking transaction as reconciled after sending in bank file
        //
        $refundTransaction = $this->getLastEntity('transaction',true);

        $this->assertNotNull($refundTransaction['reconciled_at']);
    }
}
