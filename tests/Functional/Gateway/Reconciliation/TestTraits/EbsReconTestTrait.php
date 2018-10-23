<?php
namespace RZP\Tests\Functional\Gateway\Reconciliation\TestTraits;

use Carbon\Carbon;
use RZP\Gateway\Ebs;
use RZP\Models\Batch;
use RZP\Models\Payment;

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

        $file = $this->writeToCsvFile($entries, 'EBS_SETTLEMENT_DETAILS');
        $this->runForFiles([$file], 'Ebs');

        $payment->reload();
        $this->assertSame(Payment\Status::AUTHORIZED, $payment->getStatus());

        $transaction = $this->getDbLastEntity('transaction');
        $this->assertSame($payment->getId(), $transaction->getEntityId());

        // Recon must fill gateway payment entries for failed payments
        $ebs->reload();
        $this->assertSame($entries[0]['transactionid'], $ebs->getGatewayTransactionId());
        $this->assertSame($entries[0]['paymentid'], $ebs->getGatewayPaymentId());

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
        $this->assertFalse($transaction->isReconciled());

        $this->assertBatchStatus(Batch\Status::PROCESSED);
    }

    private function overrideEbsPayment(Payment\Entity $payment, Ebs\Entity $ebs, array $override = [])
    {
        $amounts = $this->parseEbsReconFileAmount($payment->getAmount(), 'payment');

        $facade = [
            'transactionid'    => (string) $ebs->transaction_id ?? random_integer(8),
            'paymentid'        => (string) $ebs->getGatewayPaymentId() ?? random_integer(8),
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

    private function overrideEbsRefund(Payment\Entity $payment, Ebs\Entity $ebs, array $override = [])
    {
        $amounts = $this->parseEbsReconFileAmount($payment->getAmount(), 'refund');

        $facade = [
            'transactionid'    => (string) $ebs->transaction_id ?? random_integer(8),
            'paymentid'        => (string) $ebs->getGatewayPaymentId() ?? random_integer(8),
            'merchant_refno'   => $ebs->getPaymentId(),
            'txn_date'         => Carbon::createFromTimestamp($payment->getCreatedAt())->format('d/m/Y'),
            'settlement_date'  => Carbon::createFromTimestamp($payment->getCreatedAt())->addDay()->format('d/m/Y'),
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
