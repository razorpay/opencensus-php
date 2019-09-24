<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;
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

    protected $terminal;

    public function setUp()
    {
        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingSbiCombinedFileTestData.php';

        parent::setUp();

        $this->setMockGatewayTrue();

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_sbi_terminal');
    }

    public function testGenerateCombinedFile()
    {
        Mail::fake();

        $this->createClaimAndRefundPayment();

        $content = $this->generateFiles();

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $this->performPostFileGenerationAssertions();
    }

    public function testClaimFileWithEmandatePayment()
    {
        Mail::fake();

        $this->createClaimAndRefundPayment();

        $this->setUpEmandate();

        $this->createEmandatePayment();

        $content = $this->generateFiles();

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
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

        $this->assertEquals($refundsFileRow[4], 500);
    }

    protected function createClaimAndRefundPayment()
    {
        $payment = $this->getDefaultNetbankingPaymentArray('SBIN');

        $payment = $this->doAuthAndCapturePayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $this->refundPayment($payment['id']);
    }

    protected function createEmandatePayment()
    {
        $this->payment = $this->getEmandateNetbankingRecurringPaymentArray('SBIN');

        $this->payment['bank_account'] = [
            'account_number'    => self::ACCOUNT_NUMBER,
            'ifsc'              => self::IFSC,
            'name'              => self::NAME,
        ];

        unset($this->payment['card']);

        $registerPayments[] = [
            'payment' => $this->createRegistrationPayment(),
            'status'  => 'SUCCESS',
            'umrn'    => '111111111111111'
        ];

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

        $rfDate = Carbon::now(Timezone::IST)->format('d.m.y');

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 2,
            'items' => [
                [
                    'type' => 'sbi_netbanking_claim',
                    'location' => 'SBI_CLAIM' . '_' . $date . '.txt'
                ],
                [
                    'type' => 'sbi_netbanking_refund',
                    'location' => 'RZPY_SBI_Refund' . '_' . $rfDate . '.txt'
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
}
