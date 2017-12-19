<?php

namespace RZP\Tests\Functional\Dispute;

use Mail;
use RZP\Models\Dispute\Entity;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Mail\Dispute\Creation as DisputeCreationMail;

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

    public function testDisputeCreateMerchantMail()
    {
        Mail::fake();

        $testData = $this->updateCreateTestData();

        $testData['response']['content']['payment_id'] = $this->payment->getId();

        $this->startTest($testData);

        Mail::assertSent(DisputeCreationMail::class);
    }

    public function testDisputeCreateWithoutMerchantEmail()
    {
        Mail::fake();

        $testData = $this->updateCreateTestData();

        $this->startTest($testData);

        Mail::assertNotSent(DisputeCreationMail::class);
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

    public function testDisputeCreateWithParent()
    {
        $disputeParent = $this->fixtures->create('dispute');

        $testData = $this->updateCreateTestData();

        $testData['request']['content']['parent_id'] = $disputeParent->getId();

        $this->runRequestResponseFlow($testData);

        $disputeChild = $this->getLastEntity('dispute', true);

        $this->assertEquals($disputeParent->getId(), $disputeChild['parent_id']);
    }

    public function testDisputeCreateWithDuplicateParent()
    {
        $disputeParent = $this->fixtures->create('dispute');

        $this->fixtures->create('dispute', ['parent_id' => $disputeParent->getId()]);

        $testData = $this->updateCreateTestData();

        $testData['request']['content']['parent_id'] = $disputeParent->getId();

        $this->startTest($testData);
    }

    public function testDisputeCreateWithInvalidMerchantEmail()
    {
        $this->updateCreateTestData();

        $this->startTest();
    }

    public function testDisputeCreateWithInvalidMerchantEmail2()
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

    public function testDisputeEditForNoInitialParent()
    {
        $disputeParent = $this->fixtures->create('dispute');

        $testData = $this->updateEditTestData();

        $testData['request']['content']['parent_id'] = $disputeParent->getId();
        $testData['response']['content']['parent_id'] = $disputeParent->getId();

        $content = $this->runRequestResponseFlow($testData);

        $disputes = $this->getEntities('dispute', [], true);

        $this->checkRequestDisputeAttributes($content, $disputes);
        $this->assertEquals(2, $disputes['count']);
        $this->assertEquals($content['parent_id'], Entity::stripDefaultSign($disputes['items'][1]['id']));
    }

    public function testDisputeEditWithExistingParent()
    {
        $disputeParent = $this->fixtures->create('dispute');

        $testData = $this->updateEditTestData(['parent_id' => $disputeParent->getId()]);

        $testData['request']['content']['parent_id'] = $disputeParent->getId();

        $this->startTest($testData);
    }

    public function testDisputeEditReplaceParent()
    {
        $disputeParent = $this->fixtures->create('dispute');

        $disputeNewParent = $this->fixtures->create('dispute');

        $testData = $this->updateEditTestData(['parent_id' => $disputeParent->getId()]);

        $testData['request']['content']['parent_id'] = $disputeNewParent->getId();
        $testData['response']['content']['parent_id'] = $disputeNewParent->getId();

        $content = $this->runRequestResponseFlow($testData);

        $disputes = $this->getEntities('dispute', [], true);

        $this->checkRequestDisputeAttributes($content, $disputes);
        $this->assertEquals(3, $disputes['count']);
        $this->assertEquals($content['parent_id'], Entity::stripDefaultSign($disputes['items'][1]['id']));
    }

    public function testDisputeEditReplaceParentWithAlreadyLinkedParent()
    {
        $disputeOtherParent = $this->fixtures->create('dispute');

        $this->fixtures->create('dispute', ['parent_id' => $disputeOtherParent->getId()]);

        $disputeParent = $this->fixtures->create('dispute');

        $testData = $this->updateEditTestData(['parent_id' => $disputeParent->getId()]);

        $testData['request']['content']['parent_id'] = $disputeOtherParent->getId();

        $this->startTest($testData);
    }

    public function testDisputeLostPartiallyAccepted()
    {
        // Input params while creating
        $input = [
            'amount'                => 10000,
            'deduct_at_onset'       => 1,
        ];
        $testdata = $this->updateEditTestData($input);

        $testdata['request']['content'][Entity::ACCEPTED_AMOUNT] = 7000;

        $content = $this->runRequestResponseFlow($testdata);

        $dispute = $this->getLastEntity('dispute', true);

        $adjustments = $this->getEntities('adjustment', [], true);

        $reqContent = $testdata['request']['content'];

        $this->assertEquals($dispute['id'], $content['id']);
        $this->assertEquals($testdata['request']['content']['status'], $content['status']);
        $this->assertEquals($input['amount'], $dispute['amount']);
        $this->assertEquals($input['amount'], $dispute['amount_deducted']);
        $this->assertEquals(($input['amount'] - $reqContent[Entity::ACCEPTED_AMOUNT]),
            $dispute['amount_reversed']);
        $this->assertEquals(2, $adjustments['count']);
        $this->assertEquals(Entity::stripDefaultSign($dispute['id']), $adjustments['items'][0]['entity_id']);
        $this->assertEquals(Entity::stripDefaultSign($dispute['id']), $adjustments['items'][1]['entity_id']);
        $this->assertEquals(($input['amount'] - $reqContent[Entity::ACCEPTED_AMOUNT]),
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

        $testdata['request']['content'][Entity::ACCEPTED_AMOUNT] = 7000;

        $content = $this->runRequestResponseFlow($testdata);

        $dispute = $this->getLastEntity('dispute', true);

        $adjustments = $this->getEntities('adjustment', [], true);

        $reqContent = $testdata['request']['content'];

        $this->assertEquals($dispute['id'], $content['id']);
        $this->assertEquals($reqContent['status'], $content['status']);
        $this->assertEquals($input['amount'], $dispute['amount']);
        $this->assertEquals($reqContent[Entity::ACCEPTED_AMOUNT],
            $dispute['amount_deducted']);
        $this->assertEquals(0, $dispute['amount_reversed']);
        $this->assertEquals(1, $adjustments['count']);
        $this->assertEquals(Entity::stripDefaultSign($dispute['id']),
            $adjustments['items'][0]['entity_id']);
        $this->assertEquals((0 - $reqContent[Entity::ACCEPTED_AMOUNT]),
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

        $testdata['request']['content'][Entity::ACCEPTED_AMOUNT] = 20000;

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

        $testdata['request']['content'][Entity::ACCEPTED_AMOUNT] = 0;

        $this->startTest($testdata);
    }

    public function testDisputeFetchForMerchant()
    {
        $this->ba->proxyAuth();

        $disputes[0] = $this->fixtures->create('dispute');

        $disputes[1] = $this->fixtures->create('dispute');

        $testData = $this->updateFetchTestData();

        $content = $this->runRequestResponseFlow($testData);

        $this->checkDisputeFetchForMerchant($disputes, $content);

        $this->ba->privateAuth();

        $content = $this->runRequestResponseFlow($testData);

        $this->checkDisputeFetchForMerchant($disputes, $content);
    }

    protected function checkDisputeFetchForMerchant(array $disputes, array $content)
    {
        $this->assertEquals(2, $content['count']);
        $this->assertEquals($disputes[0]->getId(), Entity::stripDefaultSign($content['items'][1]['id']));
        $this->assertEquals($disputes[1]->getId(), Entity::stripDefaultSign($content['items'][0]['id']));
        $this->assertEquals($disputes[0]->payment->getId(), $content['items'][1]['payment_id']);
        $this->assertEquals($disputes[1]->payment->getId(), $content['items'][0]['payment_id']);
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

    protected function updateFetchTestData(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $name = $trace[1]['function'];

        $testData = &$this->testData[$name];

        return $testData;
    }

    protected function checkRequestDisputeAttributes(array $content, array $disputes)
    {
        $this->assertEquals($content['id'], $disputes['items'][0]['id']);
        $this->assertEquals($content['parent_id'], $disputes['items'][0]['parent_id']);
        $this->assertEquals($content['payment_id'], $disputes['items'][0]['payment_id']);
    }
}
