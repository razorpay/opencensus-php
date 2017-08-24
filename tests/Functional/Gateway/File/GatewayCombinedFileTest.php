<?php

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class GatewayCombinedFileTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/GatewayCombinedFileTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:shared_netbanking_axis_terminal');
    }

    public function testGenerateCombinedFile()
    {
        Mail::fake();

        $payment = $this->getDefaultNetbankingPaymentArray('UTIB');

        $payment = $this->doAuthAndCapturePayment($payment);

        $refund = $this->refundPayment($payment['id']);

        $this->ba->appAuth();

        $content = $this->startTest();

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $files = $this->getEntities('file_store', [
            'count' => 2
        ], true);

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 2,
            'items' => [
                [
                    'type' => 'axis_netbanking_claims',
                ],
                [
                    'type' => 'axis_netbanking_refund',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        Mail::assertSent(DailyFileMail::class);
    }

    public function testGenerateCombinedFileWithNoRefundOrClaims()
    {
        Mail::fake();

        $this->ba->appAuth();

        $content = $this->startTest();

        $this->assertNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNull($content[File\Entity::SENT_AT]);
        $this->assertNotNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        Mail::assertNotSent(DailyFileMail::class);
    }

    public function testGenerateCombinedFileWithClaimsLessThanRefunds()
    {
        Mail::fake();

        $payment1 = $this->getDefaultNetbankingPaymentArray('UTIB');
        $payment2 = $this->getDefaultNetbankingPaymentArray('UTIB');

        $payment1 = $this->doAuthAndCapturePayment($payment1);
        $payment2 = $this->doAuthAndCapturePayment($payment2);

        $createdAt = Carbon::yesterday(Timezone::IST)->timestamp;

        $this->fixtures->edit('payment', $payment1['id'], [
            'created_at'    => $createdAt,
            'authorized_at' => $createdAt + 10,
            'captured_at'   => $createdAt + 20,
        ]);

        $refund1 = $this->refundPayment($payment1['id']);
        $refund2 = $this->refundPayment($payment2['id']);

        $this->ba->appAuth();

        $content = $this->startTest();

        $this->assertNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNull($content[File\Entity::SENT_AT]);
        $this->assertNotNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        Mail::assertNotSent(DailyFileMail::class);
    }
}
