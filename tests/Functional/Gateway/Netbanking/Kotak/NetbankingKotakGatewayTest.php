<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Kotak;

use Mail;
use Mockery;
use Carbon\Carbon;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;


class NetbankingKotakGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/NetbankingKotakGatewayTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'netbanking_kotak';

        $this->setMockGatewayTrue();

        $this->fixtures->on('test')->create('terminal:shared_netbanking_kotak_terminal');
    }

    public function testPayment()
    {
        $terminal = $this->fixtures->create('terminal:netbanking_kotak_terminal');

        $payment = $this->doNetbankingKotakAuthAndCapturePayment();

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('netbanking', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentNetbankingEntity'], $payment);

        $this->assertArrayHasKey('bank_payment_id', $payment);
        $this->assertTrue(filter_var($payment['bank_payment_id'], FILTER_VALIDATE_INT) !== false);
    }

    public function testPaymentVerify()
    {
        $terminal = $this->fixtures->create('terminal:netbanking_kotak_terminal');

        $payment = $this->doNetbankingKotakAuthAndCapturePayment();

        $payment = $this->getLastEntity('payment', true);

        $content = $this->verifyPayment($payment['id']);

        assert($content['payment']['verified'] === 1);
    }

    public function testRefundsFileGeneration()
    {
        // Make 3 test payments
        $this->testPayment();

        $this->testPayment();

        $this->testPayment();

        $payments = $this->getEntities('payment', [], true);

        $createdAt = Carbon::yesterday('Asia/Kolkata')->addHours(10)->addMinutes(30)->timestamp;

        // Set payment dates to yesterday
        foreach ($payments['items'] as $payment)
        {
            $this->fixtures->edit('payment', $payment['id'], ['created_at' => $createdAt,
                                                              'authorized_at' => $createdAt + 10,
                                                              'captured_at' => $createdAt + 20]);
        }

        // Set the transactions to be reconciled today
        $transactions = $this->getEntities('transaction', [], true);

        $reconciledAt = Carbon::today('Asia/Kolkata')->addHours(5)->addMinutes(13)->timestamp;

        foreach ($transactions['items'] as $transaction)
        {
            $this->fixtures->edit('transaction', $transaction['id'], ['reconciled_at' => $reconciledAt]);
        }

        // Refund a payment
        $lastPayment = $payments['items'][2];

        $refundPayment = $this->refundPayment($lastPayment['id'], 100);

        $refundPayment = $this->refundPayment($lastPayment['id']);

        $refunds = $this->getEntities('refund', [], true);

        $createdAt = Carbon::yesterday('Asia/Kolkata')->addHours(10)->addMinutes(45)->timestamp;

        // Mark refunds as created yesterday
        foreach ($refunds['items'] as $refund)
        {
            $this->fixtures->edit('refund', $refund['id'], ['created_at' => $createdAt]);
        }

        // Mail catch with amount and refund everywhere
        Mail::shouldReceive('queue')
              ->once()
              ->with(
                    Mockery::any(),
                    Mockery::on(function ($data)
                        {
                            $date = Carbon::today('Asia/Kolkata')->format('d-m-Y');

                            $testData = array(
                                'subject' => 'Kotak Netbanking claims and refund files for '.$date,
                                'amount' => [
                                    'claims' => 1500,
                                    'refunds' => 500,
                                    'total' => 1000,
                                ]);

                            $this->assertArraySelectiveEquals($testData, $data);

                            return true;
                        }),
                    Mockery::any()
                );


        $content = $this->generateRefundsExcelForKkbkNB();

        $refundsFileUrl = $content['netbanking_kotak'][0];

        $claimsFileUrl = $content['netbanking_kotak'][1];

        $claimsFileContents = file($claimsFileUrl);

        $refundsFileContents = file($refundsFileUrl);

        // Both files should have 3 lines
        assert(count($claimsFileContents) === 3);

        assert(count($refundsFileContents) === 3);


        $refundsFileName = explode('/', $refundsFileUrl);

        $refundsFileLine = explode('|', $refundsFileContents[0]);

        // Refund file should have name in the first line
        $refundsFileNameId = count($refundsFileName) - 1;

        assert($refundsFileName[$refundsFileNameId] === $refundsFileLine[0]);

        $claimsFileLine1 = explode('|', $claimsFileContents[1]);

        $refundsFileLine1 = explode('|', $refundsFileContents[1]);

        // Both files should have 6 columns in a row
        assert(count($claimsFileLine1) === 6);

        assert(count($refundsFileLine1) === 6);
    }

    protected function generateRefundsExcelForKkbkNB()
    {
        $this->ba->appAuth();

        $request = array(
            'url' => '/refunds/netbanking/excel',
            'method' => 'post',
            'content' => [
                'bank'   => 'KKBK'
            ],
        );

        return $this->makeRequestAndGetContent($request);
    }

    protected function doNetbankingKotakAuthAndCapturePayment()
    {
        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment['bank'] = 'KKBK';
        $payment = $this->doAuthAndCapturePayment($payment);

        return $payment;
    }
}
