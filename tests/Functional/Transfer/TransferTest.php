<?php

namespace RZP\Tests\Functional\Transfer;

use Carbon\Carbon;

use RZP\Models\Reversal;
use RZP\Models\Payment;
use RZP\Models\Transfer;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class TransferTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/TransferTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->fixtures->create('merchant:marketplace_account');

        $this->customer = $this->fixtures->create('customer:customer_balance');
    }

    public function testTransferToAccount()
    {
        $transfer = $this->createTransfer('account');

        $savedTransfer =  $this->getLastEntity('transfer', true);

        $this->assertEquals($transfer['id'], $savedTransfer['id']);

        // When Transfer Fee = 0, zero pricing
        $this->assertEquals($transfer['amount'], $this->getBalance('10000000000001'));

        $this->checkPaymentAndTxnRecords($transfer);
    }

    public function testLiveModeTransferToNonActivatedAccount()
    {
        $this->fixtures->merchant->edit('10000000000000', ['activated' => true]);

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function()
        {
            $this->createTransfer('account', [], 'live');
        });
    }

    public function testTransferToWallet()
    {
        // @todo: not implemented for wallet yet
    }

    public function testTransferInvalidType()
    {
        $body = $this->getTransferRequestBody('account')['content'];

        unset($body['account']);

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function() use ($body)
        {
            $this->createTransfer('account', $body);
        });
    }

    public function testTransferOnHold()
    {
        $transfer = $this->createTransfer('account');

        $this->assertEquals(true, $transfer['on_hold']);
    }

    public function testTransferOnHoldFalse()
    {
        $body = $this->getTransferRequestBody('account')['content'];

        unset($body['on_hold'], $body['on_hold_until']);

        $transfer = $this->createTransfer('account', $body);

        $this->assertEquals(false, $transfer['on_hold']);
    }

    public function testTransferOnHoldUntilInvalid()
    {
        $this->markTestSkipped('on_hold_until removed for now');

        $body = $this->getTransferRequestBody('account')['content'];

        unset($body['on_hold']);

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function() use ($body)
        {
            $this->createTransfer('account', $body);
        });
    }

    public function testTransferOnHoldUntilOnHoldFalse()
    {
        $this->markTestSkipped('on_hold_until removed for now');

        $body = $this->getTransferRequestBody('account')['content'];

        $body['on_hold'] = '0';

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function() use ($body)
        {
            $this->createTransfer('account', $body);
        });
    }

    public function testPatchTransferOnHold()
    {
        $transfer = $this->createTransfer('account');

        $body = $this->getTransferRequestBody('account', 'patch')['content'];

        unset($body['on_hold_until']);

        $body['on_hold'] = '0';

        $patch = $this->patchTransfer('account', $transfer['id'], $body);

        $this->assertEquals(false, $patch['on_hold']);

        $this->checkPaymentAndTxnRecords($patch);
    }

    public function testPatchTransferOnHoldTxnSettled()
    {
        $transfer = $this->createTransfer('account');

        $transferId = $this->fixtures->transfer->stripSign($transfer['id']);

        $transferPayment = $this->getEntities('payment', ['transfer_id' => $transferId], true)['items'][0];

        $this->fixtures->edit('transaction', $transferPayment['transaction_id'], ['settled' => 1]);

        $body = $this->getTransferRequestBody('account', 'patch')['content'];

        unset($body['on_hold_until']);

        $body['on_hold'] = '0';

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function() use ($transfer, $body)
        {
            $this->patchTransfer('account', 'trf_' . $transfer['id'], $body);
        });
    }

    public function testPatchTransferOnHoldUntilOnHoldFalse()
    {
        $this->markTestSkipped('on_hold_until removed for now');

        $transfer = $this->createTransfer('account');

        $body = $this->getTransferRequestBody('account', 'patch')['content'];

        $body['on_hold'] = '0';

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function() use ($transfer, $body)
        {
            $this->patchTransfer('account', $transfer['id'], $body);
        });
    }

    public function testRetrieveTransfer()
    {
        $transfer = $this->createTransfer('account');

        $data = $this->testData[__FUNCTION__];

        $data['request']['url'] .= '/' . $transfer['id'];

        $response = $this->runRequestResponseFlow($data);

        $this->assertEquals($transfer['id'], $response['id']);
    }

    public function testRetrieveMultipleTransfers()
    {
        $data = $this->testData[__FUNCTION__];

        $transfer1 = $this->createTransfer('account');

        $transfer2 = $this->createTransfer('account');

        $data['response']['content']['items'][] = $transfer2;

        $data['response']['content']['items'][] = $transfer1;

        $this->runRequestResponseFlow($data);
    }

    public function testFullReversal()
    {
        $this->checkReversals();
    }

    public function testReversalAmountExceedingTransferred()
    {
        $transfer = $this->createTransfer('account');

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function() use ($transfer)
        {
            $this->createReversal($transfer['id'], $transfer['amount'] + 100);
        });
    }

    public function testPartialReversal()
    {
        $this->checkReversals(200);
    }

    public function testPartialReversalExceeding()
    {
        $transfer = $this->createTransfer('account');

        $reversal = $this->createReversal($transfer['id'], 300);

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function() use ($transfer)
        {
            $this->createReversal($transfer['id'], 800);
        });
    }

    // ---- Helpers -----

    protected function createTransfer($type, $data = [], $mode = 'test')
    {
        $request = $this->getTransferRequestBody($type, 'create');

        return $this->getResponse($request, $data, $mode);
    }

    protected function patchTransfer(string $type, string $id, array $data = [])
    {
        $request = $this->getTransferRequestBody($type, 'patch');

        $request['url'] .= '/' . $id;

        return $this->getResponse($request, $data);
    }

    protected function createReversal(string $id, $amount = null)
    {
        $request = $this->getReversalRequestBody($id);

        if ($amount !== null)
        {
            $request['content']['amount'] = $amount;
        }

        return $this->getResponse($request);
    }

    protected function getResponse(array $request, array $data = [], $mode = 'test')
    {
        if (empty($data) === false)
        {
            $request['content'] = $data;
        }

        $this->ba->privateAuth();

        if ($mode === 'live')
        {
            $this->ba->privateAuth('rzp_live_TheLiveAuthKey');
        }

        return $this->makeRequestAndGetContent($request);
    }

    // @todo: Refactor for transfer pricing calc
    protected function checkReversals($amount = null)
    {
        $accOldBalance = $this->getBalance('10000000000001');

        $marketplaceOldBalance = $this->getBalance('10000000000000');

        $transfer = $this->createTransfer('account');

        $amount = $transfer['amount'] - $amount;

        $reversal = $this->createReversal($transfer['id'], $amount);

        $expected = [
            'amount'        => $amount,
            'transfer_id'   => $transfer['id'],
            'currency'      => $transfer['currency'],
        ];

        $this->assertArraySelectiveEquals($expected, $reversal);

        $transaction = $this->getReversalTxn($reversal['id']);

        $this->assertEquals($amount, $transaction['credit']);

        $transfer = $this->getEntityById('transfer', $transfer['id']);

        $amountUntransferred = $transfer['amount'] - $transfer['amount_reversed'];

        $this->assertEquals($marketplaceOldBalance - $amountUntransferred, $this->getBalance('10000000000000'));

        $this->assertEquals($accOldBalance + $amountUntransferred, $this->getBalance('10000000000001'));
    }

    protected function getTransferRequestBody(string $type, string $action = 'create')
    {
        $request = $this->testData[$action . 'Transfer'];

        $key = $action . title_case($type) . 'TransferRequest';

        $content = $this->testData[$key];

        $request['content'] = $content;

        return $request;
    }

    protected function getReversalRequestBody(string $id)
    {
        $request = [
            'url'       => '/transfers/' . $id . '/reversal',
            'method'    => 'POST',
            'content'   => []
        ];

        return $request;
    }

    protected function getBalance(string $accountId)
    {
        return $this->getEntityById('balance', $accountId, true)['balance'];
    }

    protected function getTransferPayment(string $transferId) : array
    {
        Transfer\Entity::verifyIdAndSilentlyStripSign($transferId);

        $payments = $this->getEntities('payment', ['transfer_id' => $transferId], true);

        $this->assertEquals(1, count($payments['items']));

        return $payments['items'][0];
    }

    protected function getTransferPaymentTxn(string $paymentId)
    {
        Payment\Entity::verifyIdAndSilentlyStripSign($paymentId);

        $txn = $this->getEntities('transaction', ['entity_id' => $paymentId], true);

        $this->assertEquals(1, count($txn['items']));

        return $txn['items'][0];
    }

    protected function getReversalTxn(string $reversalId)
    {
        Reversal\Entity::verifyIdAndSilentlyStripSign($reversalId);

        $txn = $this->getEntities('transaction', ['entity_id' => $reversalId], true);

        $this->assertEquals(1, count($txn['items']));

        return $txn['items'][0];
    }

    protected function checkPaymentAndTxnRecords($transfer)
    {
        $id = $transfer['id'];

        $payment = $this->getTransferPayment($id);

        $expectedPayment = [
            'amount'        => $transfer['amount'],
            'on_hold'       => $transfer['on_hold'],
            // 'on_hold_until' => $transfer['on_hold_until'],
        ];

        $this->assertArraySelectiveEquals($expectedPayment, $payment);

        $txn = $this->getTransferPaymentTxn($payment['id']);

        $expectedTxn = [
            'credit'        => $transfer['amount'],
            'on_hold'       => $transfer['on_hold'],
            'settled'       => false
        ];

        $this->assertArraySelectiveEquals($expectedTxn, $txn);
    }
}
