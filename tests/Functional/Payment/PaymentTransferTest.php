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

        $payment = $this->fixtures->create('payment:authorized');

        $this->payment = $payment->toArrayPublic();

        $this->ba->privateAuth();
    }

    public function testCaptureAndTransferToInvalidCustomerId()
    {
        $this->payment = $this->defaultAuthPayment();

        $this->startTest();
    }

    public function testCaptureAndTransferToUnknownCustomerId()
    {
        $this->payment = $this->defaultAuthPayment();

        $this->startTest();
    }

    public function testTransferToExistingCustomerWithNoExistingWallet()
    {
        $customer = $this->fixtures->create('customer');

        $customerPublicId = $customer->getPublicId();

        $amount = $this->payment['amount'];

        $transferData = [
            'customer' => $customerPublicId,
            'amount'   => $amount,
        ];

        $this->testData[__FUNCTION__]['request']['content']['transfers'][0] = $transferData;

        $this->startTest();

        $customerBalance = $this->getLastEntity('customer_balance', true);

        $this->assertEquals($customerPublicId, $customerBalance['customer_id']);

        $this->assertEquals($customerBalance['balance'], $amount);
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
        $this->checkAndSetIdAndAmount($id, $amount);

        $request['content']['amount'] = $amount;

        $url = '/payments/' . $id . '/capture';

        $this->setRequestUrlAndMethod($request, $url, 'POST');
    }

    protected function checkAndSetIdAndAmount(& $id = null, & $amount = null)
    {
        if ($id === null)
        {
            $id = $this->payment['id'];
        }

        if ($amount === null)
        {
            if (isset($this->payment['amount']))
                $amount = $this->payment['amount'];
        }
    }

}
