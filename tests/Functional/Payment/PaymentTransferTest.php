<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class PaymentTransferTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PaymentTransferTestData.php';

        parent::setUp();

        $this->fixtures->create('payment:authorized');

        $this->payment = $this->getLastEntity('payment', false);

        $this->ba->privateAuth();
    }

    public function testCaptureAndTransferToInvalidCustomerId()
    {
        $this->payment = $this->doAuthAndCapturePayment();

        $this->startTest();
    }

    public function testCaptureAndTransferToUnknownCustomerId()
    {
        $this->payment = $this->doAuthAndCapturePayment();

        $amount = $this->payment['amount'];

        $this->startTest();
    }

    public function testTransferToExistingCustomerWithNoExistingWallet()
    {
        $customer = $this->fixtures->create('customer');

        $customerPublicId = $customer->getPublicId();

        $amount = $this->payment['amount'];

        $this->capturePayment($this->payment['id'], $amount);

        $this->setCustomerTransferArray($this->testData[__FUNCTION__], $customerPublicId, $amount);

        $this->startTest();

        $customerBalance = $this->getLastEntity('customer_balance', true);

        $this->assertSame($customerPublicId, $customerBalance['customer_id']);

        $this->assertSame($amount, $customerBalance['balance']);
    }

    public function testTransferAndVerifyCustomerBalance()
    {
        $customerBalance = $this->fixtures->create('customer:customer_balance', ['balance' => 14000]);

        $customerPublicId = $customerBalance->customer->getPublicId();

        $customerId = $customerBalance->customer->getId();

        $oldBalanceAmount = $customerBalance->getBalance();

        $amount = $this->payment['amount'];

        $this->capturePayment($this->payment['id'], $amount);

        $this->setCustomerTransferArray($this->testData[__FUNCTION__], $customerPublicId, $amount);

        $this->startTest();

        $customerBalance = $this->getLastEntity('customer_balance', true);

        $this->assertSame($customerPublicId, $customerBalance['customer_id']);

        $this->assertSame($oldBalanceAmount + $amount, $customerBalance['balance']);

        $this->testLastTransferEntity($customerId, 'customer', $amount);
    }

    public function testTransferCustomerUsageFirstTxn()
    {
        $customerValues = [
            'balance'       => 100,
            'daily_usage'   => 100,
            'weekly_usage'  => 100,
            'monthly_usage' => 100,
        ];

        $customerBalance = $this->fixtures->create('customer:customer_balance', $customerValues);

        $customerPublicId = $customerBalance->customer->getPublicId();

        $amount = $this->payment['amount'];

        $this->capturePayment($this->payment['id'], $amount);

        $this->setCustomerTransferArray($this->testData[__FUNCTION__], $customerPublicId, $amount);

        $this->assertNull($this->getLastEntity('customer_transaction', true));

        $this->startTest();

        $customerBalance = $this->getLastEntity('customer_balance', true);

        $expected = [
            'balance'       => $amount + 100,
            'daily_usage'   => $amount,
            'weekly_usage'  => $amount,
            'monthly_usage' => $amount,
        ];

        $this->assertArraySelectiveEquals($expected, $customerBalance);
    }

    protected function testLastTransferEntity($toId, $toType, int $amount)
    {
        $testData = [
            'to_type' => $toType,
            'to_id'   => $toId,
            'amount'  => $amount
        ];

        $transfer = $this->getLastEntity('transfer', true);

        $this->assertArraySelectiveEquals($testData, $transfer);
    }

    protected function setCustomerTransferArray(& $testData, $customerId, $amount)
    {
        $transferData = [
            'customer' => $customerId,
            'amount'   => $amount,
        ];

        $testData['request']['content']['transfers'][0] = $transferData;
    }

    public function startTest($id = null, $amount = null)
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        $this->ba->privateAuth();

        $this->setRequestData($testData['request'], $id, $amount);

        return $this->runRequestResponseFlow($testData);
    }

    protected function setRequestData(& $request, $id = null, $amount = null)
    {
        if ($id === null)
        {
            $id = $this->payment['id'];
        }

        $url = '/payments/' . $id . '/transfer';

        $this->setRequestUrlAndMethod($request, $url, 'POST');
    }
}
