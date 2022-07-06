<?php

namespace RZP\Tests\Functional\Transfer;

use Mail;
use Mockery;

use RZP\Models\Transfer;
use RZP\Constants\Entity;
use RZP\Models\User\Role;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\RefundSource;
use RZP\Exception\BadRequestException;
use RZP\Models\Reversal\Entity as ReversalEntity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class TransferTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    const STANDARD_PRICING_PLAN_ID  = '1A0Fkd38fGZPVC';

    /**
     * @var string
     */
    protected $linkedAccountId;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/TransferTestData.php';

        parent::setUp();

        $this->initializeTestSetup();
    }

    protected function initializeTestSetup()
    {
        $this->fixtures->merchant->addFeatures(['marketplace', 'direct_transfer']);

        $account = $this->fixtures->create('merchant:marketplace_account');

        $merchantDetailAttributes =  [
            'merchant_id'   => $account['id'],
            'contact_email' => $account['email'],
            'activation_status' => "activated",
            'bank_details_verification_status'  => 'verified'
        ];

        $this->fixtures->create('merchant_detail:associate_merchant', $merchantDetailAttributes);

        $this->linkedAccountId = $account['id'];
    }

    public function testDirectTransferWithoutFeature()
    {
        $this->fixtures->merchant->removeFeatures(['direct_transfer']);

        $this->makeRequestAndCatchException(
            function ()
            {
                $this->createTransfer('account');
            },
            BadRequestException::class,
            'This feature is not enabled for this merchant.'
        );
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

    public function testFetchSingleReversalProxyAuth()
    {
        $transfer = $this->createTransfer('account');

        $data = $this->testData[__FUNCTION__];

        $reversal = $this->createReversal($transfer['id']);

        $data['request']['url'] = '/reversals/' . $reversal['id'] . '?expand[]=transaction.settlement';

        $this->ba->proxyAuth();

        $response = $this->startTest($data);

        $txn = $this->getLastEntity('transaction', true);

        $expected = [
            'id'            => $reversal['id'],
            'transfer_id'   => $transfer['id'],
            'amount'        => $transfer['amount'],
            'transaction'   => [
                'entity_id' => $reversal['id'],
                'amount'    => $reversal['amount'],
                'settlement' => NULL,
            ],
        ];

        $this->assertArraySelectiveEquals($expected, $response);
    }

    public function testFetchMultipleReversals()
    {
        $transfer1 = $this->createTransfer('account');

        $transfer2 = $this->createTransfer('account');

        $reversal1 = $this->createReversal($transfer1['id'], 500);

        $reversal2 = $this->createReversal($transfer1['id'], 400);

        $reversal3 = $this->createReversal($transfer2['id']);

        $data = $this->testData[__FUNCTION__];

        $data['response']['content']['items'][] = $reversal3;

        $data['response']['content']['items'][] = $reversal2;

        $data['response']['content']['items'][] = $reversal1;

        $this->runRequestResponseFlow($data);
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

    public function testFetchTransferProxyAuth()
    {
        $transfer = $this->createTransfer('account');

        $data = $this->testData[__FUNCTION__];

        $data['request']['url'] = '/transfers/' . $transfer['id'] . '?expand[]=transaction.settlement';

        $this->ba->privateAuth();

        $response = $this->startTest($data);

        $expected = [
            'id'            => $transfer['id'],
            'amount'        => $transfer['amount'],
            'transaction'   => [
                'entity_id'     => $transfer['id'],
                'amount'        => $transfer['amount'],
                'settlement'    => NULL,
            ],
        ];

        $this->assertArraySelectiveEquals($expected, $response);
    }

    public function testTransferToAccountUsingAccountCode()
    {
        $this->fixtures->merchant->addFeatures('route_code_support');
        $this->fixtures->edit('merchant', '10000000000001', ['account_code' => 'code-007']);

        $this->ba->privateAuth();
        $this->startTest();
    }

    public function testTransferToAccountUsingAccountCodeWhenFeatureDisabled()
    {
        $this->fixtures->edit('merchant', '10000000000001', ['account_code' => 'code-007']);

        $request = $this->testData['testTransferToAccountUsingAccountCode']['request'];

        $this->makeRequestAndCatchException(
            function() use ($request)
            {
                $this->ba->privateAuth();
                $this->makeRequestAndGetContent($request);
            },
            BadRequestException::class,
            'account_code is not allowed for this merchant.'
        );
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
            'amount'          => $transfer['amount'],
            'fee'             => $expectedFee,
            'tax'             => $tax,
            'debit'           => $transfer['amount'] + $expectedFee,
            'credit_type'     => 'default',
            'fee_credits'     => 0,
        ];

        $txnId = $this->checkTransferAndTxnRecords($transfer, $transferData, $txnData);

        $feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $txnId], true);

        $expectedBreakup = [
            'name'            => "transfer",
            'transaction_id'  => $txnId,
            'pricing_rule_id' => "1zE31zbyeGCTd4",
            'percentage'      => null,
            'amount'          => 20,
        ];

        $this->assertArraySelectiveEquals($expectedBreakup, $feesSplit['items'][1]);
    }

    public function testTransferToAccountPricingPostpaid()
    {
        $this->fixtures->merchant->edit('10000000000000', ['fee_model' => 'postpaid']);

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
            'debit'       => $transfer['amount'],
            'fee_model'   => 'postpaid',
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

        $creditTransactions = $this->getEntities('credit_transaction', [], true);
        $this->assertEquals($expectedFee, $creditTransactions['items'][0]['credits_used']);
    }

    public function testTransferToAccountWithAmountCredits()
    {
        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->editPricingPlanId(self::STANDARD_PRICING_PLAN_ID);

        $this->fixtures->create('credits', ['type' => 'amount', 'value' => 10000]);
        $this->fixtures->merchant->editCredits(10000, '10000000000000');

        $transfer = $this->createTransfer('account');

        //
        // We just need to assert in this test that no fees were charged
        // for transfer with amount credits. This same thing we do for transfer
        // and txn entity.
        //

        $transferData = [
            'fees' => 0,
            'tax'  => 0,
        ];

        $txnData = [
            'amount'      => $transfer['amount'],
            'fee'         => 0,
            'tax'         => 0,
            'fee_credits' => 0,
            'gratis'      => true,
            'debit'       => $transfer['amount'],
            'credit_type' => 'amount',
        ];

        $this->checkTransferAndTxnRecords($transfer, $transferData, $txnData);

        $balance = $this->getEntityById('balance', '10000000000000', true);
        $this->assertEquals(0, $balance['fee_credits']);
        $this->assertEquals(9000, $balance['credits']);

        $creditTransactions = $this->getEntities('credit_transaction', [], true);
        $this->assertEquals(1000, $creditTransactions['items'][0]['credits_used']);
    }

    public function testLiveModeTransferToNonActivatedAccount()
    {
        $this->fixtures->on('live')->merchant->edit('10000000000000', ['activated' => true]);

        $this->fixtures->on('live')->merchant->editBalance(20000);

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function()
        {
            $this->createTransfer('account', [], 'live');
        });
    }

    public function testDirectTransferAmountOverMaxAmount()
    {
        $this->fixtures->merchant->edit('10000000000000', ['max_payment_amount' => 100]);

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function()
        {
            $this->createTransfer('account');
        });
    }

    public function testTransferInsufficientBalance()
    {
        $this->fixtures->merchant->editBalance(100);

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function()
        {
            $this->createTransfer('account', []);
        });
    }

    public function testTransferWithFeeInsufficientBalance()
    {
        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->editPricingPlanId(self::STANDARD_PRICING_PLAN_ID);

        $this->fixtures->merchant->editBalance(1000);

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function()
        {
            $this->createTransfer('account', []);
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

    public function testTransferWithoutPaymentAmountValidation()
    {
        $this->fixtures->merchant->edit($this->linkedAccountId, ['max_payment_amount' => 100]);

        $transfer = $this->createTransfer('account');

        $this->assertEquals(1000, $transfer['amount']);
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

    public function testLaNotesTransfer()
    {
        $transfer = $this->createTransfer('account');

        $payment = $this->getTransferPayment($transfer['id']);

        $notes = ['roll_no'       => "iec2011025",
                  'student_name'  => 'student',];

        $this->assertEquals($notes, $payment['notes']);
    }

    public function testLaNotesReversal()
    {
        $transfer = $this->createTransfer('account');

        $notes = [
            "roll_no" => "iec2011025",
            "awesome" => true,
            "great"   => "cool"
        ];

        $reversal = $this->createReversal($transfer['id'], null,$notes, ["roll_no", "great"]);

        $refund = $this->getReversalRefund($reversal['id']);

        $this->assertEquals(['roll_no' => 'iec2011025','great' => 'cool'], $refund['notes']);
    }

    public function testLaNotesKeyMissing()
    {
        $transfer = $this->createTransfer('account');

        $notes = [
            "roll_no" => "iec2011025",
            "awesome" => true,
            "great"   => "cool"
        ];

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function() use ($transfer, $notes)
        {
            $reversal = $this->createReversal($transfer['id'], null, $notes, ["roll_no", "no_great"]);
        });
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

    public function testRetrieveLaTransfers()
    {
        $transfer = $this->createTransfer('account');

        $data = &$this->testData[__FUNCTION__];

        // Notes will be fetched from payments entity
        unset($transfer['notes']);

        $user = $this->fixtures->user->createUserForMerchant('10000000000001', [], Role::LINKED_ACCOUNT_OWNER);

        $data['response']['content']['items'] = [$transfer];

        $this->ba->proxyAuth('rzp_test_10000000000001' , $user->getId());

        $this->startTest();
    }

    public function testFetchLaTransfer()
    {
        $transfer = $this->createTransfer('account');

        $data = &$this->testData[__FUNCTION__];

        $data['request']['url'] = sprintf($data['request']['url'], $transfer['id']);

        // Notes will be fetched from payments entity
        unset($transfer['notes']);

        $user = $this->fixtures->user->createUserForMerchant('10000000000001', [], Role::LINKED_ACCOUNT_OWNER);

        $data['response']['content'] = $transfer;

        $this->ba->proxyAuth('rzp_test_10000000000001' , $user->getId());

        $this->startTest();
    }

    public function testLinkedAccountValidation()
    {
        $transfer = $this->createTransfer('account');

        $user = $this->fixtures->user->createUserForMerchant('10000000000000', [], Role::LINKED_ACCOUNT_OWNER);

        $this->ba->proxyAuth('rzp_test_10000000000000' , $user->getId());

        $this->startTest();
    }

    public function testLaFetchTransferReversals()
    {
        $transfer = $this->createTransfer('account');

        $data = & $this->testData[__FUNCTION__];

        $reversal = $this->createReversal($transfer['id']);

        $data['request']['url'] = '/la-transfers/' . $transfer['id'] . '/reversals';

        $data['response']['content']['items'][] = $reversal;

        $user = $this->fixtures->user->createUserForMerchant('10000000000001', [], Role::LINKED_ACCOUNT_OWNER);

        $this->ba->proxyAuth('rzp_test_10000000000001', $user->getId());

        $this->startTest();
    }

    public function testLaFetchReversals()
    {
        $transfer = $this->createTransfer('account');

        $reversal = $this->createReversal($transfer['id']);

        $data = & $this->testData[__FUNCTION__];

        $data['request']['url'] = '/la-reversals';

        $data['response']['content']['items'][] = $reversal;

        $user = $this->fixtures->user->createUserForMerchant('10000000000001', [], Role::LINKED_ACCOUNT_OWNER);

        $this->ba->proxyAuth('rzp_test_10000000000001', $user->getId());

        $this->startTest();
    }

    public function testLaFetchReversal()
    {
        $transfer = $this->createTransfer('account');

        $reversal = $this->createReversal($transfer['id']);

        $data = & $this->testData[__FUNCTION__];

        $data['request']['url'] = '/la-reversals/' . $reversal['id'];

        $data['response']['content'] = $reversal;

        $user = $this->fixtures->user->createUserForMerchant('10000000000001', [], Role::LINKED_ACCOUNT_OWNER);

        $this->ba->proxyAuth('rzp_test_10000000000001', $user->getId());

        $this->startTest();
    }


    // RM.refund_source=balance & LA.refund_source=balance
    public function testLinkedAccountReversalCase1()
    {
        $testName = 'testLinkedAccountReversal';

        $data = $this->setUpForReversalsTests($testName, RefundSource::BALANCE, RefundSource::BALANCE);

        $amountReversed = $data['request']['content']['amount'];

        $marketplaceOldBalance = $this->getBalance('10000000000000');

        $accOldBalance = $this->getBalance($this->linkedAccountId);

        $this->setAuthForLinkedAccount();

        $this->startTest($data);

        $reversal = $this->getLastEntity('reversal', true);

        $this->assertNull($reversal['customer_refund_id']);

        $this->assertEquals($reversal['merchant_id'], '10000000000000');

        $this->assertEquals($reversal['initiator_id'], 'acc_' . $this->linkedAccountId);

        $this->assertEquals($accOldBalance - $amountReversed, $this->getBalance($this->linkedAccountId));

        $this->assertEquals($marketplaceOldBalance + $amountReversed, $this->getBalance('10000000000000'));
    }

    // RM.refund_source=balance & LA.refund_source=credits
    public function testLinkedAccountReversalCase2()
    {
        $testName = 'testLinkedAccountReversal';

        $data = $this->setUpForReversalsTests($testName, RefundSource::BALANCE, RefundSource::CREDITS);

        $amountReversed = $data['request']['content']['amount'];

        $marketplaceOldBalance = $this->getBalance('10000000000000');

        $accOldCredits = $this->getBalanceForType($this->linkedAccountId, 'refund_credits');

        $this->setAuthForLinkedAccount();

        $this->startTest($data);

        $accNewCredits = $this->getBalanceForType($this->linkedAccountId, 'refund_credits');

        $reversal = $this->getLastEntity('reversal', true);

        $this->assertNull($reversal['customer_refund_id']);

        $this->assertEquals($reversal['merchant_id'], '10000000000000');

        $this->assertEquals($reversal['initiator_id'], 'acc_' . $this->linkedAccountId);

        $this->assertEquals($accOldCredits - $amountReversed, $accNewCredits);

        $this->assertEquals($marketplaceOldBalance + $amountReversed, $this->getBalance('10000000000000'));
    }

    // RM.refund_source=balance & LA.refund_source=balance
    public function testLinkedAccountReversalAndCustomerRefundCase1()
    {
        $testName = 'testLinkedAccountReversalAndCustomerRefund';

        $data = $this->setUpForReversalsTests($testName, RefundSource::BALANCE, RefundSource::BALANCE);

        $amountReversed = $data['request']['content']['amount'];

        $marketplaceOldBalance = $this->getBalance('10000000000000');

        $marketplaceOldCredits = $this->getBalanceForType('10000000000000', 'refund_credits');

        $accOldBalance = $this->getBalance($this->linkedAccountId);

        $accOldCredits = $this->getBalanceForType($this->linkedAccountId, 'refund_credits');

        $this->setAuthForLinkedAccount();

        $this->startTest($data);

        $marketplaceNewCredits = $this->getBalanceForType('10000000000000', 'refund_credits');

        $accNewCredits = $this->getBalanceForType($this->linkedAccountId, 'refund_credits');

        $reversal = $this->getLastEntity('reversal', true);

        $refund = $this->getLastEntity('refund', true);

        $refundId = substr($refund['id'], strpos($refund['id'], "_") + 1);

        $this->assertEquals($reversal['merchant_id'], '10000000000000');

        $this->assertEquals($reversal['customer_refund_id'], 'rfnd_' . $refundId);

        $this->assertEquals($reversal['merchant_id'], '10000000000000');

        $this->assertEquals($reversal['initiator_id'], 'acc_' . $this->linkedAccountId);

        $this->assertEquals($accOldBalance - $amountReversed, $this->getBalance($this->linkedAccountId));

        $this->assertEquals($marketplaceOldBalance, $this->getBalance('10000000000000'));

        $this->assertEquals($marketplaceOldCredits, $marketplaceNewCredits);

        $this->assertEquals($accOldCredits, $accNewCredits);
    }

    // RM.refund_source=balance & LA.refund_source=credits
    public function testLinkedAccountReversalAndCustomerRefundCase2()
    {
        $testName = 'testLinkedAccountReversalAndCustomerRefund';

        $data = $this->setUpForReversalsTests($testName, RefundSource::BALANCE, RefundSource::CREDITS);

        $amountReversed = $data['request']['content']['amount'];

        $marketplaceOldBalance = $this->getBalance('10000000000000');

        $marketplaceOldCredits = $this->getBalanceForType('10000000000000', 'refund_credits');

        $accOldBalance = $this->getBalance($this->linkedAccountId);

        $accOldCredits = $this->getBalanceForType($this->linkedAccountId, 'refund_credits');

        $this->setAuthForLinkedAccount();

        $this->startTest($data);

        $marketplaceNewCredits = $this->getBalanceForType('10000000000000', 'refund_credits');

        $accNewCredits = $this->getBalanceForType($this->linkedAccountId, 'refund_credits');

        $reversal = $this->getLastEntity('reversal', true);

        $refund = $this->getLastEntity('refund', true);

        $refundId = substr($refund['id'], strpos($refund['id'], "_") + 1);

        $this->assertEquals($reversal['merchant_id'], '10000000000000');

        $this->assertEquals($reversal['customer_refund_id'], 'rfnd_' . $refundId);

        $this->assertEquals($reversal['initiator_id'], 'acc_' . $this->linkedAccountId);

        $this->assertEquals($accOldBalance, $this->getBalance($this->linkedAccountId));

        $this->assertEquals($accOldCredits - $amountReversed, $accNewCredits);

        $this->assertEquals($marketplaceOldBalance, $this->getBalance('10000000000000'));

        $this->assertEquals($marketplaceOldCredits, $marketplaceNewCredits);
    }

    // RM.refund_source=credits & LA.refund_source=balance
    public function testLinkedAccountReversalAndCustomerRefundCase3()
    {
        $testName = 'testLinkedAccountReversalAndCustomerRefund';

        $data = $this->setUpForReversalsTests($testName, RefundSource::CREDITS, RefundSource::BALANCE);

        $amountReversed = $data['request']['content']['amount'];

        $marketplaceOldBalance = $this->getBalance('10000000000000');

        $marketplaceOldCredits = $this->getBalanceForType('10000000000000', 'refund_credits');

        $accOldBalance = $this->getBalance($this->linkedAccountId);

        $this->setAuthForLinkedAccount();

        $this->startTest($data);

        $marketplaceNewCredits = $this->getBalanceForType('10000000000000', 'refund_credits');

        $reversal = $this->getLastEntity('reversal', true);

        $refund = $this->getLastEntity('refund', true);

        $refundId = substr($refund['id'], strpos($refund['id'], "_") + 1);

        $this->assertEquals($reversal['merchant_id'], '10000000000000');

        $this->assertEquals($reversal['customer_refund_id'], 'rfnd_' . $refundId);

        $this->assertEquals($reversal['initiator_id'], 'acc_' . $this->linkedAccountId);

        $this->assertEquals($accOldBalance - $amountReversed, $this->getBalance($this->linkedAccountId));

        $this->assertEquals($marketplaceOldBalance + $amountReversed, $this->getBalance('10000000000000'));

        $this->assertEquals($marketplaceOldCredits - $amountReversed, $marketplaceNewCredits);
    }

    // RM.refund_source=credits & LA.refund_source=credits
    public function testLinkedAccountReversalAndCustomerRefundCase4()
    {
        $testName = 'testLinkedAccountReversalAndCustomerRefund';

        $data = $this->setUpForReversalsTests($testName, RefundSource::CREDITS, RefundSource::CREDITS);

        $amountReversed = $data['request']['content']['amount'];

        $marketplaceOldBalance = $this->getBalance('10000000000000');

        $marketplaceOldCredits = $this->getBalanceForType('10000000000000', 'refund_credits');

        $accOldCredits = $this->getBalanceForType($this->linkedAccountId, 'refund_credits');

        $this->setAuthForLinkedAccount();

        $this->startTest($data);

        $marketplaceNewCredits = $this->getBalanceForType('10000000000000', 'refund_credits');

        $accNewCredits = $this->getBalanceForType($this->linkedAccountId, 'refund_credits');

        $reversal = $this->getLastEntity('reversal', true);

        $refund = $this->getLastEntity('refund', true);

        $refundId = substr($refund['id'], strpos($refund['id'], "_") + 1);

        $this->assertEquals($reversal['merchant_id'], '10000000000000');

        $this->assertEquals($reversal['customer_refund_id'], 'rfnd_' . $refundId);

        $this->assertEquals($reversal['initiator_id'], 'acc_' . $this->linkedAccountId);

        $this->assertEquals($accOldCredits - $amountReversed, $accNewCredits);

        $this->assertEquals($marketplaceOldBalance + $amountReversed, $this->getBalance('10000000000000'));

        $this->assertEquals($marketplaceOldCredits - $amountReversed, $marketplaceNewCredits);
    }

    // Reveral + Customer Refund initiated by Route Merchant
    public function testRouteMerchantReversalAndCustomerRefund()
    {
        $data = $this->setUpForReversalsTests(__FUNCTION__, RefundSource::CREDITS, RefundSource::CREDITS);

        $amountReversed = $data['request']['content']['amount'];

        $marketplaceOldBalance = $this->getBalance('10000000000000');

        $marketplaceOldCredits = $this->getBalanceForType('10000000000000', 'refund_credits');

        $accOldBalance = $this->getBalance($this->linkedAccountId);

        $accOldCredits = $this->getBalanceForType($this->linkedAccountId, 'refund_credits');

        $this->ba->proxyAuth();

        $this->startTest($data);

        $marketplaceNewCredits = $this->getBalanceForType('10000000000000', 'refund_credits');

        $accNewCredits = $this->getBalanceForType($this->linkedAccountId, 'refund_credits');

        $reversal = $this->getLastEntity('reversal', true);

        $refund = $this->getLastEntity('refund', true);

        $refundId = substr($refund['id'], strpos($refund['id'], "_") + 1);

        $this->assertEquals($reversal['merchant_id'], '10000000000000');

        $this->assertEquals($reversal['customer_refund_id'], 'rfnd_' . $refundId);

        $this->assertEquals($reversal['merchant_id'], '10000000000000');

        $this->assertEquals($reversal['initiator_id'], '10000000000000');

        $this->assertEquals($accOldBalance, $this->getBalance($this->linkedAccountId));

        $this->assertEquals($marketplaceOldBalance + $amountReversed, $this->getBalance('10000000000000'));

        $this->assertEquals($marketplaceOldCredits - $amountReversed, $marketplaceNewCredits);

        $this->assertEquals($accOldCredits- $amountReversed, $accNewCredits);
    }

    public function testLinkedAccountReversalWithoutPermission()
    {
        $data = $this->setTransferIdInRequest(__FUNCTION__);

        $this->setAuthForLinkedAccount();

        $this->startTest($data);
    }

    // Insufficient Balance on LA
    public function testLinkedAccountReversalInsufficientBalance()
    {
        $data = $this->setTransferIdInRequest(__FUNCTION__);

        $this->fixtures->merchant->addFeatures([FeatureConstants::ALLOW_REVERSALS_FROM_LA], $this->linkedAccountId);

        $this->fixtures->merchant->editBalance(10, $this->linkedAccountId);

        $this->setAuthForLinkedAccount();

        $this->startTest($data);
    }

    public function testLinkedAccountReversalInsufficientRefundCredits()
    {
        $data = $this->setTransferIdInRequest(__FUNCTION__);

        $this->fixtures->merchant->addFeatures([FeatureConstants::ALLOW_REVERSALS_FROM_LA], $this->linkedAccountId);

        $this->setRefundSourceForMarketplaceAnddAccount(RefundSource::BALANCE, RefundSource::CREDITS);

        $this->setRefundCreditsForMarketplaceAnddAccount(10, 10);

        $lastOldReversal =  $this->getLastEntity('reversal', true);

        $this->setAuthForLinkedAccount();

        $this->startTest($data);

        $lastNewReversal =  $this->getLastEntity('reversal', true);

        $this->assertEquals($lastOldReversal['id'], $lastNewReversal['id']);
    }

    public function testLinkedAccountCustomerRefundInsufficientMarketplaceCredits()
    {
        $testName = 'testLinkedAccountReversalInsufficientRefundCredits';

        $data = $this->setTransferIdInRequest($testName);

        $this->fixtures->merchant->addFeatures([FeatureConstants::ALLOW_REVERSALS_FROM_LA], $this->linkedAccountId);

        $this->setRefundSourceForMarketplaceAnddAccount(RefundSource::CREDITS, RefundSource::BALANCE);

        $this->setRefundCreditsForMarketplaceAnddAccount(10, 10000);

        $lastOldReversal =  $this->getLastEntity('reversal', true);

        $this->setAuthForLinkedAccount();

        $this->startTest($data);

        $lastNewReversal =  $this->getLastEntity('reversal', true);

        $this->assertEquals($lastOldReversal['id'], $lastNewReversal['id']);
    }

    public function testLinkedAccountReversalInvalidTransfer()
    {
        $this->fixtures->merchant->addFeatures([FeatureConstants::ALLOW_REVERSALS_FROM_LA], $this->linkedAccountId);

        $account = $this->fixtures->create('merchant:marketplace_account', ['id' => '10000000000002']);

        $merchantDetailAttributes =  [
            'merchant_id'   => $account['id'],
            'contact_email' => $account['email'],
            'activation_status' => "activated",
            'bank_details_verification_status'  => 'verified'
        ];

        $this->fixtures->create('merchant_detail:associate_merchant', $merchantDetailAttributes);

        $payment = $this->doAuthAndCapturePayment();

        $transfers[0] = [
            'account' => 'acc_' . $account->getId(),
            'amount'  => 1000,
            'currency'=> 'INR',
        ];

        $transfers = $this->transferPayment($payment['id'], $transfers);

        $transferId = $transfers['items'][0]['id'];

        $data = $this->testData[__FUNCTION__];

        $data['request']['url'] = sprintf($data['request']['url'], $transferId);

        $this->setAuthForLinkedAccount();

        $this->startTest();
    }

    public function testCreateReversalFromBatch()
    {
        $transferId = $this->createPaymentAndTransfer();

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/transfers/' . $transferId . '/reversals/batch';

        $this->ba->batchAuth();
        $this->runRequestResponseFlow($testData);

        $transfer = $this->getDbLastEntity('transfer');
        $this->assertEquals('partially_reversed', $transfer['status']);
        $this->assertEquals(200, $transfer['amount_reversed']);

        $reversal = $this->getDbLastEntity('reversal');
        $this->assertNotNull($reversal);
        $this->assertEquals('transfer', $reversal['entity_type']);
        $this->assertEquals($transferId, 'trf_' . $reversal['entity_id']);
        $this->assertEquals(200, $reversal['amount']);

        $testData['request']['content']['amount'] = null;
        $testData['response']['content']['amount'] = 800;

        $this->ba->batchAuth();
        $this->runRequestResponseFlow($testData);

        $transfer->reload();
        $this->assertEquals('reversed', $transfer['status']);
        $this->assertEquals(1000, $transfer['amount_reversed']);

        $reversal = $this->getDbLastEntity('reversal');
        $this->assertNotNull($reversal);
        $this->assertEquals('transfer', $reversal['entity_type']);
        $this->assertEquals($transferId, 'trf_' . $reversal['entity_id']);
        $this->assertEquals(800, $reversal['amount']);
    }

    public function testRearchPaymentTransferMarketplace()
    {
        $this->enablePgRouterConfig();

        $transaction = $this->fixtures->create('transaction', [
            'entity_id' =>'GfnS1Fj048VHo2',
            'type' =>'payment',
            'merchant_id' =>'10000000000000',
            'amount' =>50000,
            'fee' =>1000,
            'mdr' =>1000,
            'tax' =>0,
            'pricing_rule_id' => NULL,
            'debit' =>0,
            'credit' =>49000,
            'currency' =>'INR',
            'balance' =>2025400,
            'gateway_amount' => NULL,
            'gateway_fee' =>0,
            'gateway_service_tax' =>0,
            'api_fee' =>0,
            'gratis' =>FALSE,
            'fee_credits' =>0,
            'escrow_balance' =>0,
            'channel' =>'axis',
            'fee_bearer' =>'platform',
            'fee_model' =>'prepaid',
            'credit_type' =>'default',
            'on_hold' =>FALSE,
            'settled' =>FALSE,
            'settled_at' =>1614641400,
            'gateway_settled_at' => NULL,
            'settlement_id' => NULL,
            'reconciled_at' => NULL,
            'reconciled_type' => NULL,
            'balance_id' =>'10000000000000',
            'reference3' => NULL,
            'reference4' => NULL,
            'balance_updated' =>TRUE,
            'reference6' => NULL,
            'reference7' => NULL,
            'reference8' => NULL,
            'reference9' => NULL,
            'posted_at' => NULL,
            'created_at' =>1614262078,
            'updated_at' =>1614262078,

        ]);

        $hdfc = $this->fixtures->create('hdfc', [
            'payment_id' => 'GfnS1Fj048VHo2',
            'refund_id' => NULL,
            'gateway_transaction_id' => 749003768256564,
            'gateway_payment_id' => NULL,
            'action' => 5,
            'received' => TRUE,
            'amount' => '500',
            'currency' => NULL,
            'enroll_result' => NULL,
            'status' => 'captured',
            'result' => 'CAPTURED',
            'eci' => NULL,
            'auth' => '999999',
            'ref' => '627785794826',
            'avr' => 'N',
            'postdate' => '0225',
            'error_code2' => NULL,
            'error_text' => NULL,
            'arn_no' => NULL,
            'created_at' => 1614275082,
            'updated_at' => 1614275082,
        ]);

        $card = $this->fixtures->create('card', [
                'merchant_id' =>'10000000000000',
                'name' =>'Harshil',
                'expiry_month' =>12,
                'expiry_year' =>2024,
                'iin' =>'401200',
                'last4' =>'3335',
                'length' =>'16',
                'network' =>'Visa',
                'type' =>'credit',
                'sub_type' =>'consumer',
                'category' =>'STANDARD',
                'issuer' =>'HDFC',
                'international' =>FALSE,
                'emi' =>TRUE,
                'vault' =>'rzpvault',
                'vault_token' =>'NDAxMjAwMTAzODQ0MzMzNQ==',
                'global_fingerprint' =>'==QNzMzM0QDOzATMwAjMxADN',
                'trivia' => NULL,
                'country' =>'IN',
                'global_card_id' => NULL,
                'created_at' =>1614256967,
                'updated_at' =>1614256967,
        ]);

        $pgService = \Mockery::mock('RZP\Services\PGRouter')->makePartial();

        $this->app->instance('pg_router', $pgService);

        $paymentData = [
            'body' => [
                "data" => [
                    "payment" => [
                        'id' =>'GfnS1Fj048VHo2',
                        'merchant_id' =>'10000000000000',
                        'amount' =>50000,
                        'currency' =>'INR',
                        'base_amount' =>50000,
                        'method' =>'card',
                        'status' =>'captured',
                        'two_factor_auth' =>'not_applicable',
                        'order_id' => NULL,
                        'invoice_id' => NULL,
                        'transfer_id' => NULL,
                        'payment_link_id' => NULL,
                        'receiver_id' => NULL,
                        'receiver_type' => NULL,
                        'international' =>FALSE,
                        'amount_authorized' =>50000,
                        'amount_refunded' =>0,
                        'base_amount_refunded' =>0,
                        'amount_transferred' =>0,
                        'amount_paidout' =>0,
                        'refund_status' => NULL,
                        'description' =>'description',
                        'card_id' =>$card->getId(),
                        'bank' => NULL,
                        'wallet' => NULL,
                        'vpa' => NULL,
                        'on_hold' =>FALSE,
                        'on_hold_until' => NULL,
                        'emi_plan_id' => NULL,
                        'emi_subvention' => NULL,
                        'error_code' => NULL,
                        'internal_error_code' => NULL,
                        'error_description' => NULL,
                        'global_customer_id' => NULL,
                        'app_token' => NULL,
                        'global_token_id' => NULL,
                        'email' =>'a@b.com',
                        'contact' =>'+919918899029',
                        'notes' =>[
                            'merchant_order_id' =>'id',
                        ],
                        'transaction_id' => $transaction->getId(),
                        'authorized_at' =>1614253879,
                        'auto_captured' =>FALSE,
                        'captured_at' =>1614253880,
                        'gateway' =>'hdfc',
                        'terminal_id' =>'1n25f6uN5S1Z5a',
                        'authentication_gateway' => NULL,
                        'batch_id' => NULL,
                        'reference1' => NULL,
                        'reference2' => NULL,
                        'cps_route' =>0,
                        'signed' =>FALSE,
                        'verified' => NULL,
                        'gateway_captured' =>TRUE,
                        'verify_bucket' =>0,
                        'verify_at' =>1614253880,
                        'callback_url' => NULL,
                        'fee' =>1000,
                        'mdr' =>1000,
                        'tax' =>0,
                        'otp_attempts' => NULL,
                        'otp_count' => NULL,
                        'recurring' =>FALSE,
                        'save' =>FALSE,
                        'late_authorized' =>FALSE,
                        'convert_currency' => NULL,
                        'disputed' =>FALSE,
                        'recurring_type' => NULL,
                        'auth_type' => NULL,
                        'acknowledged_at' => NULL,
                        'refund_at' => NULL,
                        'reference13' => NULL,
                        'settled_by' =>'Razorpay',
                        'reference16' => NULL,
                        'reference17' => NULL,
                        'created_at' =>1614253879,
                        'updated_at' =>1614253880,
                        'captured' =>TRUE,
                        'reference2' => '12343123',
                        'entity' =>'payment',
                        'fee_bearer' =>'platform',
                        'error_source' => NULL,
                        'error_step' => NULL,
                        'error_reason' => NULL,
                        'dcc' =>FALSE,
                        'gateway_amount' =>50000,
                        'gateway_currency' =>'INR',
                        'forex_rate' => NULL,
                        'dcc_offered' => NULL,
                        'dcc_mark_up_percent' => NULL,
                        'dcc_markup_amount' => NULL,
                        'mcc' =>FALSE,
                        'forex_rate_received' => NULL,
                        'forex_rate_applied' => NULL,
                    ]
                ]
            ]
        ];

        $this->cpsCount = 0;

        $pgService->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'), Mockery::type('bool'), Mockery::type('int'), Mockery::type('bool'))
            ->andReturnUsing(function (string $endpoint, string $method, array $data, bool $throwExceptionOnFailure, int $timeout, bool $retry) use ($card, $transaction, $paymentData)
            {
                if ($method === 'GET')
                {
                    if ($this->cpsCount === 1)
                    {
                        $paymentData['body']['data']['payment']['amount_transferred'] = 1000;
                    }

                    return $paymentData;
                }

                if ($method === 'POST')
                {
                    return [];
                }

            });


        $pgService->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'), Mockery::type('bool'))
            ->andReturnUsing(function (string $endpoint, string $method, array $data, bool $throwExceptionOnFailure) use ($card, $transaction, $paymentData)
            {
                if ($method === 'GET')
                {
                    if ($this->cpsCount === 1)
                    {
                        $paymentData['body']['data']['payment']['amount_transferred'] = 1000;
                    }

                    return  $paymentData;

                }

                if ($method === 'POST')
                {
                    $this->cpsCount += 1;

                    if ($this->cpsCount === 1)
                    {
                        $this->assertEquals($data['amount_transferred'], 1000);    
                    }

                    if ($this->cpsCount === 2)
                    {
                        $this->assertEquals($data['amount_transferred'], 800);    
                    }
                    
                    return [];
                }

            });

        $testData = & $this->testData['testPaymentAfterTransferReversal'];

        $testData['request']['url'] = '/payments/' . 'pay_GfnS1Fj048VHo2' . '/transfers';

        $this->ba->privateAuth();

        $transfers = $this->startTest($testData);

        $this->createReversal($transfers['items'][0]['id'], 200);
    }

    // ---- Helpers -----

    protected function createPaymentAndTransfer()
    {
        $payment = $this->doAuthAndCapturePayment();

        $transfers[0] = [
            'account' => 'acc_' . $this->linkedAccountId,
            'amount'  => 1000,
            'currency'=> 'INR',
        ];

        $transfers = $this->transferPayment($payment['id'], $transfers);

        $transferId = $transfers['items'][0]['id'];

        return $transferId;
    }

    protected function setTransferIdInRequest(string $testName)
    {
        $transferId = $this->createPaymentAndTransfer();

        $data = $this->testData[$testName];

        $data['request']['url'] = sprintf($data['request']['url'], $transferId);

        return $data;
    }

    protected function setUpDataForLAReveralTests(string $testName): array
    {
        $data = $this->setTransferIdInRequest($testName);

        $transfer =  $this->getLastEntity('transfer', true);

        $amountReversed = $data['request']['content']['amount'];

        $data['response']['content'] = [
            'entity'        => 'reversal',
            'transfer_id'   => $transfer['id'],
            'amount'        => $amountReversed,
            'currency'      => 'INR'
        ];

        return $data;
    }

    protected function setUpForReversalsTests(
        string $testName,
        string $marketplaceRefunsSource = RefundSource::BALANCE,
        string $accRefunsSource = RefundSource::BALANCE,
        bool $allowLAReversals = true)
    {
        $data = $this->setUpDataForLAReveralTests($testName);

        $this->setRefundCreditsForMarketplaceAnddAccount();

        $this->setRefundSourceForMarketplaceAnddAccount($marketplaceRefunsSource, $accRefunsSource);

        if ($allowLAReversals === true)
        {
            $this->fixtures->merchant->addFeatures([FeatureConstants::ALLOW_REVERSALS_FROM_LA], $this->linkedAccountId);
        }

        return $data;
    }

    protected function setRefundSourceForMarketplaceAnddAccount(
        string $marketplaceRefunsSource = RefundSource::BALANCE,
        string $accRefunsSource = RefundSource::BALANCE)
    {
        $this->fixtures->merchant->edit('10000000000000', ['refund_source' => $marketplaceRefunsSource]);

        $this->fixtures->merchant->edit($this->linkedAccountId, ['refund_source' => $accRefunsSource]);
    }

    protected function setRefundCreditsForMarketplaceAnddAccount(int $marketplaceCredits = 100000, int $accCredits = 100000)
    {
        $this->fixtures->merchant->editRefundCredits($marketplaceCredits, '10000000000000');

        $this->fixtures->merchant->editRefundCredits($accCredits, $this->linkedAccountId);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => $marketplaceCredits, 'type' => 'refund']);

        $this->fixtures->create('credits', ['merchant_id' => $this->linkedAccountId, 'value' => $accCredits, 'type' => 'refund']);
    }

    protected function setAuthForLinkedAccount($mid = null)
    {
        $mid = $mid ?? $this->linkedAccountId;

        $user = $this->fixtures->user->createUserForMerchant($mid, [], Role::LINKED_ACCOUNT_OWNER);

        $this->ba->proxyAuth('rzp_test_' . $mid , $user->getId());
    }

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

    protected function createReversal(string $id, $amount = null, array $notes = [], array $laNotes = [])
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

        if (empty($laNotes) === false)
        {
            $request['content']['linked_account_notes'] = $laNotes;
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

        $this->assertEquals($transaction['balance_id'], '10000000000000');
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

    protected function getBalanceForType(string $accountId, $type)
    {
        return $this->getEntityById('balance', $accountId, true)[$type];
    }

    protected function getTransferPayment(string $transferId) : array
    {
        Transfer\Entity::verifyIdAndSilentlyStripSign($transferId);

        $payments = $this->getEntities('payment', ['transfer_id' => $transferId], true);

        $this->assertEquals(1, count($payments['items']));

        return $payments['items'][0];
    }

    protected function getReversalRefund(string $reversalId): array
    {
        ReversalEntity::verifyIdAndSilentlyStripSign($reversalId);

        $refunds = $this->getDbEntities('refund', ['reversal_id' => $reversalId], 'test');

        $refunds = $refunds->toArrayPublic();

        $this->assertEquals(1, count($refunds['items']));

        return $refunds['items'][0];
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

        $txnId = str_after($txn['id'], 'txn_');

        return $txnId;;
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
