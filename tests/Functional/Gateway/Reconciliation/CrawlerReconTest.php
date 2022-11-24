<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation;

use RZP\Models\Batch\Status;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\ReconciliationException;
use RZP\Tests\Functional\Batch\BatchTestTrait;


class CrawlerReconTest extends TestCase
{
    use BatchTestTrait;

    protected function setUp(): void
    {

        parent::setUp();

        $this->gateway = '';
    }

    public function testBobCrawlerReconciliation()
    {
        $this->markTestSkipped('skipping this as the flow in not live');

        $this->gateway = 'netbanking_bob';

        $terminal = $this->fixtures->create('terminal:shared_netbanking_bob_terminal');

        $payment = $this->createPayment('netbanking_bob', ['id'=>'D85nLQUuW4i5Jp', 'amount'=>100, 'terminal_id' => $terminal['id']]);

        $this->createNetbanking($payment['id'], 'BOB', 'SUC');

        $response = $this->reconcile('NetbankingBobV2');

        $gatewayEntity = $this->getDbLastEntity('netbanking');

        $this->assertEquals($gatewayEntity['bank_payment_id'], 99999);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertTrue($transactionEntity['reconciled_at'] !== null);

    }

    public function testBobCrawlerReconciliationGatewayFailure()
    {
        $this->gateway = 'netbanking_bob';

        $reconException  = false;

        try
        {
            $this->reconcile('NetbankingBobV2', ['gateway_failure' => true]);
        }
        catch (ReconciliationException $e)
        {
            $reconException = true;
        }

        $this->assertTrue($reconException);
    }

    public function testPaypalCrawlerReconciliation()
    {
        $this->gateway = 'mozart';

        $terminal = $this->fixtures->create('terminal:shared_paypal_terminal');

        $payment = $this->createPayment('wallet_paypal', ['id'=>'DJEN97tL54dTIN', 'amount'=>100, 'currency'=>'USD','method'=>'wallet', 'terminal_id' => $terminal['id']]);

        $this->createWallet($payment['id'], 100,'wallet_paypal','USD', 'capture', 1234567);

        $this->createRefund('wallet_paypal', $payment, ['id'=>'DJGIIHMST4i8G4', 'status'=> \RZP\Models\Payment\Refund\Status::PROCESSED, 'amount' => 100,'currency'=>'USD']);

        $response = $this->reconcile('Paypal');

        $gatewayEntityPayment = $this->getDbLastEntity('mozart');

        $dataPayment = json_decode($gatewayEntityPayment['raw'], true);

        $this->assertEquals($dataPayment['CaptureId'], '74X988560K9095032');

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertTrue($transactionEntity['reconciled_at'] !== null);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testGetSimplCrawlerReconciliation()
    {
        $this->gateway = 'mozart';

        $terminal = $this->fixtures->create('terminal:getsimpl_terminal');

        $payment = $this->createPayment('Getsimpl', ['id'=>'DXSg7YJuXEQs5Q', 'amount'=>100, 'method'=>'paylater', 'terminal_id' => $terminal['id']]);

        $this->createPaylater($payment['id'], 100,'Getsimpl', 'capture', 1234567);

        $this->createRefund('Getsimpl', $payment, ['id'=>'DXSh0fhShgw6np', 'status'=> \RZP\Models\Payment\Refund\Status::PROCESSED, 'amount' => 100]);

        $response = $this->reconcile('Getsimpl');

        $gatewayEntityPayment = $this->getDbLastEntity('mozart');

        $dataPayment = json_decode($gatewayEntityPayment['raw'], true);

        $this->assertEquals($dataPayment['transaction']['id'], '4478925e-5139-4c34-84a7-24858d51fc2c');

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertTrue($transactionEntity['reconciled_at'] !== null);

        $this->assertBatchStatus(Status::PROCESSED);
    }

    public function testCubCrawlerReconciliation()
    {
        $this->gateway = 'netbanking_cub';

        $terminal = $this->fixtures->create('terminal:shared_netbanking_cub_terminal');

        $payment = $this->createPayment('netbanking_cub', ['id'=>'DEelpRi0HMBGOi', 'amount'=>100, 'terminal_id' => $terminal['id']]);

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

    public function testPaypalCrawlerReconciliationGatewayFailure()
    {
        $this->gateway = 'wallet_paypal';

        $reconException  = false;

        try
        {
            $this->reconcile('Paypal', ['gateway_failure' => true]);
        }
        catch (ReconciliationException $e)
        {
            $reconException = true;
        }

        $this->assertTrue($reconException);
    }

    public function testPaypalCrawlerReconciliationCurrencyMissmatch()
    {
        $this->gateway = 'wallet_paypal';

        $reconException  = false;

        try
        {
            $this->reconcile('Paypal', ['currency_missmatch' => true]);
        }
        catch (ReconciliationException $e)
        {
            $reconException = true;
        }

        $this->assertTrue($reconException);
    }

    public function testGetsimplCrawlerReconciliationGatewayFailure()
    {
        $this->gateway = 'mozart';

        $reconException  = false;

        try
        {
            $this->reconcile('Getsimpl', ['gateway_failure' => true]);
        }
        catch (ReconciliationException $e)
        {
            $reconException = true;
        }

        $this->assertTrue($reconException);
    }

    public function testGetsimplCrawlerReconciliationCurrencyMissmatch()
    {
        $this->gateway = 'mozart';

        $reconException  = false;

        try
        {
            $this->reconcile('Getsimpl', ['currency_missmatch' => true]);
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
            'gateway' => $gateway,
            'gateway_captured' => true
        ];

        $paymentAttributes = array_merge($paymentAttributes, $attributes);

        $payment = $this->fixtures->create('payment:authorized', $paymentAttributes);

        return $payment;
    }

    protected function createRefund($gateway, $payment, $attributes = [])
    {
        $refundAttributes = [
            'id'        => $payment['id'],
            'amount'    => $payment['amount'],
            'gateway'   => $gateway,
            'payment'   => $payment,
            'payment_id'=> 'DJEN97tL54dTIN',
        ];

        $refundAttributes = array_merge($refundAttributes, $attributes);

        $refund = $this->fixtures->create('refund:from_payment', $refundAttributes);

        return $refund;
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

    protected function createWallet($paymentId, $amount, $gateway, $currency, $action, $id, $rfndId = null)
    {
        $raw = null;

        if ($action === 'capture')
        {
            $raw = json_encode(['payment_id' => $paymentId,'CaptureId' => '74X988560K9095032','currency'   => $currency]);
        }
        elseif ($action === 'refund')
        {
            $raw = json_encode(['payment_id' => $paymentId,'id' => '4E238155FF697490G','currency'   => $currency]);
        }

        $mozartAttributes = [
            'id'         => $id,
            'payment_id' => $paymentId,
            'gateway'    => $gateway,
            'amount'     => $amount,
            'raw'        => $raw,
            'action'     => $action,
            'refund_id'  => $rfndId,
        ];

        $wallet = $this->fixtures->create('mozart', $mozartAttributes);

        return $wallet;
    }

    protected function createPaylater($paymentId, $amount, $gateway, $action, $id, $rfndId = null)
    {
        $raw = null;

        if ($action === 'capture')
        {
            $raw = json_encode(['payment_id' => $paymentId,'transaction' => ['id' => '4478925e-5139-4c34-84a7-24858d51fc2c']]);
        }
        elseif ($action === 'refund')
        {
            $raw = json_encode(['payment_id' => $paymentId,'transaction_id' => 'e7f87958-abea-4f94-b8d5-b4ccf677e9e2']);
        }

        $mozartAttributes = [
            'id'         => $id,
            'payment_id' => $paymentId,
            'gateway'    => $gateway,
            'amount'     => $amount,
            'raw'        => $raw,
            'action'     => $action,
            'refund_id'  => $rfndId,
        ];

        $wallet = $this->fixtures->create('mozart', $mozartAttributes);

        return $wallet;
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
