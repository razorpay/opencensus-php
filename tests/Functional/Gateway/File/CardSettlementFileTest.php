<?php

namespace RZP\Tests\Functional\Gateway\File;

use Cassandra\Time;
use Mail;
use Queue;
use Excel;
use Mockery;
use Carbon\Carbon;

use RZP\Jobs\BeamJob;
use RZP\Models\Feature;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Models\Admin\ConfigKey;
use RZP\Tests\Traits\MocksRazorx;
use RZP\Tests\Functional\TestCase;
use RZP\Http\Controllers as Controllers;
use RZP\Jobs\GatewayFile as GatewayFileJob;
use RZP\Models\Admin\Service as AdminService;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class CardSettlementFileTest extends TestCase
{
    use MocksRazorx;
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/CardSettlementFileTestData.php';

        parent::setUp();

        $this->axisSettlementTestCore();
    }


    public function testGenerateAxisSettlementAndRefundFileWithTimestamps()
    {
        $this->testData[__FUNCTION__] = $this->testData['testGenerateAxisSettlementAndRefundFile'];

        $this->testData[__FUNCTION__]['request']['content']['begin'] = Carbon::today(Timezone::IST)->getTimestamp();

        $this->testData[__FUNCTION__]['request']['content']['end'] = Carbon::tomorrow(Timezone::IST)->getTimestamp();

        $content = $this->startTest();

        $this->axisSettlementFileVerifyOutput($content);
    }

    public function testGenerateAxisSettlementAndRefundFileWithoutTimestamps()
    {
        $this->testData[__FUNCTION__] = $this->testData['testGenerateAxisSettlementAndRefundFile'];

        $content = $this->startTest();

        $this->axisSettlementFileVerifyOutput($content);
    }

    /*
     * Test case sheet: https://docs.google.com/spreadsheets/d/1IGfnaBIzZD0W-iF_aDo91k1ZpW1feHHhBMbnwmyWqb4/edit#gid=1442026146
     */
    public function testCase2()
    {
        $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()+1);

        $this->generateAndCheckFile(3, 13);

        $this->generateAndCheckFile(2, 15);

        $this->generateAndCheckFile(2, 18);

        $this->generateAndCheckFile(2, 25);
    }

    public function testCase3()
    {
        $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()+1, 'failed');

        $this->generateAndCheckFile(2, 13);

        $this->generateAndCheckFile(2, 15);

        $this->generateAndCheckFile(2, 18);

        $this->generateAndCheckFile(2, 25);
    }

    public function testCase4()
    {
        $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()+1, 'authorized');

        $this->generateAndCheckFile(2, 13);

        $this->generateAndCheckFile(2, 15);

        $this->generateAndCheckFile(2, 18);

        $this->generateAndCheckFile(2, 25);
    }

    public function testCase5()
    {
        $id = $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()+1, 'authorized');

        $this->generateAndCheckFile(2, 13);

        $paymentAttributes = [
            'captured_at'   => $this->calculateTimestampStartOfBatch(2)->getTimestamp()+1,
            'status'        => 'captured'
        ];

        $this->fixtures->edit('payment', $id, $paymentAttributes);

        $this->generateAndCheckFile(3, 15);

        $this->generateAndCheckFile(2, 18);

        $this->generateAndCheckFile(2, 25);
    }

    public function testCase6()
    {
        $id = $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()+1, 'authorized');

        $paymentAttributes = [
            'captured_at'   => $this->calculateTimestampStartOfBatch(1)->getTimestamp()+1,
            'status'        => 'captured'
        ];

        $this->fixtures->edit('payment', $id, $paymentAttributes);

        $this->generateAndCheckFile(3, 13);

        $this->generateAndCheckFile(2, 15);

        $this->generateAndCheckFile(2, 18);

        $this->generateAndCheckFile(2, 25);
    }

    public function testCase7()
    {
        $id = $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()+1);

        $refund = $this->refundPayment('pay_'.$id);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(1)->getTimestamp()+2),
        ]);

        $this->generateAndCheckFile(4, 13);

        $this->generateAndCheckFile(2, 15);

        $this->generateAndCheckFile(2, 18);

        $this->generateAndCheckFile(2, 25);
    }

    public function testCase7WithExperiment()
    {
        $this->mockRazorxTreatmentV2('axis_moto_new_column', 'on');

        $id = $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()+1);

        $refund = $this->refundPayment('pay_'.$id);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(1)->getTimestamp()+2),
        ]);

        $this->generateAndCheckFile(4, 13);

        $this->generateAndCheckFile(2, 15);

        $this->generateAndCheckFile(2, 18);

        $this->generateAndCheckFile(2, 25);
    }

    public function testCase8()
    {
        $id = $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()+1);

        $this->generateAndCheckFile(3, 13);

        $refund = $this->refundPayment('pay_'.$id);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(2)->getTimestamp()+2),
        ]);

        $this->generateAndCheckFile(3, 15);

        $this->generateAndCheckFile(2, 18);

        $this->generateAndCheckFile(2, 25);
    }

    public function testCase9()
    {
        $id = $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()+1);

        $refund = $this->refundPayment('pay_'.$id, 100);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(1)->getTimestamp()+2),
        ]);

        $this->generateAndCheckFile(4, 13);

        $this->generateAndCheckFile(2, 15);

        $this->generateAndCheckFile(2, 18);

        $this->generateAndCheckFile(2, 25);
    }

    public function testCase10()
    {
        $id = $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()+1);

        $this->generateAndCheckFile(3, 13);

        $refund = $this->refundPayment('pay_'.$id, 100);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(2)->getTimestamp()+2),
        ]);

        $this->generateAndCheckFile(3, 15);

        $this->generateAndCheckFile(2, 18);

        $this->generateAndCheckFile(2, 25);
    }

    public function testCase20()
    {
        $id = $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()+1);

        $refund = $this->refundPayment('pay_'.$id, 500000);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(1)->getTimestamp()+2),
        ]);

        $refund = $this->refundPayment('pay_'.$id, 500000);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(1)->getTimestamp()+2),
        ]);

        $this->generateAndCheckFile(5, 13);

        $this->generateAndCheckFile(2, 15);

        $this->generateAndCheckFile(2, 18);

        $this->generateAndCheckFile(2, 25);
    }

    public function testCase21()
    {
        $id = $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()+1);

        $refund = $this->refundPayment('pay_'.$id, 500000);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(1)->getTimestamp()+2),
        ]);

        $this->generateAndCheckFile(4, 13);

        $refund = $this->refundPayment('pay_'.$id, 500000);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(2)->getTimestamp()+2),
        ]);

        $this->generateAndCheckFile(3, 15);

        $this->generateAndCheckFile(2, 18);

        $this->generateAndCheckFile(2, 25);
    }

    public function testCase22()
    {
        $id = $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()+1);

        $this->generateAndCheckFile(3, 13);

        $refund = $this->refundPayment('pay_'.$id, 500000);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(2)->getTimestamp()+2),
        ]);

        $this->generateAndCheckFile(3, 15);

        $refund = $this->refundPayment('pay_'.$id, 500000);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(3)->getTimestamp()+2),
        ]);

        $this->generateAndCheckFile(3, 18);

        $this->generateAndCheckFile(2, 25);
    }

    public function testCase28()
    {
        $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()+1);

        $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()+2);

        $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()+3);

        $this->generateAndCheckFile(5, 13);

        $this->generateAndCheckFile(2, 15);

        $this->generateAndCheckFile(2, 18);

        $this->generateAndCheckFile(2, 25);
    }

    public function testCase29()
    {
        $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()+1);

        $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()+2);

        $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()+3);

        $this->generateAndCheckFile(5, 13);

        $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(2)->getTimestamp()+3);

        $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(2)->getTimestamp()+3);

        $this->generateAndCheckFile(4, 15);

        $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(3)->getTimestamp()+3);

        $this->generateAndCheckFile(3, 18);

        $this->generateAndCheckFile(2, 25);
    }

    public function testCase31()
    {
        $id = $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()+1);

        $refund = $this->refundPayment('pay_'.$id);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(1)->getTimestamp()+2),
        ]);

        $id = $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()+3);

        $refund = $this->refundPayment('pay_'.$id);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(1)->getTimestamp()+4),
        ]);

        $id = $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()+5);

        $refund = $this->refundPayment('pay_'.$id);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(1)->getTimestamp()+6),
        ]);

        $this->generateAndCheckFile(8, 13);

        $id = $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(2)->getTimestamp()+1);

        $refund = $this->refundPayment('pay_'.$id);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(2)->getTimestamp()+2),
        ]);

        $id = $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(2)->getTimestamp()+3);

        $refund = $this->refundPayment('pay_'.$id);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(2)->getTimestamp()+4),
        ]);

        $this->generateAndCheckFile(6, 15);

        $id = $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(3)->getTimestamp()+1);

        $refund = $this->refundPayment('pay_'.$id);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(3)->getTimestamp()+2),
        ]);

        $this->generateAndCheckFile(4, 18);

        $this->generateAndCheckFile(2, 25);
    }

    public function testCase32()
    {
        $id = $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()-1);
        $refund = $this->refundPayment('pay_'.$id);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(1)->getTimestamp()+4),
        ]);

        $id = $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()-2);
        $refund = $this->refundPayment('pay_'.$id);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(1)->getTimestamp()+5),
        ]);

        $id = $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()-3);
        $refund = $this->refundPayment('pay_'.$id);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(1)->getTimestamp()+6),
        ]);

        $id = $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()-4);
        $refund = $this->refundPayment('pay_'.$id);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(1)->getTimestamp()+7),
        ]);

        $this->generateAndCheckFile(6, 13);

        $id = $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()-3);
        $refund = $this->refundPayment('pay_'.$id);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(2)->getTimestamp()+7),
        ]);

        $id = $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()-3);
        $refund = $this->refundPayment('pay_'.$id);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(2)->getTimestamp()+8),
        ]);

        $this->generateAndCheckFile(4, 15);

        $this->generateAndCheckFile(2, 18);

        $id = $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()-3);
        $refund = $this->refundPayment('pay_'.$id);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(4)->getTimestamp()+7),
        ]);

        $this->generateAndCheckFile(3, 25);
    }

    public function testCase37()
    {
        $id = $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()+1);
        $refund = $this->refundPayment('pay_'.$id);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(1)->getTimestamp()+5),
        ]);

        $id = $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()+2);
        $refund = $this->refundPayment('pay_'.$id);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(1)->getTimestamp()+7),
        ]);

        $this->generateAndCheckFile(6, 13);

        $this->generateAndCheckFile(2, 15);

        $id = $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()+3);
        $refund = $this->refundPayment('pay_'.$id);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(1)->getTimestamp()+8),
        ]);

        $id = $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()+4);
        $refund = $this->refundPayment('pay_'.$id);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(1)->getTimestamp()+17),
        ]);

        $this->generateAndCheckFile(6, 18);

        $this->generateAndCheckFile(2, 25);
    }

    public function testCase26()
    {
        $id = $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()+1);
        $refund = $this->refundPayment('pay_'.$id, 300000);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(1)->getTimestamp()+2),
        ]);

        $refund = $this->refundPayment('pay_'.$id, 300000);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(2)->getTimestamp()+2),
        ]);

        $refund = $this->refundPayment('pay_'.$id, 400000);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(3)->getTimestamp()+2),
        ]);

        $id = $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()+10);
        $refund = $this->refundPayment('pay_'.$id, 500000);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(1)->getTimestamp()+11),
        ]);

        $refund = $this->refundPayment('pay_'.$id, 400000);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(2)->getTimestamp()+11),
        ]);

        $id = $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()+100);
        $refund = $this->refundPayment('pay_'.$id, 400000);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(2)->getTimestamp()+101),
        ]);

        $refund = $this->refundPayment('pay_'.$id, 400000);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(3)->getTimestamp()+101),
        ]);

        $id = $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(2)->getTimestamp()+1);
        $refund = $this->refundPayment('pay_'.$id, 400000);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(2)->getTimestamp()+105),
        ]);

        $refund = $this->refundPayment('pay_'.$id, 400000);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(3)->getTimestamp()+107),
        ]);

        $this->generateAndCheckFile(7, 13);

        $this->generateAndCheckFile(7, 15);

        $this->generateAndCheckFile(5, 18);

        $this->generateAndCheckFile(2, 25);
    }

    public function testCase36()
    {
        $id = $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()+1);
        $refund = $this->refundPayment('pay_'.$id);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(1)->getTimestamp()+5),
        ]);

        $id = $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()+2);
        $refund = $this->refundPayment('pay_'.$id);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(1)->getTimestamp()+7),
        ]);

        $this->generateAndCheckFile(6, 13);

        $this->generateAndCheckFile(2, 15);

        $id = $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()+3);
        $refund = $this->refundPayment('pay_'.$id);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(1)->getTimestamp()+8),
        ]);

        $id = $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(1)->getTimestamp()+4);
        $refund = $this->refundPayment('pay_'.$id);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(1)->getTimestamp()+17),
        ]);

        ///////
        $id = $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(2)->getTimestamp()+3);
        $refund = $this->refundPayment('pay_'.$id);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(2)->getTimestamp()+8),
        ]);

        $id = $this->createCapturedPaymentAt($this->calculateTimestampStartOfBatch(2)->getTimestamp()+4);
        $refund = $this->refundPayment('pay_'.$id);

        $this->fixtures->edit('refund', $refund['id'], [
            'processed_at' => ($this->calculateTimestampStartOfBatch(2)->getTimestamp()+17),
        ]);

        $this->generateAndCheckFile(10, 18);

        $this->generateAndCheckFile(2, 25);
    }

    protected function generateAndCheckFile($rows, $extraHrs)
    {
        $this->ba->adminAuth();

        $this->testData[__FUNCTION__] = $this->testData['testGenerateAxisSettlementAndRefundFile'];

        Carbon::setTestNow(Carbon::yesterday(Timezone::IST)->addHours($extraHrs));

        $content = $this->startTest();

        Carbon::setTestNow();

        $this->checkFileData($rows);

        $this->axisSettlementFileVerifyOutput($content);
    }

    protected function checkFileData($numRows)
    {
        $filestorecontrol = new Controllers\FileStoreController();

        $file = $this->getLastEntity('file_store', true);

        $fileEntity = $filestorecontrol->getFile($file['id']);

        $content = $fileEntity->getOriginalContent();

        // Open the file
        $fp = fopen($content['url'], 'r');

        // Add each line to an array
        if ($fp) {
            $data = explode("\n", fread($fp, filesize($content['url'])));
        }

        $this->assertEquals($numRows, count($data));
    }

    protected function axisSettlementTestCore()
    {
        Mail::fake();

        Queue::fake();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->mockCardVault();

        $this->fixtures->edit('iin', '411146', [
            'issuer' => 'ICIC'
        ]);

        $this->fixtures->merchant->addFeatures([Feature\Constants::AXIS_SETTLEMENT_FILE]);

        (new AdminService)->setConfigKeys([
                ConfigKey::CARD_PAYMENTS_SETTLEMENT_FILE_CUTOFF_TIMESTAMP => Carbon::yesterday(Timezone::IST)->getTimestamp()
            ]);

        (new AdminService)->setConfigKeys([
                ConfigKey::CARD_REFUNDS_SETTLEMENT_FILE_CUTOFF_TIMESTAMP => Carbon::yesterday(Timezone::IST)->getTimestamp()
            ]);

        $this->ba->adminAuth();
    }

    protected function axisSettlementFileVerifyOutput($content)
    {
        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $entities = $this->getEntities('file_store', [], true)['items'];

        $expectedFileContent = [
            'type'        => 'axis_cardsettlement_file',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'gpg',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $entities[1]);

        $expectedFileContent = [
            'type'        => 'axis_cardsettlement_output_file',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'txt',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $entities[0]);

//        Queue::assertPushed(BeamJob::class, 1);

        Queue::assertPushedOn('beam_test', BeamJob::class);
    }

    protected function createCapturedPaymentAt($time, $status='captured')
    {
        switch ($status)
        {
            case 'authorized'   : $paymentId = $this->fixtures->create('payment:'.$status, [
                                        'gateway'       => 'cybersource',
                                        'reference_2'   => 'abcdefgh',
                                        'notes'         => [
                                            'GST' => 'GST_' . $time,
                                            'CorporateName' => 'ABCD \nCorp_' . $time,
                                            'MTR' => 'paymentRefId_'.$time
                                        ],
                                    ])->getId();
                        break;

            default             : $paymentId = $this->fixtures->create('payment:'.$status, [
                                        'gateway'       => 'cybersource',
                                        'captured_at'   => $time,
                                        'reference_2'   => 'abcdefgh',
                                        'notes'         => [
                                            'GST' => 'GST_' . $time,
                                            'CorporateName' => 'ABCD
                                            Corp_Comp' . $time,
                                            'MTR' => 'paymentRefId_'.$time
                                        ],
                                    ])->getId();
        }

        return $paymentId;
    }

    protected function calculateTimestampStartOfBatch($batch)
    {
        switch ($batch)
        {
            case 1: return Carbon::yesterday(Timezone::IST)->addHours(0);

            case 2: return Carbon::yesterday(Timezone::IST)->addHours(12);

            case 3: return Carbon::yesterday(Timezone::IST)->addHours(14);

            case 4: return Carbon::yesterday(Timezone::IST)->addHours(17);
        }

        return Carbon::today(Timezone::IST)->getTimestamp();
    }

}
