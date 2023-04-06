<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Excel;
use Carbon\Carbon;

use Mockery;
use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class GatewayCombinedFileTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/GatewayCombinedFileTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:shared_netbanking_axis_terminal');

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);

        $this->app['rzp.mode'] = Mode::TEST;
        $nbPlusService = Mockery::mock('RZP\Services\Mock\NbPlus\Netbanking', [$this->app])->makePartial();
        $this->app->instance('nbplus.payments', $nbPlusService);
    }

    public function testGenerateCombinedFile()
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

    public function testGenerateCombinedFileWithRefundsOutOfRange()
    {
        Mail::fake();

        $payment = $this->getDefaultNetbankingPaymentArray('UTIB');

        $payment = $this->doAuthAndCapturePayment($payment);

        $refund = $this->refundPayment($payment['id']);

        $refundEntity = $this->getDbLastEntity('refund');

        // Refund out of range
        $refundEntity['created_at'] = Carbon::yesterday()->getTimestamp();

        // Netbanking Axis refunds have moved to scrooge
        $this->assertEquals(1, $refundEntity['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity]);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNull($content[File\Entity::SENT_AT]);
        $this->assertNotNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        Mail::assertNotSent(DailyFileMail::class);
    }

    public function testGenerateCombinedFileWithNoRefundOrClaims()
    {
        Mail::fake();

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNull($content[File\Entity::SENT_AT]);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNotNull($content[File\Entity::ACKNOWLEDGED_AT]);

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
        $refundEntity1 = $this->getDbLastEntity('refund');

        $refund2 = $this->refundPayment($payment2['id']);
        $refundEntity2 = $this->getDbLastEntity('refund');

        // Netbanking Axis refunds have moved to scrooge
        $this->assertEquals(1, $refundEntity1['is_scrooge']);
        $this->assertEquals(1, $refundEntity2['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity1, $refundEntity2]);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull($content[File\Entity::SENT_AT]);

        Mail::assertSent(DailyFileMail::class);
    }

    public function testGenerateCombinedFileWithFileGenerationError()
    {
        $this->markTestSkipped('this flow is depricated and is moved to nbplus service');

        $this->fixtures->create('terminal:shared_netbanking_rbl_terminal');

        Mail::fake();

        $payment = $this->getDefaultNetbankingPaymentArray('RATN');

        $payment['amount'] = 10000012;

        $payment = $this->doAuthAndCapturePayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $refund = $this->refundPayment($payment['id']);

        $refundEntity = $this->getDbLastEntity('refund');

        // Netbanking Rbl refunds have moved to scrooge
        $this->assertEquals(1, $refundEntity['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity]);

        Excel::shouldReceive('create')->andThrow(new \Exception('file_generation_exception'));

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNull($content[File\Entity::SENT_AT]);
        $this->assertNotNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        Mail::assertNotSent(DailyFileMail::class);
    }

    public function testGenerateCombinedFileWithMailSendError()
    {
        $this->markTestSkipped();

        Mail::shouldReceive('send')->andThrow(new \Exception('mail_send_exceptiopn'));

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
        $this->assertNull($content[File\Entity::SENT_AT]);
        $this->assertNotNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);
    }

    public function testGenerateCombinedFileWithReplicationLagError()
    {
        $this->markTestSkipped('this flow is depricated and is moved to nbplus service');

        $connector = $this->mockSqlConnectorWithReplicaLag(400000);

        $this->app->instance('db.connector.mysql', $connector);

        $this->app['config']->set('database.connections.live.heartbeat_check.enabled', true);

        $this->fixtures->create('terminal:shared_netbanking_rbl_terminal');

        Mail::fake();

        $payment = $this->getDefaultNetbankingPaymentArray('RATN');

        $payment['amount'] = 10000012;

        $payment = $this->doAuthAndCapturePayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $this->refundPayment($payment['id']);

        $refundEntity = $this->getDbLastEntity('refund');

        // Netbanking Rbl refunds have moved to scrooge
        $this->assertEquals(1, $refundEntity['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity]);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNull($content[File\Entity::SENT_AT]);
        $this->assertNotNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        Mail::assertNotSent(DailyFileMail::class);
    }
}
