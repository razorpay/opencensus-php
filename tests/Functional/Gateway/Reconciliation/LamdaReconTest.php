<?php

namespace RZP\Tests\Functional\Lambda;

use Excel;
use Config;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Batch\Status;
use RZP\Reconciliator\RequestProcessor\Base;
use RZP\Tests\Functional\Batch\BatchTestTrait;


class LambdaReconTest extends TestCase
{
    use BatchTestTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/NetbankingReconciliationTestData.php';

        parent::setUp();

        $this->gateway = '';
    }

    public function testIciciLambdaReconciliation()
    {
        $this->gateway = 'netbanking_icici';

        $terminal = $this->fixtures->create('terminal:shared_netbanking_icici_terminal');

        $payment = $this->createPayment('netbanking_icici', ['terminal_id' => $terminal['id']]);

        $this->createNetbanking($payment['id'], 'ICIC', 'S');

        $fileContents = $this->generateFile('icici', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $uploadedFile->move(storage_path('files/filestore'), basename($fileContents['local_file_path']));

        $this->reconcile('NetbankingIcici', $uploadedFile, basename($fileContents['local_file_path']));

        $gatewayEntity = $this->getDbLastEntity('netbanking');

        $this->assertEquals($gatewayEntity['bank_payment_id'], 99999);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertTrue($transactionEntity['reconciled_at'] !== null);

        $this->assertBatchStatus(Status::PROCESSED);
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

    protected function generateFile($bank, $input)
    {
        $gateway = 'netbanking_' . $bank;

        $request = [
            'url'     => '/gateway/mock/reconciliation/' . $gateway,
            'content' => $input,
            'method'  => 'POST'
        ];

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function reconcile($gateway, $uploadedFile, $local_file_path)
    {
        $arr = explode('/', $local_file_path);

        // set auth so that isLambda() returns true
        $this->ba->h2hAuth();

        $input = [
            'key'              => $gateway . '/' . end($arr),
            'gateway'          => $gateway,
            'attachment-count' => 1,
        ];

        $request = [
            'url'     => '/reconciliate',
            'content' => $input,
            'method'  => 'POST',
            'files'   => [
                Base::ATTACHMENT_HYPHEN_ONE => $uploadedFile,
            ],
        ];

        return $this->makeRequestAndGetContent($request);
    }
}
