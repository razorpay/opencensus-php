<?php

namespace RZP\Tests\Functional\Dispute;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class DisputeTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected $payment = null;

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

    public function testDisputeEditClose()
    {
        $data = $this->updateEditTestData();

        $content = $this->runRequestResponseFlow($data);

        $payment = $this->getLastEntity('payment', true);

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

        $testData = &$this->testData[$name];

        $testData['request']['url'] = '/disputes/' . $dispute->getPublicId();

        return $testData;
    }
}
