<?php
namespace RZP\Tests\Functional\Gateway\Reconciliation\TestTraits;

use Carbon\Carbon;
use RZP\Gateway\Ebs;
use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Models\Base\PublicEntity;

trait EbsReconTestTrait
{
    protected function ebsReconSetup()
    {
        $this->fixtures->create('terminal:shared_ebs_terminal');
        $this->fixtures->merchant->addFeatures('charge_at_will');
        $this->payment = $this->getDefaultNetbankingPaymentArray();
    }

    //For success case of Bill desk reconciliation
    public function testEbsReconFileCapturedPayment()
    {
        $this->ebsReconSetup();

        // Captured payment
        $this->getNewPaymentEntity(false, true);
        $payment = $this->getDbLastPayment();
        $ebs = $this->getDbLastEntity('ebs');
        $transaction = $this->getDbLastEntity('transaction');
        $entries[] = $this->overrideEbsPayment($payment, $ebs);

        $file = $this->writeToCsvFile($entries, 'EBS_SETTLEMENT_DETAILS');
        $this->runForFiles([$file], 'Ebs');

        $transaction->reload();
        //Reconciled at should not be null
        $this->assertSame(650, $transaction['gateway_fee']);
        $this->assertSame(100, $transaction['gateway_service_tax']);
        $this->assertNotNull($transaction['reconciled_at']);
        $this->assertNotNull($transaction['gateway_settled_at']);

        $this->assertBatchStatus(Batch\Status::PROCESSED);
    }

    public function testEbsReconFileFailedPayment()
    {
        $this->ebsReconSetup();

        // Non captured failed payment
        $this->payment['amount'] = 100300;
        $this->payment['bank'] = 'CBIN';
        $this->makeRequestAndCatchException(
            function()
            {
                $this->getNewPaymentEntity();
            });
        $payment = $this->getDbLastPayment();
        $this->assertSame(Payment\Status::FAILED, $payment->getStatus());
        $ebs = $this->getDbLastEntity('ebs');

        $this->assertNull($ebs->getGatewayPaymentId());
        $this->assertNull($ebs->getGatewayTransactionId());
        $entries[] = $this->overrideEbsPayment($payment, $ebs);

        $this->mockServerContentFunction(
            function(& $content, $action = null) use ($entries)
            {
                $replace = [
                    '{{transactionId}}' => $entries[0]['transactionid'],
                    '{{paymentId}}'     => $entries[0]['paymentid'],
                ];

                $content = strtr($content, $replace);
            },
            'ebs');

        $file = $this->writeToCsvFile($entries, 'EBS_SETTLEMENT_DETAILS');
        $this->runForFiles([$file], 'Ebs');

        $payment->reload();
        $this->assertSame(Payment\Status::AUTHORIZED, $payment->getStatus());

        $transaction = $this->getDbLastEntity('transaction');
        $this->assertSame($payment->getId(), $transaction->getEntityId());

        // Verify from recon must fill gateway payment entries for failed payments
        $ebs->reload();
        $this->assertEquals($entries[0]['transactionid'], $ebs->getGatewayTransactionId());
        $this->assertEquals($entries[0]['paymentid'], $ebs->getGatewayPaymentId());

        $this->assertSame(1304, $transaction['gateway_fee']);
        $this->assertSame(201, $transaction['gateway_service_tax']);
        $this->assertNotNull($transaction['reconciled_at']);
        $this->assertNotNull($transaction['gateway_settled_at']);

        $this->assertBatchStatus(Batch\Status::PROCESSED);
    }

    public function testEbsReconRefund()
    {
        $this->ebsReconSetup();

        $this->getNewPaymentEntity(false, true);
        $payment = $this->getDbLastPayment();
        $this->refundPayment($payment->getPublicId());
        $ebs = $this->getDbLastEntity('ebs');
        $entries[] = $this->overrideEbsRefund($payment, $ebs);
        $transaction = $this->getDbLastEntity('transaction');

        $file = $this->writeToCsvFile($entries, 'EBS_SETTLEMENT_DETAILS');
        $this->runForFiles([$file], 'Ebs');

        $transaction->reload();
        $this->assertTrue($transaction->isReconciled());

        $this->assertBatchStatus(Batch\Status::PROCESSED);
    }

    /**
     * We are allowing recon for refunds that can be
     * uniquely identified by given payment id and amount,
     * since, that is all we get in MIS as of the moment.
     * We create a file containing 1 payment and 3 refund
     * rows, the refund row which contains payment id and
     * amount, against which only 1 entry is present, gets
     * successfully reconciled, rest do not.
     */
    public function testEbsCombinedUniqueEntityRecon()
    {
        $this->ebsReconSetup();

        $this->getNewPaymentEntity(false, true);
        $payment = $this->getDbLastPayment();
        $ebs = $this->getDbLastEntity('ebs');
        $refund1 = $this->refundPayment($payment->getPublicId(), '10000');
        $refund2 = $this->refundPayment($payment->getPublicId(), '20000');
        $refund3 = $this->refundPayment($payment->getPublicId(), '20000');

        $refundEntity1 = $this->getDbENtityById('refund', $refund1['id']);
        $refundEntity2 = $this->getDbENtityById('refund', $refund2['id']);
        $refundEntity3 = $this->getDbENtityById('refund', $refund3['id']);

        $entries[] = $this->overrideEbsPayment($payment, $ebs);
        $entries[] = $this->overrideEbsRefund($refundEntity1, $ebs);
        $entries[] = $this->overrideEbsRefund($refundEntity2, $ebs);
        $entries[] = $this->overrideEbsRefund($refundEntity3, $ebs);

        $file = $this->writeToCsvFile($entries, 'EBS_SETTLEMENT_DETAILS');
        $this->runForFiles([$file], 'Ebs');

        $transaction1 = $this->getDbEntity('transaction', ['type' => 'payment', 'entity_id' => $payment['id']]);;
        $transaction2 = $this->getDbEntity('transaction', ['type' => 'refund', 'entity_id' => $refundEntity1['id']]);;
        $transaction3 = $this->getDbEntity('transaction', ['type' => 'refund', 'entity_id' => $refundEntity2['id']]);;
        $transaction4 = $this->getDbEntity('transaction', ['type' => 'refund', 'entity_id' => $refundEntity3['id']]);;

        $this->assertNotNull($transaction1['reconciled_at']);
        $this->assertNotNull($transaction2['reconciled_at']);
        $this->assertNull($transaction3['reconciled_at']);
        $this->assertNull($transaction4['reconciled_at']);

        $this->assertBatchStatus(Batch\Status::PARTIALLY_PROCESSED);
    }

    private function overrideEbsPayment(Payment\Entity $payment, Ebs\Entity $ebs, array $override = [])
    {
        $amounts = $this->parseEbsReconFileAmount($payment->getAmount(), 'payment');

        $facade = [
            'transactionid'    => (string) $ebs->transaction_id ?: random_integer(8),
            'paymentid'        => (string) $ebs->getGatewayPaymentId() ?: random_integer(8),
            'merchant_refno'   => $ebs->getPaymentId(),
            'txn_date'         => Carbon::createFromTimestamp($payment->getCreatedAt())->format('d/m/Y'),
            'settlement_date'  => Carbon::createFromTimestamp($payment->getCreatedAt())->addDay()->format('d/m/Y'),
            'accountid'        => '20640',
            'merchant'         => 'Razorpay',
            'paymentmethod'    => 'Bank of Razorpay',
            'particular'       => 'Captured',
            'credit'           => $amounts['credit'],
            'debit'            => $amounts['debit'],
            'tdr_amt'          => $amounts['tdr'],
            'service_tax'      => $amounts['tax'],
            'net_amt'          => $amounts['net'],
            'description'      => 'STS',
        ];

        return array_merge($facade, $override);
    }

    private function overrideEbsRefund(PublicEntity $entity, Ebs\Entity $ebs, array $override = [])
    {
        $amounts = $this->parseEbsReconFileAmount($entity->getAmount(), 'refund');

        $facade = [
            'transactionid'    => (string) $ebs->transaction_id ?? random_integer(8),
            'paymentid'        => (string) $ebs->getGatewayPaymentId() ?? random_integer(8),
            'merchant_refno'   => $ebs->getPaymentId(),
            'txn_date'         => Carbon::createFromTimestamp($entity->getCreatedAt())->format('d/m/Y'),
            'settlement_date'  => Carbon::createFromTimestamp($entity->getCreatedAt())->addDay()->format('d/m/Y'),
            'accountid'        => '20640',
            'merchant'         => 'Razorpay',
            'paymentmethod'    => 'Bank of Razorpay',
            'particular'       => 'Refunded',
            'credit'           => $amounts['credit'],
            'debit'            => $amounts['debit'],
            'tdr_amt'          => $amounts['tdr'],
            'service_tax'      => $amounts['tax'],
            'net_amt'          => $amounts['net'],
            'description'      => 'STS',
        ];

        return array_merge($facade, $override);
    }

    private function parseEbsReconFileAmount(int $amount, string $type)
    {
        switch ($type)
        {
            case 'payment':
                $credit = -1 * ($amount / 100);
                $output['tdr'] = -0.011 * $credit;
                $output['tax'] = -0.002 * $credit;
                $output['net'] = $credit + ($output['tdr'] + $output['tax']);
                $output['debit'] = 0;
                $output['credit'] = $credit;
                break;
            case 'refund':
                $debit = $amount / 100;
                $output['tdr'] = 0;
                $output['tax'] = 0;
                $output['net'] = $debit;
                $output['debit'] = $debit;
                $output['credit'] = 0;
        }

        return array_map(function($amount)
        {
            return number_format($amount, 2, '.', '');
        },
        $output);
    }
}
