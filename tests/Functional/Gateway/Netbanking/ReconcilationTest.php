<?php

namespace RZP\Tests\Functional\Gateway\Netbanking;

use Mail;
use Mockery;
use Carbon\Carbon;
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

        $this->ba->publicAuth();

        $this->fixtures->create('terminal:shared_netbanking_rbl_terminal');
    }

    public function testRblMailGunReconcilation()
    {

        $var = 'gateway.mock_'.'netbanking_rbl';

        $this->config[$var] = true;

        $paymentAttributes = [
            'gateway' => 'netbanking_rbl'
        ];

        $payment = $this->fixtures->create('payment:authorized', $paymentAttributes);

        $netbankingAttributes = [
            'payment_id' => $payment['id'],
            'bank'       => 'RATN',
            'caps_payment_id' => strtoupper($payment['id']),
            'bank_payment_id' => 99999
        ];

        $netbanking = $this->fixtures->create('netbanking', $netbankingAttributes);

        $data = [
            'id' => $payment['id'],
            'bank_payment_id' => $netbanking['bank_payment_id'],
            'created_at' => $payment['created_at'],
            'status'     => 'SUC',
            'amount'    => $payment['amount'],
        ];

        $fileContents = $this->generateFile('rbl', ['data' => [$data]]);

        s($fileContents);
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
