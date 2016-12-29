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

    public function testRefundAmountGreaterThanTransferred()
    {
        ; // ?
    }

    public function testFullRefund()
    {
        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 1000
        ];

        $transfers = $this->transferPayment($this->payment['id'], $transfers);

        $transferId = explode('_', $transfers['items'][0]['id'])[1];

        $this->refundPayment($this->payment['id']);

        $this->assertEquals(0, $this->getAccountBalance('10000000000001'));

        $this->checkReverseTransfersSingle($transfers['items']);

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
            'amount'  => 1000
        ];
        $transfers[1] = [
            'account' => 'acc_10000000000002',
            'amount'  => 12000
        ];

        $transfers = $this->transferPayment($this->payment['id'], $transfers);

        $transferId = explode('_', $transfers['items'][1]['id'])[1];

        $this->refundPayment($this->payment['id']);

        $this->assertEquals(0, $this->getAccountBalance('10000000000001'));

        $this->assertEquals(0, $this->getAccountBalance('10000000000002'));

        $this->checkReverseTransfersSingle($transfers['items']);

        $transferEntity = $this->getEntityById('transfer', $transferId, true);

        $this->assertEquals(12000, $transferEntity['amount_reversed']);

        // On a zero-transfer fee plan
        $this->assertEquals(0, $this->getAccountBalance('10000000000001'));

        $this->assertEquals(0, $this->getAccountBalance('10000000000002'));
    }

    // Payment created with multiple transfers.
    // A partial refund on this payment should fail if reversals attribte
    // is not sent.
    public function testPartialRefundMultipleTransfersReversalsNotDefined()
    {
        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 1000
        ];
        $transfers[1] = [
            'account' => 'acc_10000000000002',
            'amount'  => 12000
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
        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 1000
        ];

        $transfers = $this->transferPayment($this->payment['id'], $transfers);

        $this->refundPayment($this->payment['id'], 2000);

        $this->checkReverseTransfersSingle($transfers['items']);

        $this->assertEquals(0, $this->getAccountBalance('10000000000001'));
    }

    public function testPartialRefundReversalsDefined()
    {
        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 1000
        ];
        $transfers[1] = [
            'account' => 'acc_10000000000002',
            'amount'  => 12000
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
        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 20000
        ];
        $transfers[1] = [
            'account' => 'acc_10000000000002',
            'amount'  => 10000
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

        $this->checkReverseTransfersSingle($transfers['items'], $reversals);

        $this->assertEquals(20000 - 2000, $this->getAccountBalance('10000000000001'));

        $this->assertEquals(10000 - 1000, $this->getAccountBalance('10000000000002'));

        $paymentEntity = $this->getEntityById('payment', explode('_', $this->payment['id'])[1], true);

        // sd($paymentEntity);

        $this->assertEquals(30000, $paymentEntity['amount_transferred']);

        $this->assertEquals(50000, $paymentEntity['amount_refunded']);
    }

    // Use only when a single refund reversal is made per transfer.
    protected function checkReverseTransfersSingle($transfers = [], $reversals = [])
    {
        $index = 0;

        foreach ($transfers as $transfer)
        {
            $id = $transfer['id'];

            Transfer\Entity::verifyIdAndSilentlyStripSign($id);

            $content = $this->getEntities('reverse_transfer', ['transfer_id' => $id], true)['items'][0];

            $expected = [
                'transfer_id'   => $transfer['id'],
                'merchant_id'   => '10000000000000',
                'amount'        => $reversals[$index]['amount'] ?? $transfer['amount'],
                'entity'        => 'reverse_transfer',
            ];

            $this->assertArraySelectiveEquals($expected, $content);

            ++$index;
        }
    }
}
