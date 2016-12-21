<?php

namespace RZP\Tests\Functional\Payment\Transfers;

trait TransferTrait
{
    protected function checkLastTransferEntity($toId, $toType, int $amount)
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
