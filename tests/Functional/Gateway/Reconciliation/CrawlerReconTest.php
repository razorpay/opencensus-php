<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation;

use Excel;
use Config;
use RZP\Exception\ReconciliationException;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Batch\Status;
use RZP\Reconciliator\RequestProcessor\Base;
use RZP\Tests\Functional\Batch\BatchTestTrait;


class CrawlerReconTest extends TestCase
{
    use BatchTestTrait;

    public function setUp()
    {

        parent::setUp();

        $this->gateway = '';
    }

    public function testCubCrawlerReconciliation()
    {
        $this->gateway = 'netbanking_cub';

        $payment = $this->createPayment('netbanking_cub', ['id'=>'DEelpRi0HMBGOi', 'amount'=>100]);

        $this->createNetbanking($payment['id'], 'CUB', 'S');

        $response = $this->reconcile('NetbankingCub');

        $gatewayEntity = $this->getDbLastEntity('netbanking');

        $this->assertEquals($gatewayEntity['bank_payment_id'], 99999);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertTrue($transactionEntity['reconciled_at'] !== null);

        //$this->assertBatchStatus(Status::PROCESSED);
    }

    public function testCubCrawlerReconciliationNoRecords()
    {
        $this->gateway = 'netbanking_cub';

        $reconException  = false;

        try
        {
            $this->reconcile('NetbankingCub', ['return_no_records' => true]);
        }
        catch (ReconciliationException $e)
        {
            $reconException = true;
        }

        $this->assertTrue($reconException);
    }

    public function testCubCrawlerReconciliationGatewayFailure()
    {
        $this->gateway = 'netbanking_cub';

        $reconException  = false;

        try
        {
            $this->reconcile('NetbankingCub', ['gateway_failure' => true]);
        }
        catch (ReconciliationException $e)
        {
            $reconException = true;
        }

        $this->assertTrue($reconException);
    }

    protected function createPayment($gateway, $attributes = [])
    {
        $paymentAttributes = [
            'gateway' => $gateway
        ];

        $paymentAttributes = array_merge($paymentAttributes, $attributes);

        $payment = $this->fixtures->create('payment:authorized', $paymentAttributes);

        return $payment;
    }

    protected function createNetbanking($paymentId, $bank, $status = 'SUC')
    {
        $netbankingAttributes = [
            'payment_id'      => $paymentId,
            'bank'            => $bank,
            'caps_payment_id' => strtoupper($paymentId),
            'bank_payment_id' => 99999,
            'status'          => $status,
        ];

        $netbanking = $this->fixtures->create('netbanking', $netbankingAttributes);

        return $netbanking;
    }



    protected function reconcile($gateway, $metaInfo = null)
    {
        $this->ba->cronAuth();

        $input = [
            'gateway'          => $gateway,
            'crawler'          => '1',
            'meta_data'        => $metaInfo,
        ];

        $request = [
            'url'     => '/reconciliate',
            'content' => $input,
            'method'  => 'POST',
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }
}
