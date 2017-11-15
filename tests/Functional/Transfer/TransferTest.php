<?php

namespace RZP\Tests\Functional\Transfer;

use RZP\Constants\Entity;
use RZP\Models\Transfer;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class TransferTest extends TestCase
{
    use PaymentTrait;

    const STANDARD_PRICING_PLAN_ID  = '1A0Fkd38fGZPVC';

    /**
     * @var string
     */
    protected $linkedAccountId;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/TransferTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $account = $this->fixtures->create('merchant:marketplace_account');

        $this->linkedAccountId = $account['id'];
    }

    public function testFetchTransferReversals()
    {
        $transfer = $this->createTransfer('account');

        $data = $this->testData[__FUNCTION__];

        $this->createReversal($transfer['id']);

        $data['request']['url'] = '/transfers/' . $transfer['id'] . '/reversals';

        $this->ba->privateAuth();

        $this->startTest($data);
    }

    public function testFetchSingleReversal()
    {
        $transfer = $this->createTransfer('account');

        $data = $this->testData[__FUNCTION__];

        $reversal = $this->createReversal($transfer['id']);

        $data['request']['url'] = '/reversals/' . $reversal['id'];

        $this->ba->privateAuth();

        $response = $this->startTest($data);

        $expected = [
            'id'            => $reversal['id'],
            'transfer_id'   => $transfer['id'],
            'amount'        => $transfer['amount']
        ];

        $this->assertArraySelectiveEquals($expected, $response);
    }

    public function testTransferToAccount()
    {
        $transfer = $this->createTransfer('account');

        $savedTransfer =  $this->getLastEntity('transfer', true);

        $this->assertEquals($transfer['id'], $savedTransfer['id']);

        // When Transfer Fee = 0, zero pricing
        $this->assertEquals($transfer['amount'], $this->getBalance($this->linkedAccountId));

        $this->checkTransferAndTxnRecords($transfer, ['fees' => 0, 'tax' => 0]);

        $this->checkPaymentAndTxnRecords($transfer);
    }

    public function testTransferToAccountWithPricing()
    {
        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->editPricingPlanId(self::STANDARD_PRICING_PLAN_ID);

        $transfer = $this->createTransfer('account');

        $tax = 4;
        $expectedFee = 20 + $tax;

        $transferData = [
            'fees'  => $expectedFee,
            'tax'   => $tax
        ];

        $txnData = [
            'amount'      => $transfer['amount'],
            'fee'         => $expectedFee,
            'tax'         => $tax,
            'debit'       => $transfer['amount'] + $expectedFee,
            'credit_type' => 'default',
            'fee_credits' => 0,
        ];

        $this->checkTransferAndTxnRecords($transfer, $transferData, $txnData);
    }

    public function testTransferToAccountWithFeeCredits()
    {
        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->editPricingPlanId(self::STANDARD_PRICING_PLAN_ID);

        $this->fixtures->create('credits', ['type' => 'fee', 'value' => 10000]);
        $this->fixtures->merchant->editFeeCredits(10000, '10000000000000');

        $transfer = $this->createTransfer('account');

        $tax = 4;
        $expectedFee = 20 + $tax;

        $transferData = [
            'fees'  => $expectedFee,
            'tax'   => $tax
        ];

        $txnData = [
            'amount'      => $transfer['amount'],
            'fee'         => $expectedFee,
            'tax'         => $tax,
            'debit'       => $transfer['amount'],
            'fee_credits' => $expectedFee,
            'credit_type' => 'fee',
        ];

        $this->checkTransferAndTxnRecords($transfer, $transferData, $txnData);

        $balance = $this->getEntityById('balance', '10000000000000', true);
        $this->assertEquals(10000 - $expectedFee, $balance['fee_credits']);
    }

    public function testLiveModeTransferToNonActivatedAccount()
    {
        $this->fixtures->merchant->edit('10000000000000', ['activated' => true]);

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function()
        {
            $this->createTransfer('account', [], 'live');
        });
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
        $body = $this->getTransferRequestBody('account')['content'];

        unset($body['on_hold']);

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function() use ($body)
        {
            $this->createTransfer('account', $body);
        });
    }

    public function testTransferOnHoldUntilOnHoldFalse()
    {
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

        $transferId = $this->fixtures->stripSign($transfer['id']);

        $transferPayment = $this->getEntities('payment', ['transfer_id' => $transferId], true)['items'][0];

        $this->fixtures->edit('transaction', $transferPayment['transaction_id'], ['settled' => 1]);

        $body = $this->getTransferRequestBody('account', 'patch')['content'];

        unset($body['on_hold_until']);

        $body['on_hold'] = '0';

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function() use ($transfer, $body)
        {
            $this->patchTransfer('account', Transfer\Entity::getSignedId($transfer['id']), $body);
        });
    }

    public function testPatchTransferOnHoldUntilOnHoldFalse()
    {
        $transfer = $this->createTransfer('account');

        $body = $this->getTransferRequestBody('account', 'patch')['content'];

        $body['on_hold'] = '0';

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function() use ($transfer, $body)
        {
            $this->patchTransfer('account', $transfer['id'], $body);
        });
    }

    public function testPatchTransferOnHoldUntilOnHoldTrue()
    {
        $transfer = $this->createTransfer('account');

        $body['on_hold'] = '1';

        $patch = $this->patchTransfer('account', $transfer['id'], $body);

        $this->assertEquals(true, $patch['on_hold']);
        $this->assertEquals(null, $patch['on_hold_until']);
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

    public function testReversalWithInsufficientLinkedAccountBalance()
    {
        $transfer = $this->createTransfer('account');

        $transferAmount = $transfer['amount'];

        $linkedAccountBalance = $this->getEntityById('balance', $this->linkedAccountId, true);
        $this->assertEquals($transferAmount, $linkedAccountBalance['balance']);

        // Reset the linked account's balance to 0
        $balance = $this->fixtures->balance->edit($this->linkedAccountId, ['balance' => 0]);

        $linkedAccountBalance = $this->getEntityById('balance', $this->linkedAccountId, true);
        $this->assertEquals(0, $linkedAccountBalance['balance']);

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function() use ($transfer)
        {
            $this->createReversal($transfer['id']);
        });
    }

    public function testPaymentAfterTransferReversal()
    {
        $payment = $this->fixtures->create('payment:captured');

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/payments/' . $payment->getPublicId() . '/transfers';

        $this->ba->privateAuth();

        $transfers = $this->startTest();

        $payment = $this->getEntityById('payment', $payment->getId(), true);
        $this->assertEquals(1000, $payment['amount_transferred']);

        $this->createReversal($transfers['items'][0]['id'], 200);

        $payment = $this->getEntityById('payment', $payment['id'], true);
        $this->assertEquals(800, $payment['amount_transferred']);
    }

    public function testLiveTransferFundsOnHold()
    {
        $this->fixtures->merchant->holdFunds();

        // Merchant needs to be activated to make live requests
        $this->fixtures->merchant->edit('10000000000000', ['activated' => 1]);

        $body = $this->getTransferRequestBody('account')['content'];

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function() use ($body)
        {
            $this->createTransfer('account', $body, 'live');
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

    protected function createReversal(string $id, $amount = null, array $notes = [])
    {
        $request = $this->getReversalRequestBody($id);

        if ($amount !== null)
        {
            $request['content']['amount'] = $amount;
        }

        if (empty($notes) === false)
        {
            $request['content']['notes'] = $notes;
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

    protected function checkReversals($amount = null)
    {
        $accOldBalance = $this->getBalance($this->linkedAccountId);

        $marketplaceOldBalance = $this->getBalance('10000000000000');

        $transfer = $this->createTransfer('account');

        $amount = $transfer['amount'] - $amount;

        $notes  = [
            'order_info'    => 'random_string',
            'version'       => 2,
        ];

        $reversal = $this->createReversal($transfer['id'], $amount, $notes);

        $expected = [
            'amount'        => $amount,
            'transfer_id'   => $transfer['id'],
            'currency'      => $transfer['currency'],
            'notes'         => $notes,
        ];

        $this->assertArraySelectiveEquals($expected, $reversal);

        $transaction = $this->getSingleTxn('reversal', $reversal['id']);

        $this->assertEquals($amount, $transaction['credit']);

        $transfer = $this->getEntityById('transfer', $transfer['id']);

        $amountUntransferred = $transfer['amount'] - $transfer['amount_reversed'];

        $this->assertEquals($marketplaceOldBalance - $amountUntransferred, $this->getBalance('10000000000000'));

        $this->assertEquals($accOldBalance + $amountUntransferred, $this->getBalance($this->linkedAccountId));
    }

    protected function getTransferRequestBody(string $type, string $action = 'create')
    {
        // fetches the array from TransferTestData
        $request = $this->testData[$action . 'Transfer'];

        $key = $action . title_case($type) . 'TransferRequest';

        $content = $this->testData[$key];

        $request['content'] = $content;

        return $request;
    }

    protected function getReversalRequestBody(string $id)
    {
        $request = [
            'url'       => '/transfers/' . $id . '/reversals',
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

    protected function getSingleTxn(string $entity = 'payment', string $entityId)
    {
        $entity = Entity::getEntityClass($entity);

        $entity::verifyIdAndSilentlyStripSign($entityId);

        $txn = $this->getEntities('transaction', ['entity_id' => $entityId], true);

        $this->assertEquals(1, count($txn['items']));

        return $txn['items'][0];
    }

    protected function checkTransferAndTxnRecords($transfer, array $transferData = [], array $txnData = [])
    {
        $this->assertArraySelectiveEquals($transferData, $transfer);

        $txn = $this->getSingleTxn('transfer', $transfer['id']);

        $expectedTxn = [
            'type'          => 'transfer',
            'entity_id'     => $transfer['id'],
            'debit'         => $transfer['amount'],
            'credit'        => 0,
            'settled'       => false,
            'fee'           => 0,
            'tax'           => 0,
        ];

        if (empty($txnData) === false)
        {
            $expectedTxn = array_merge($expectedTxn, $txnData);
        }

        $this->assertArraySelectiveEquals($expectedTxn, $txn);
    }

    protected function checkPaymentAndTxnRecords($transfer, array $txnData = [])
    {
        $id = $transfer['id'];

        $payment = $this->getTransferPayment($id);

        $expectedPayment = [
            'amount'        => $transfer['amount'],
            'on_hold'       => $transfer['on_hold'],
            'on_hold_until' => $transfer['on_hold_until'],
        ];

        $this->assertArraySelectiveEquals($expectedPayment, $payment);

        $txn = $this->getSingleTxn('payment', $payment['id']);

        $expectedTxn = [
            'type'          => 'payment',
            'entity_id'     => $payment['id'],
            'credit'        => $transfer['amount'],
            'on_hold'       => $transfer['on_hold'],
            'settled'       => false,
            'fee'           => 0,
            'tax'           => 0,
        ];

        if (empty($txnData) === false)
        {
            $expectedTxn = array_merge($expectedTxn, $txnData);
        }

        $this->assertArraySelectiveEquals($expectedTxn, $txn);
    }
}
