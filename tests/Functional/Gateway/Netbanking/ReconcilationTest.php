<?php

namespace RZP\Tests\Functional\Gateway\Netbanking;

use Mail;
use Mockery;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class ReconcilationTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/ReconcilationTestData.php';

        parent::setUp();

        $this->ba->appAuth();

        $this->fixtures->create('terminal:shared_netbanking_rbl_terminal');
    }

    public function testRblManualReconcilation()
    {
        $payment = $this->createPayment('netbanking_rbl');

        $netbanking = $this->createNetbanking($payment['id'], 'RATN');

        $fileContents = $this->generateFile('rbl', []);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile('NetbankingRbl', $uploadedFile);

        $gatewayEnttiy = $this->getLastEntity('netbanking', true);

        $this->assertEquals('309002069863', $gatewayEnttiy['account_number']);

        $this->assertEquals('309001141935', $gatewayEnttiy['credit_account_number']);
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


    protected function createPayment($gateway)
    {
        $paymentAttributes = [
            'gateway' => $gateway
        ];

        $payment = $this->fixtures->create('payment:authorized', $paymentAttributes);

        return $payment;
    }

    protected function createNetbanking($paymentId, $bank)
    {
        $netbankingAttributes = [
            'payment_id'      => $paymentId,
            'bank'            => $bank,
            'caps_payment_id' => strtoupper($paymentId),
            'bank_payment_id' => 99999,
            'status'          => 'SUC'
        ];

        $netbanking = $this->fixtures->create('netbanking', $netbankingAttributes);
    }

    protected function generateFile($bank, $input)
    {
        $request = [
            'url'     => '/gateway/mock/reconcilation/' . $bank,
            'content' => $input,
            'method'  => 'POST'
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }
}
