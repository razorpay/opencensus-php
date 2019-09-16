<?php

namespace RZP\Tests\Functional\Lambda;

use Excel;
use Config;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Batch\Status;
use RZP\Reconciliator\RequestProcessor\Base;
use RZP\Tests\Functional\Batch\BatchTestTrait;


class CrawlerReconTest extends TestCase
{
    use BatchTestTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/NetbankingReconciliationTestData.php';

        parent::setUp();

        $this->gateway = '';
    }

    public function testCubCrawlerReconciliation()
    {
        $this->gateway = 'netbanking_cub';

        $payment = $this->createPayment('netbanking_cub', ['id'=>'DEelpRi0HMBGOi', 'amount'=>100]);

        $this->createNetbanking($payment['id'], 'CUB', 'S');

        $this->reconcile('NetbankingCub');

        $gatewayEntity = $this->getDbLastEntity('netbanking');

        $this->assertEquals($gatewayEntity['bank_payment_id'], 99999);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertTrue($transactionEntity['reconciled_at'] !== null);

        //$this->assertBatchStatus(Status::PROCESSED);
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



    protected function reconcile($gateway)
    {
        $this->ba->cronAuth();

        $input = [
            'gateway'          => $gateway,
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
