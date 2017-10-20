<?php

namespace RZP\Tests\Functional\Dispute;

use RZP\Models\Dispute\Entity as DisputeEntity;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class DisputeTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected $payment = null;

    protected $merchant = null;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/DisputeTestData.php';

        parent::setUp();

        $this->ba->appAuth();
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

    public function testDisputeLostPartiallyAccepted()
    {
        // Input params while creating
        $input = [
            'amount'                => 10000,
            'deduct_at_onset'       => 1,
        ];
        $testdata = $this->updateEditTestData($input);

        $testdata['request']['content'][DisputeEntity::ACCEPTED_DISPUTE_AMOUNT] = 7000;

        $content = $this->runRequestResponseFlow($testdata);

        $dispute = $this->getLastEntity('dispute', true);

        $adjustments = $this->getEntities('adjustment', [], true);

        $reqContent = $testdata['request']['content'];

        $this->assertEquals($dispute['id'], $content['id']);
        $this->assertEquals($testdata['request']['content']['status'], $content['status']);
        $this->assertEquals($input['amount'], $dispute['amount']);
        $this->assertEquals($input['amount'], $dispute['amount_deducted']);
        $this->assertEquals(($input['amount'] - $reqContent[DisputeEntity::ACCEPTED_DISPUTE_AMOUNT]),
            $dispute['amount_reversed']);
        $this->assertEquals(2, $adjustments['count']);
        $this->assertEquals(DisputeEntity::stripDefaultSign($dispute['id']), $adjustments['items'][0]['entity_id']);
        $this->assertEquals(DisputeEntity::stripDefaultSign($dispute['id']), $adjustments['items'][1]['entity_id']);
        $this->assertEquals(($input['amount'] - $reqContent[DisputeEntity::ACCEPTED_DISPUTE_AMOUNT]),
            $adjustments['items'][0]['amount']);
        $this->assertEquals(0 - $input['amount'], $adjustments['items'][1]['amount']);
    }

    public function testDisputeLostPartiallyAcceptedForNoOnsetDeduct()
    {
        // Input params while creating
        $input = [
            'amount'                => 10000,
            'deduct_at_onset'       => 0,
        ];
        $testdata = $this->updateEditTestData($input);

        $testdata['request']['content'][DisputeEntity::ACCEPTED_DISPUTE_AMOUNT] = 7000;

        $content = $this->runRequestResponseFlow($testdata);

        $dispute = $this->getLastEntity('dispute', true);

        $adjustments = $this->getEntities('adjustment', [], true);

        $reqContent = $testdata['request']['content'];

        $this->assertEquals($dispute['id'], $content['id']);
        $this->assertEquals($reqContent['status'], $content['status']);
        $this->assertEquals($input['amount'], $dispute['amount']);
        $this->assertEquals($reqContent[DisputeEntity::ACCEPTED_DISPUTE_AMOUNT],
            $dispute['amount_deducted']);
        $this->assertEquals(0, $dispute['amount_reversed']);
        $this->assertEquals(1, $adjustments['count']);
        $this->assertEquals(DisputeEntity::stripDefaultSign($dispute['id']),
            $adjustments['items'][0]['entity_id']);
        $this->assertEquals((0 - $reqContent[DisputeEntity::ACCEPTED_DISPUTE_AMOUNT]),
            $adjustments['items'][0]['amount']);
    }

    public function testDisputeLostPartiallyAcceptedWithInvalidAcceptedAmount()
    {
        // Input params while creating
        $input = [
            'amount'                => 10000,
            'deduct_at_onset'       => 0,
        ];
        $testdata = $this->updateEditTestData($input);

        $testdata['request']['content'][DisputeEntity::ACCEPTED_DISPUTE_AMOUNT] = 20000;

        $this->startTest($testdata);
    }

    public function testDisputeLostPartiallyAcceptedWithZeroAcceptedAmount()
    {
        // Input params while creating
        $input = [
            'amount'                => 10000,
            'deduct_at_onset'       => 0,
        ];
        $testdata = $this->updateEditTestData($input);

        $testdata['request']['content'][DisputeEntity::ACCEPTED_DISPUTE_AMOUNT] = 0;

        $this->startTest($testdata);
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
}
