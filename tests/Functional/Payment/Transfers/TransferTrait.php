<?php

namespace RZP\Tests\Functional\Payment\Transfers;

use RZP\Constants\Entity as E;

trait TransferTrait
{
    protected function checkLastTransferEntity($toId, $toType, int $amount)
    {
        $testData = [
            'recipient' => $toId,
            'amount'    => $amount
        ];

        $transfer = $this->getLastEntity('transfer', true);

        $this->assertArraySelectiveEquals($testData, $transfer);
    }

    protected function setCustomerTransferArray(& $testData, $customerId, $amount)
    {
        $transferData = [
            'customer' => $customerId,
            'amount'   => $amount,
            'currency'=> 'INR',
        ];

        $testData['request']['content']['transfers'][0] = $transferData;
    }

    public function startTest($id = null, $amount = null, $mode = 'test')
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        $this->ba->privateAuth();

        if ($mode === 'live')
        {
            $this->ba->privateAuth('rzp_live_TheLiveAuthKey');
        }

        $this->setRequestData($testData['request'], $id, $amount);

        return $this->runRequestResponseFlow($testData);
    }

    protected function setRequestData(& $request, $id = null, $amount = null)
    {
        if ($id === null)
        {
            $id = $this->payment['id'];
        }

        $url = '/payments/' . $id . '/transfers';

        $this->setRequestUrlAndMethod($request, $url, 'POST');
    }

    protected function getAccountBalance(string $accountId)
    {
        return $this->getEntityById('balance', $accountId, true)['balance'];
    }

    protected function getLastTransactionFee(string $accountId)
    {
        $lastTxnForAccount =  $this->getEntities(
                                'transaction',
                                [
                                    'merchant_id' => $accountId,
                                    'count' => 1
                                ],
                                true);

        return $lastTxnForAccount['items'][0]['fee'];
    }
}
