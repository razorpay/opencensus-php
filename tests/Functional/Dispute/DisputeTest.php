<?php

namespace RZP\Tests\Functional\Dispute;

use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Dispute\Entity as DisputeEntity;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Models\Dispute\File\Entity as DisputeFileEntity;

class DisputeTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected $payment = null;

    protected $merchant = null;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/DisputeTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
    }

    public function testDisputeCreate()
    {
        $testData = $this->updateCreateTestData();

        $testData['response']['content']['payment_id'] = $this->payment->getId();

        $this->startTest($testData);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(true, $payment['disputed']);

        $dispute = $this->getLastEntity('dispute', true);

        $this->assertEquals(0, $dispute['amount_deducted']);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals('payment', $txn['type']);

    }

    public function testDisputeCreateWithDeduct()
    {
        $testData = $this->updateCreateTestData();

        $testData['response']['content']['payment_id'] = $this->payment->getId();

        $this->startTest($testData);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(true, $payment['disputed']);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals('adjustment', $transaction['type']);

        $this->assertEquals(100, $transaction['amount']);

        $this->assertEquals(100, $transaction['debit']);

        $this->assertEquals(0, $transaction['credit']);

        $dispute = $this->getLastEntity('dispute', true);

        $this->assertEquals(100, $dispute['amount_deducted']);
    }

    public function testDisputeCreateWithDeductWithoutEnoughBalance()
    {
        $payment = $this->fixtures->create('payment:captured');

        $this->fixtures->refund->createFromPayment(['payment' => $payment]);

        $testData = $this->updateCreateTestData('pay_' . $payment->getId());

        $testData['request']['content']['amount'] = $payment->getAmount();

        $this->startTest($testData);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals('refund', $transaction['type']);

        $dispute = $this->getLastEntity('dispute', true);

        $this->assertNull($dispute);
    }

    public function testDisputeCreateWithoutReason()
    {
        $testData = $this->updateCreateTestData();

        $testData['request']['content']['reason_id'] = null;

        $this->startTest($testData);
    }

    public function testDisputeCreateWithExtraFields()
    {
        $this->updateCreateTestData();

        $this->startTest();
    }

    public function testDisputeCreateOnDisputedPayment()
    {
        $dispute = $this->fixtures->create('dispute');

        $this->updateCreateTestData('pay_'.$dispute['payment_id']);

        $this->startTest();
    }

    public function testDisputeCreateWithAmountGreaterThanPayment()
    {
        $this->updateCreateTestData();

        $this->startTest();
    }

    public function testDisputeCreateWithAmountLessThanMin()
    {
        $this->updateCreateTestData();

        $this->startTest();
    }

    public function testDisputeCreateWithInvalidPhase()
    {
        $this->updateCreateTestData();

        $this->startTest();
    }

    public function testDisputeEdit()
    {
        $this->updateEditTestData();

        $this->startTest();
    }

    public function testDisputeEditWon()
    {
        $data = $this->updateEditTestData();

        $this->runRequestResponseFlow($data);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(false, $payment['disputed']);
    }

    public function testDisputeEditClose()
    {
        $data = $this->updateEditTestData();

        $this->runRequestResponseFlow($data);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(false, $payment['disputed']);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals('payment', $txn['type']);

        $adj = $this->getLastEntity('adjustment', true);

        $this->assertNull($adj);
    }

    public function testDisputeEditDeductOnLost()
    {
        $data = $this->updateEditTestData();

        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals('payment', $txn['type']);

        $this->runRequestResponseFlow($data);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(false, $payment['disputed']);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals('adjustment', $txn['type']);

        $this->assertEquals(1000000, $txn['amount']);

        $this->assertEquals(1000000, $txn['debit']);

        $this->assertEquals(0, $txn['credit']);
    }

    public function testDisputeEditDoNotDeductOnLostIfDeducted()
    {
        $data = $this->updateEditTestData(['deduct_at_onset' => 1]);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals('adjustment', $txn['type']);

        $this->assertEquals(1000000, $txn['amount']);

        $this->assertEquals(1000000, $txn['debit']);

        $this->assertEquals(0, $txn['credit']);

        $this->runRequestResponseFlow($data);

        $payment = $this->getLastEntity('payment', true);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals('adjustment', $txn['type']);

        $this->assertEquals(1000000, $txn['amount']);

        $this->assertEquals(1000000, $txn['debit']);

        $this->assertEquals(0, $txn['credit']);

        $this->assertEquals(false, $payment['disputed']);
    }

    public function testDisputeEditClosed()
    {
        $this->updateEditTestData(['status' => 'won']);

        $this->startTest();
    }

    public function testDisputeEditExtraInput()
    {
        $this->updateEditTestData();

        $this->startTest();
    }

    public function testDisputeEditInvalidStatus()
    {
        $this->updateEditTestData();

        $this->startTest();
    }

    public function testDisputeReversalWinLogic()
    {
        // Input params while creating
        $input = [
            'amount'                => 10100,
            'deduct_at_onset'       => 1,
        ];
        $testdata = $this->updateEditTestData($input);

        $oldMerchantBalance = $this->getEntityById('balance', $this->merchant['id'], true)['balance'];

        $content = $this->runRequestResponseFlow($testdata);

        $adjustment = $this->getLastEntity('adjustment', true);

        $dispute = $this->getLastEntity('dispute', true);

        $txn = $this->getLastEntity('transaction', true);

        $newMerchantBalance = $this->getEntityById('balance', $dispute['merchant_id'], true)['balance'];

        $this->assertEquals($dispute['id'], $content['id']);
        $this->assertEquals($testdata['request']['content']['status'], $content['status']);
        $this->assertEquals($input['amount'], $dispute['amount_deducted']);
        $this->assertEquals($dispute['amount_deducted'], $dispute['amount_reversed']);
        $this->assertEquals($adjustment['amount'], $dispute['amount_reversed']);
        $this->assertEquals($input['amount'], ($newMerchantBalance - $oldMerchantBalance));
        $this->assertEquals('adjustment', $txn['type']);
    }

    public function testDisputeReversalLostLogic()
    {
        // Input params while creating
        $input = [
            'amount'                => 10100,
            'deduct_at_onset'       => 1,
        ];
        $testdata = $this->updateEditTestData($input);

        $content = $this->runRequestResponseFlow($testdata);

        $dispute = $this->getLastEntity('dispute', true);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals($dispute['id'], $content['id']);
        $this->assertEquals($testdata['request']['content']['status'], $content['status']);
        $this->assertEquals($input['amount'], $dispute['amount_deducted']);
        $this->assertEquals(0, $dispute['amount_reversed']);
        $this->assertEquals('adjustment', $txn['type']);
    }

    public function testEditDisputeMerchantDocumentUploadByPrivate()
    {
        $this->ba->privateAuth();

        $testData = $this->updateUploadDocumentData();

        $testData['request']['files'][DisputeFileEntity::FILES] = $this->getTestFiles();

        $content = $this->runRequestResponseFlow($testData);

        $this->checkUploadedFilesArray($content);
    }

    public function testEditDisputeMerchantDocumentUploadByProxy()
    {
        $this->ba->proxyAuth();

        $testData = $this->updateUploadDocumentData();

        $testData['request']['files'][DisputeFileEntity::FILES] = $this->getTestFiles();

        $content = $this->runRequestResponseFlow($testData);

        $this->checkUploadedFilesArray($content);
    }

    public function testEditDisputeMerchantAcceptDispute()
    {
        $this->ba->proxyAuth();

        // Input params while creating
        $input = [
            'amount'                => 10100,
            'deduct_at_onset'       => 0,
        ];

        $testdata = $this->updateEditTestData($input);

        $content = $this->runRequestResponseFlow($testdata);

        s($content);
    }

    protected function checkUploadedFilesArray(array $content)
    {
        $dispute = $this->getLastEntity('dispute', true);

        $files = $this->getEntities('dispute_file', [], true);

        $dispute['id'] = DisputeEntity::stripDefaultSign($dispute['id']);

        $this->assertEquals(2, sizeof($content));
        $this->assertEquals(2, $files['count']);
        $this->assertEquals($dispute['id'], $content[0]['dispute_id']);
        $this->assertEquals($dispute['id'], $content[1]['dispute_id']);
        $this->assertEquals($files['items'][0]['dispute_id'], $dispute['id']);
        $this->assertEquals($files['items'][1]['dispute_id'], $dispute['id']);
        $this->assertEquals($files['items'][0]['url'], $content[1]['url']);
        $this->assertEquals($files['items'][1]['url'], $content[0]['url']);
    }

    // ---------------------------- helper methods-------------------------------

    protected function updateCreateTestData(string $paymentId = null): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $name = $trace[1]['function'];

        if (isset($paymentId) === false)
        {
            $this->payment = $this->fixtures->create('payment:captured');

            $paymentId = $this->payment->getPublicId();
        }

        $reason = $this->fixtures->create('dispute_reason');

        $testData = &$this->testData[$name];

        $testData['request']['url'] = '/payments/' . $paymentId . '/disputes';

        $testData['request']['content']['reason_id'] = $reason['id'];

        return $testData;
    }

    protected function updateEditTestData(array $attributes = []): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $name = $trace[1]['function'];

        $dispute = $this->fixtures->create('dispute', $attributes);

        $this->merchant = $dispute->merchant;

        $testData = &$this->testData[$name];

        $testData['request']['url'] = '/disputes/' . $dispute->getPublicId();

        return $testData;
    }

    protected function updateUploadDocumentData(array $attributes = []): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $name = $trace[1]['function'];

        $dispute = $this->fixtures->create('dispute', $attributes);

        $this->merchant = $dispute->merchant;

        $testData = &$this->testData[$name];

        $testData['request']['url'] = '/disputes/' . $dispute->getPublicId();

        return $testData;
    }

    protected function createUploadedFile(string $filePath, string $mimeType = null, int $fileSize = -1)
    {
        $this->assertFileExists($filePath);

        $mimeType = $mimeType ?: 'image/png';

        $fileSize = ($fileSize === -1) ? filesize($filePath) : $fileSize;

        $uploadedFile = new UploadedFile(
            $filePath,
            $filePath,
            $mimeType,
            $fileSize,
            null,
            true
        );

        return $uploadedFile;
    }

    protected function getTestFiles()
    {
        $files = [];

        $originalFile = $this->createUploadedFile('tests/Functional/Storage/a.png');

        copy($originalFile, 'tests/Functional/Storage/a2.png');

        $files[0] = $this->createUploadedFile('tests/Functional/Storage/a2.png');

        $originalFile = $this->createUploadedFile('tests/Functional/Storage/chargeback_codes.pdf');

        copy($originalFile, 'tests/Functional/Storage/chargeback_codes2.pdf');

        $files[1] = $this->createUploadedFile('tests/Functional/Storage/chargeback_codes2.pdf');

        return $files;
    }
}
