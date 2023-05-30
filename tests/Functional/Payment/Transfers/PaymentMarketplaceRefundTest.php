<?php

namespace RZP\Tests\Functional\Payment\Transfers;

use RZP\Models\Transfer;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Payment\Transfers\TransferTrait;

class PaymentMarketplaceRefundTest extends TestCase
{
    use PaymentTrait;
    use TransferTrait;
    use DbEntityFetchTrait;

    // @todo: Clean up all test cases
    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PaymentMarketplaceRefundTestData.php';

        parent::setUp();

        $this->initializeTestSetup();
    }

    protected function initializeTestSetup()
    {
        $this->payment = $this->doAuthAndCapturePayment();

        $account1 = $this->fixtures->create('merchant:marketplace_account');

        $attributes1 =  [
            'merchant_id'   => $account1['id'],
            'contact_email' => $account1['email'],
            'activation_status' => "activated",
            'bank_details_verification_status'  => 'verified'
        ];

        $this->fixtures->create('merchant_detail:associate_merchant', $attributes1);

        $account2 = $this->fixtures->create('merchant:marketplace_account', ['id' => '10000000000002']);

        $attributes2 =  [
            'merchant_id'   => $account2['id'],
            'contact_email' => $account2['email'],
            'activation_status' => "activated",
            'bank_details_verification_status'  => 'verified'
        ];

        $this->fixtures->create('merchant_detail:associate_merchant', $attributes2);

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->ba->privateAuth();
    }

    /**
     * Try refunding a transfer payment using account auth.
     * Transfer payments can only be refunded via Reversals.
     * Direct refunds should fail
     */
    public function testRefundTransferPayment()
    {
        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 1000,
            'currency'=> 'INR',
        ];
        $transfers[1] = [
            'account' => 'acc_10000000000002',
            'amount'  => 500,
            'currency'=> 'INR',
        ];

        $this->transferPayment($this->payment['id'], $transfers);

        // Fetch last payment entity (transfer payment to merchant - 10000000000002)
        $transferPayment = $this->getLastEntity('payment', true);

        $this->ba->addAccountAuth('acc_10000000000002');

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function() use ($transferPayment)
        {
            $this->refundPayment($transferPayment['id']);
        });
    }

    public function testFullRefund()
    {
        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 1000,
            'currency'=> 'INR',
        ];

        $transfers = $this->transferPayment($this->payment['id'], $transfers);

        $transferId = explode('_', $transfers['items'][0]['id'])[1];

        $this->refundPayment($this->payment['id'], null,[], [], true);

        $this->assertEquals(0, $this->getAccountBalance('10000000000001'));

        $this->checkReversalsSingle($transfers['items']);

        $transferPayment = $this->getLastEntity('payment', true);

        $this->assertEquals($transferId, $transferPayment['transfer_id']);

        $this->assertEquals(1000, $transferPayment['amount_refunded']);

        $paymentEntity = $this->getEntityById('payment', explode('_', $this->payment['id'])[1], true);

        $paymentAmountRefunded = $paymentEntity['amount_refunded'];

        $this->assertEquals($this->payment['amount'], $paymentAmountRefunded);

        $transferEntity = $this->getEntityById('transfer', $transferId, true);

        $amountReversed = $transferEntity['amount_reversed'];

        $this->assertEquals(1000, $amountReversed);
    }

    public function testFullRefundMultipleTransfers()
    {
        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 1000,
            'currency'=> 'INR',
        ];
        $transfers[1] = [
            'account' => 'acc_10000000000002',
            'amount'  => 12000,
            'currency'=> 'INR',
        ];

        $transfers = $this->transferPayment($this->payment['id'], $transfers);

        $transferId = explode('_', $transfers['items'][1]['id'])[1];

        $this->refundPayment($this->payment['id'], null,[], [], true);

        $this->assertEquals(0, $this->getAccountBalance('10000000000001'));

        $this->assertEquals(0, $this->getAccountBalance('10000000000002'));

        $this->checkReversalsSingle($transfers['items']);

        $transferEntity = $this->getEntityById('transfer', $transferId, true);

        $this->assertEquals(12000, $transferEntity['amount_reversed']);

        // On a zero-transfer fee plan
        $this->assertEquals(0, $this->getAccountBalance('10000000000001'));

        $this->assertEquals(0, $this->getAccountBalance('10000000000002'));
    }

    /**
     * Tests reverse_all flag for partial refund, single transfer
     */
    public function testReverseAllPartialRefundSingleTransfer()
    {
        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 1000,
            'currency'=> 'INR',
        ];

        $transfers = $this->transferPayment($this->payment['id'], $transfers);

        $this->refundPayment($this->payment['id'], 2000, [], [], true);

        $this->checkReversalsSingle($transfers['items']);

        $this->assertEquals(0, $this->getAccountBalance('10000000000001'));
    }

    /**
     * Tests reverse_all flag for partial refund, multiple transfers
     */
    public function testReverseAllPartialRefundMultipleTransfers()
    {
        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 1000,
            'currency'=> 'INR',
        ];
        $transfers[1] = [
            'account' => 'acc_10000000000002',
            'amount'  => 12000,
            'currency'=> 'INR',
        ];

        $this->transferPayment($this->payment['id'], $transfers);

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function() use ($transfers)
        {
            $this->refundPayment($this->payment['id'], 20000, [], [], true);
        });
    }

    /**
     * Payment created with multiple transfers.
     * A partial refund on this payment should fail if reversals attribte
     * is not sent.
     */
    public function testPartialRefundMultipleTransfersReversalsNotDefined()
    {
        $this->markTestSkipped('reversals array excluded temporarily');

        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 1000,
            'currency'=> 'INR',
        ];
        $transfers[1] = [
            'account' => 'acc_10000000000002',
            'amount'  => 12000,
            'currency'=> 'INR',
        ];

        $this->transferPayment($this->payment['id'], $transfers);

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function() use ($transfers)
        {
            $this->refundPayment($this->payment['id'], 20000);
        });
    }

    // Payment created with single transfers
    // A partial refund on this payment will work since there's only one
    // transfer to reverse
    public function testPartialRefundSingleTransferReversalsNotDefined()
    {
        $this->markTestSkipped('reversals array excluded temporarily');

        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 1000,
            'currency'=> 'INR',
        ];

        $transfers = $this->transferPayment($this->payment['id'], $transfers);

        $this->refundPayment($this->payment['id'], 2000);

        $this->checkReversalsSingle($transfers['items']);

        $this->assertEquals(0, $this->getAccountBalance('10000000000001'));
    }

    public function testPartialRefundReversalsDefined()
    {
        $this->markTestSkipped('reversals array excluded temporarily');

        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 1000,
            'currency'=> 'INR',
        ];
        $transfers[1] = [
            'account' => 'acc_10000000000002',
            'amount'  => 12000,
            'currency'=> 'INR',
        ];

        $transfers = $this->transferPayment($this->payment['id'], $transfers);

        $reversals[0] = [
            'transfer'  => $transfers['items'][0]['id'],
            'amount'    => 350
        ];
        $reversals[1] = [
            'transfer'  => $transfers['items'][1]['id'],
            'amount'    => 450
        ];

        $this->refundPayment($this->payment['id'], 1000, $reversals);

        $this->assertEquals(1000 - 350, $this->getAccountBalance('10000000000001'));

        $this->assertEquals(12000 - 450, $this->getAccountBalance('10000000000002'));
    }

    public function testFullRefundReversalsDefined()
    {
        $this->markTestSkipped('reversals array excluded temporarily');

        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 20000,
            'currency'=> 'INR',
        ];
        $transfers[1] = [
            'account' => 'acc_10000000000002',
            'amount'  => 10000,
            'currency'=> 'INR',
        ];

        $transfers = $this->transferPayment($this->payment['id'], $transfers);

        $reversals[0] = [
            'transfer'  => $transfers['items'][0]['id'],
            'amount'    => 2000
        ];
        $reversals[1] = [
            'transfer'  => $transfers['items'][1]['id'],
            'amount'    => 1000
        ];

        $this->refundPayment($this->payment['id'], null, $reversals);

        $this->checkReversalsSingle($transfers['items'], $reversals);

        $this->assertEquals(20000 - 2000, $this->getAccountBalance('10000000000001'));

        $this->assertEquals(10000 - 1000, $this->getAccountBalance('10000000000002'));

        $paymentEntity = $this->getEntityById('payment', explode('_', $this->payment['id'])[1], true);

        $this->assertEquals(30000, $paymentEntity['amount_transferred']);

        $this->assertEquals(50000, $paymentEntity['amount_refunded']);
    }

    // Use only when a single refund reversal is made per transfer.
    protected function checkReversalsSingle($transfers = [], $reversals = [])
    {
        $index = 0;

        foreach ($transfers as $transfer)
        {
            $id = $transfer['id'];

            Transfer\Entity::verifyIdAndSilentlyStripSign($id);

            $entities = $this->getEntities('reversal',
                                          ['entity_type' => 'transfer', 'entity_id' => $id],
                                            true);
            $content = $entities['items'][0];

            $expected = [
                'transfer_id'   => $transfer['id'],
                'merchant_id'   => '10000000000000',
                'amount'        => $reversals[$index]['amount'] ?? $transfer['amount'],
                'entity'        => 'reversal',
            ];

            $this->assertArraySelectiveEquals($expected, $content);

            ++$index;
        }
    }

    public function testReverseAllOrderTransfers()
    {
        $data = $this->testData['createOrderTransfers'];

        $this->ba->privateAuth();

        $order = $this->runRequestResponseFlow($data);

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order['id'];

        $payment = $this->doAuthAndCapturePayment($payment);

        $data = $this->testData[__FUNCTION__];

        $data['request']['url'] = '/payments/' . $payment['id'] . '/refund';

        $refund = $this->runRequestResponseFlow($data);

        $transfer = $this->getLastEntity('transfer', true);

        $this->assertEquals('reversed', $transfer['status']);
    }

    public function testReverseFailedPaymentTransferUsingReverseAll()
    {
        $this->markTestSkipped('Failing due to PR-37809, will be fixed');

        $data = $this->testData['createOrderTransfers'];
        $this->ba->privateAuth();
        $order = $this->runRequestResponseFlow($data);
        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];
        $payment = $this->doAuthAndCapturePayment($payment);

        $orderTransfer = $this->getDbLastEntity('transfer');
        $this->assertEquals('processed', $orderTransfer['status']);
        $balanceLinkedAccount1 = $this->getDbEntity('balance', ['merchant_id' => 10000000000001]);
        $this->assertEquals(20000, $balanceLinkedAccount1['balance']);

        $this->fixtures->balance->edit('10000000000000', ['balance' => 1]);
        $transfers = [
            [
                'account' => 'acc_10000000000002',
                'amount'  => 5000,
                'currency'=> 'INR',
            ],
        ];
        $this->transferPayment($payment['id'], $transfers);
        $this->fixtures->balance->edit('10000000000000', ['balance' => 123456]);

        $paymentTransfer = $this->getDbLastEntity('transfer');
        $this->assertEquals('failed', $paymentTransfer['status']);
        $balanceLinkedAccount2 = $this->getDbEntity('balance', ['merchant_id' => 10000000000002]);
        $this->assertEquals(0, $balanceLinkedAccount2['balance']);

        $refund = $this->refundPayment($payment['id'], null, [], [], true);

        $orderTransfer->reload();
        $paymentTransfer->reload();
        $balanceLinkedAccount1->reload();
        $balanceLinkedAccount2->reload();
        $balanceMerchant = $this->getDbEntity('balance', ['merchant_id' => 10000000000000]);

        $this->assertEquals($payment['id'], $refund['payment_id']);
        $this->assertEquals(50000, $refund['amount']);
        $this->assertEquals('reversed', $orderTransfer['status']);
        $this->assertEquals('failed', $paymentTransfer['status']);
        $this->assertEquals(0, $balanceLinkedAccount1['balance']);
        $this->assertEquals(0, $balanceLinkedAccount2['balance']);
        $this->assertEquals(123456 + 20000 - 50000, $balanceMerchant['balance']);
    }
}
