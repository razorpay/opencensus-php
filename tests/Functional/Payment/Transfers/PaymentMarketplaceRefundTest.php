<?php

namespace RZP\Tests\Functional\Payment\Transfers;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Payment\Transfers\TransferTrait;
use RZP\Models\Transfer;

class PaymentMarketplaceRefundTest extends TestCase
{
    use PaymentTrait;
    use TransferTrait;

    // @todo: Clean up all test cases
    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PaymentMarketplaceRefundTestData.php';

        parent::setUp();

        $this->payment = $this->doAuthAndCapturePayment();

        $this->fixtures->create('merchant:marketplace_account');
        $this->fixtures->create('merchant:marketplace_account', ['id' => '10000000000002']);

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

        $this->refundPayment($this->payment['id'], null, [], true);

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

        $this->refundPayment($this->payment['id'], null, [], true);

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

        $this->refundPayment($this->payment['id'], 2000, [], true);

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
            $this->refundPayment($this->payment['id'], 20000, [], true);
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

        // sd($paymentEntity);

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
}
