<?php

namespace RZP\Tests\Functional\Payment\Transfers;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Payment\Transfers\TransferTrait;

class PaymentMarketplaceRefundTest extends TestCase
{
    use PaymentTrait;
    use TransferTrait;

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

        $transfer = $this->transferPayment($this->payment['id'], $transfers);

        $transferId = explode('_', $transfer['items'][0]['id'])[1];

        $this->refundPayment($this->payment['id']);

        $this->assertEquals(0, $this->getAccountBalance('10000000000001'));

        $content = $this->getLastEntity('reverse_transfer', true);

        $expected = [
            'transfer_id'   => $transferId,
            'merchant_id'   => '10000000000000',
            'amount'        => 1000,
            'entity'        => 'reverse_transfer',
        ];

        $this->assertArraySelectiveEquals($expected, $content);

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

        $transfer = $this->transferPayment($this->payment['id'], $transfers);

        $transferId = explode('_', $transfer['items'][1]['id'])[1];

        $this->refundPayment($this->payment['id']);

        $this->assertEquals(0, $this->getAccountBalance('10000000000001'));

        $this->assertEquals(0, $this->getAccountBalance('10000000000002'));

        $content = $this->getLastEntity('reverse_transfer', true);

        $expected = [
            'transfer_id'   => $transferId,
            'merchant_id'   => '10000000000000',
            'amount'        => 12000,
            'entity'        => 'reverse_transfer',
        ];

        $this->assertArraySelectiveEquals($expected, $content);

        $transferEntity = $this->getEntityById('transfer', $transferId, true);

        $this->assertEquals(12000, $transferEntity['amount_reversed']);

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

    // Payment created with single transfers.
    // A partial refund on this payment should fail if reversals attribte
    // is not sent.
    public function testPartialRefundSingleTransferReversalsNotDefined()
    {
        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 1000
        ];

        $transfer = $this->transferPayment($this->payment['id'], $transfers);
    }

    public function testPartialRefundReversalsDefined()
    {

    }

    public function testFullRefundReversalsDefined()
    {

    }
}
