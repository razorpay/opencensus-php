<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation;

use Mockery;
use Illuminate\Http\UploadedFile;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class NetbankingReconciliationTest extends TestCase
{
    use RequestResponseFlowTrait;

    const ACCOUNT_NUMBER = '309002069863';

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/NetbankingReconciliationTestData.php';

        parent::setUp();

        $this->ba->appAuth();

        $this->gateway = '';
    }

    public function testRblManualReconciliation()
    {
        $this->gateway = 'netbanking_rbl';

        $payment = $this->createPayment('netbanking_rbl');

        $netbanking = $this->createNetbanking($payment['id'], 'RATN');

        $this->mockReconContentFunction(function(& $content, $action = null)
        {
            if ($action === 'claims_data')
            {
                $content['0']['Debit Account'] = self::ACCOUNT_NUMBER;

                $content['0']['Credit Account'] = '309001141935';
            }
        });

        $fileContents = $this->generateFile('rbl', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingRbl', $uploadedFile);

        $gatewayEntity = $this->getLastEntity('netbanking', true);

        $this->assertEquals(self::ACCOUNT_NUMBER, $gatewayEntity['account_number']);

        $this->assertEquals('309001141935', $gatewayEntity['credit_account_number']);
    }

    public function testRblWrongFormatReconciliation()
    {
        $this->gateway = 'netbanking_rbl';

        $payment = $this->createPayment('netbanking_rbl');

        $netbanking = $this->createNetbanking($payment['id'], 'RATN');

        $this->mockReconContentFunction(function(& $content, $action = null)
        {
            if ($action === 'claims_data')
            {
                $content['0']['extra_row'] = 'abc';
            }
        });

        $fileContents = $this->generateFile('rbl', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($uploadedFile)
            {
                 $this->reconcile('NetbankingRbl', $uploadedFile);
            }
        );
    }

    public function testRblFailedPaymentReconciliation()
    {
        $this->gateway = 'netbanking_rbl';

        $this->setMockGatewayTrue();

        $payment = $this->createFailedPayment($this->gateway);

        $netbanking = $this->createNetbanking($payment['id'], 'RATN', 'FAL');

        $fileContents = $this->generateFile('rbl', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingRbl', $uploadedFile);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals($paymentEntity['status'], 'authorized');
    }

    public function testIndusindManualReconciliation()
    {
        $this->gateway = 'netbanking_indusind';

        $payment = $this->createPayment('netbanking_indusind');

        $netbanking = $this->createNetbanking($payment['id'], 'INDB', 'Y');

        $this->mockReconContentFunction(function(& $content, $action = null)
        {
            if ($action === 'claims_data')
            {
                $content['0']['account_number'] = self::ACCOUNT_NUMBER;
            }
        });

        $fileContents = $this->generateFile('indusind', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingIndusind', $uploadedFile);

        $gatewayEntity = $this->getLastEntity('netbanking', true);

        $this->assertEquals(self::ACCOUNT_NUMBER, $gatewayEntity['account_number']);
    }

    public function testPnbManualReconciliation()
    {
        $this->gateway = 'netbanking_pnb';

        $payment = $this->createPayment('netbanking_pnb');

        $netbanking = $this->createNetbanking($payment['id'], 'PUNB', 'S');

        $fileContents = $this->generateFile('pnb', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingPnb', $uploadedFile);

        $transactionEntity = $this->getLastEntity('transaction', true);

        $this->assertTrue($transactionEntity['reconciled_at'] !== null);
    }

    public function testPnbFailedPaymentReconciliation()
    {
        $this->gateway = 'netbanking_pnb';

        $this->setMockGatewayTrue();

        $payment = $this->createFailedPayment($this->gateway);

        $netbanking = $this->createNetbanking($payment['id'], 'PUNB', 'F');

        $fileContents = $this->generateFile('pnb', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingPnb', $uploadedFile);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals($paymentEntity['status'], 'authorized');
    }

    public function testBobManualReconciliation()
    {
        $this->gateway = 'netbanking_bob';

        $payment = $this->createPayment('netbanking_bob', ['amount' => 40000]);

        $netbanking = $this->createNetbanking($payment['id'], 'BARB', 'S');

        $payment = $this->createPayment('netbanking_bob');

        $netbanking = $this->createNetbanking($payment['id'], 'BARB', 'S');

        $fileContents = $this->generateFile('bob', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingBob', $uploadedFile);

        $gatewayEntity = $this->getLastEntity('netbanking', true);

        $this->assertEquals(self::ACCOUNT_NUMBER, $gatewayEntity['account_number']);

        $transactionEntity = $this->getLastEntity('transaction', true);

        $this->assertTrue($transactionEntity['reconciled_at'] !== null);
    }

    protected function reconcile($gateway, $uploadedFile)
    {
        $input = [
            'manual'           => true,
            'gateway'          => $gateway,
            'attachment-count' => 1,
        ];

        $request = [
            'url'     => '/reconciliate',
            'content' => $input,
            'method'  => 'POST',
            'files'   => [
                'attachment-1' => $uploadedFile,
            ],
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function createUploadedFile($file)
    {
        $this->assertFileExists($file);

        $mimeType = "text/plain";

        $uploadedFile = new UploadedFile(
                            $file,
                            $file,
                            $mimeType,
                            filesize($file),
                            null,
                            true
                        );

        return $uploadedFile;
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

    protected function createFailedPayment($gateway)
    {
        $paymentAttributes = [
            'gateway' => $gateway
        ];

        $payment = $this->fixtures->create('payment:netbanking_failed', $paymentAttributes);

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
        $request = [
            'url'     => '/gateway/mock/reconciliation/' . $bank,
            'content' => $input,
            'method'  => 'POST'
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function mockRecon()
    {
        $class = $this->app['gateway']->getReconClass($this->gateway);

        return Mockery::mock($class, [])->makePartial();
    }

    protected function mockReconContentFunction($closure)
    {
        $recon =  $this->mockRecon()
                       ->shouldReceive('content')
                       ->andReturnUsing($closure)
                       ->mock();

        $this->app['gateway']->setRecon($this->gateway, $recon);

        return $recon;
    }

    protected function setMockGatewayTrue()
    {
        $var = 'gateway.mock_'.$this->gateway;

        $this->config[$var] = true;
    }
}
