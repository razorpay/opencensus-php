<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Kotak;

use Mail;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Gateway\Base\Action;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Netbanking\Kotak\Fields;
use RZP\Gateway\Netbanking\Kotak\Status;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class NetbankingKotakNewGatewayTerminalTest extends TestCase
{
    use PaymentTrait;
    use PartnerTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/NetbankingKotakGatewayTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'netbanking_kotak';

        $this->setMockGatewayTrue();

        $terminalAttrs = [
            'id'                    => 'DrctNbKtkTrmnl',
            'account_type'          => 'enc',
            'gateway_merchant_id'   =>  'OSRAZORPAY',
        ];

        $this->fixtures->create(
            'terminal:netbanking_kotak_terminal',
            $terminalAttrs);

        $terminalAttrs = [
            'id'                    => 'TpvNbKotakTmnl',
            'network_category'      => 'securities',
            'tpv'                   => 1,
            'gateway_merchant_id'   =>  'OTRAZORPAY',
        ];

        $this->terminal = $this->fixtures->create(
            'terminal:shared_netbanking_kotak_terminal',
            $terminalAttrs);
    }

    public function testPayment()
    {
        $payment = $this->doNetbankingKotakAuthAndCapturePayment();

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $payment = $this->getLastEntity('netbanking', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentNewNetbankingEntity1'], $payment);

        $this->assertArrayHasKey('bank_payment_id', $payment);
        $this->assertTrue(filter_var($payment['bank_payment_id'], FILTER_VALIDATE_INT) !== false);
    }

    public function testPartnerPayment()
    {
        list($clientId, $submerchantId) = $this->setUpPartnerAuthForPayment();

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['bank'] = 'KKBK';

        $this->doPartnerAuthPayment($payment, $clientId, $submerchantId);

        $payment = $this->getLastEntity('payment', true);

        $this->assertSame('authorized', $payment['status']);

        $payment = $this->getLastEntity('netbanking', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentNewNetbankingEntity1'], $payment);

        $this->assertArrayHasKey('bank_payment_id', $payment);
        $this->assertTrue(filter_var($payment['bank_payment_id'], FILTER_VALIDATE_INT) !== false);
    }

    public function testAmountTampering()
    {
        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            $content['Amount'] = '1';
        });

        $data = $this->testData[__FUNCTION__];

        $payment = $this->getDefaultNetbankingPaymentArray('KKBK');

        $this->runRequestResponseFlow($data, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testTpvPayment($tpvFeatureEnabled = false)
    {
        if ($tpvFeatureEnabled === false)
        {
            $this->fixtures->merchant->enableTPV();
        }

        $this->fixtures->create('gateway_rule', [
            'method'           => 'netbanking',
            'merchant_id'      => '100000Razorpay',
            'gateway'          => 'netbanking_kotak',
            'issuer'           => 'KKBK',
            'type'             => 'filter',
            'filter_type'      => 'select',
            'category2'        => 'securities',
            'network_category' => 'securities',
            'group'            => 'tpv_filter',
            'step'             => 'authorization',
        ]);

        $order = $this->createTpvOrderForBank('KKBK');

        $payment = $this->doNetbankingKotakAuthAndCapturePayment($order);

        $payment = $this->getLastEntity('payment', true);

        $payment = $this->getLastEntity('netbanking', true);

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentTpvNewNetbankingEntity1'], $payment);

        $this->assertArrayHasKey('bank_payment_id', $payment);
        $this->assertTrue(filter_var($payment['bank_payment_id'], FILTER_VALIDATE_INT) !== false);
    }

    protected function createTpvOrderForBank($bank)
    {
        $request = [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'netbanking',
                'account_number' => '0040304030403040',
                'bank'           => $bank,
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ];

        $this->ba->privateAuth();

        $content = $this->makeRequestAndGetContent($request);

        $this->ba->publicAuth();

        return $content;
    }

    public function testPaymentVerify()
    {
        $payment = $this->doNetbankingKotakAuthAndCapturePayment();

        $payment = $this->getLastEntity('payment', true);

        $content = $this->verifyPayment($payment['id']);

        assert($content['payment']['verified'] === 1);
    }

    public function testVerifyFailed()
    {
        $payment = $this->doNetbankingKotakAuthAndCapturePayment();

        $payment = $this->getLastEntity('payment', true);

        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'verify_action')
            {
                // Decrypted string is '0520|12032019150624|OSRAZOR|155238338329361|500|Y|123456|3210708649'
                $content = 'HrEkFDECV6ZHX8zlc8RZZAcU+cnRDaeEOYBT3oSCIvk0wPTef+qGC+uSMYaR2CocJmRNSFhCgbBcapaT6xbOGoPGB7PfQi7aedT7mxks0QBj0pOYVKcXrCcUGkNghMyj';
            }
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function () use ($payment)
        {
            $this->verifyPayment($payment['id']);
        });
    }

    public function testRefundsFileGeneration()
    {
        // this route is deprecated. This has been moved to 'gateway/files'
        $this->markTestSkipped();

        Mail::fake();

        // Make 6 payments
        foreach (range(0,2) as $value)
        {
            // 3 non tpv payments
            $this->testPayment();
        }

        $tpvEnabled = $this->fixtures->merchant->enableTPV();

        foreach (range(0,2) as $value)
        {
            // 3 tpv payment
            $this->testTpvPayment($tpvEnabled);
        }

        $payments = $this->getEntities('payment', [], true);

        $this->movePaymentsToYesterday($payments);

        $this->setPaymentsReconciledAtToday();

        list($tpvPayments, $nonTpvPayments) = $this->getTpvAndNonTpvPayments($payments);

        // Refund a tpv and a non tpv payment
        foreach ([$tpvPayments[0], $nonTpvPayments[0]] as $payment)
        {
            $refundPayment = $this->refundPayment($payment['id'], 100);

            $refundPayment = $this->refundPayment($payment['id']);
        }

        $this->moveRefundsToYesteday();

        $content = $this->generateRefundsExcelForNB('KKBK');

        $this->setUpMailMock();

        foreach (['tpv', 'nonTpv'] as $fileType)
        {
            $this->checkDailyFilesContent($content, $fileType);
        }

        $this->setUpMailMock();
    }

    public function testVerifyCallback()
    {
        $payment = $this->doNetbankingKotakAuthAndCapturePayment();

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('captured', $payment['status']);
    }

    public function testVerifyCallbackFailed()
    {
        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === Action::VERIFY)
            {
                $content[Fields::AUTHORIZATION_STATUS] = Status::FAIL;
            }
        });

        $payment = $this->getDefaultNetbankingPaymentArray('KKBK');

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function () use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('failed', $payment['status']);
    }

    public function testFailedPaymentVerifyCallback()
    {
        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === Action::CALLBACK)
            {
                $content[Fields::AUTHORIZATION_STATUS] = Status::FAIL;
            }
            else if ($action === Action::VERIFY)
            {
                $content[Fields::AUTHORIZATION_STATUS] = Status::FAIL;
            }
        });

        $payment = $this->getDefaultNetbankingPaymentArray('KKBK');

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('failed', $payment['status']);
    }

    public function testFailedPaymentCallbackWithError()
    {
        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === Action::CALLBACK)
            {
                $content[Fields::AUTHORIZATION_STATUS] = Status::ERROR;
            }
        });

        $payment = $this->getDefaultNetbankingPaymentArray('KKBK');

        $data = $this->testData['testFailedPaymentVerifyCallback'];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('failed', $payment['status']);
    }

    public function testFailedPaymentCallbackWithRandomError()
    {
        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === Action::CALLBACK)
            {
                $content[Fields::AUTHORIZATION_STATUS] = 'Random';
            }
        });

        $payment = $this->getDefaultNetbankingPaymentArray('KKBK');

        $data = $this->testData['testFailedPaymentVerifyCallback'];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('failed', $payment['status']);
    }

    protected function setUpMailMock()
    {
        $date = Carbon::today(Timezone::IST)->format('d-m-Y');

        $testData = [
            'subject' => 'Kotak Netbanking claims and refund files for '.$date,
            'amount' => [
                'claims' => 1500,
                'refunds' => 500,
                'total' => 1000,
            ],
        ];

        Mail::assertQueued(DailyFileMail::class, function ($mail) use ($testData)
        {
            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            return true;
        });
    }

    protected function checkDailyFilesContent($content, $fileType)
    {
        $refundsFileUrl = $content['netbanking_kotak']['refunds'][$fileType];

        $claimsFileUrl = $content['netbanking_kotak']['claims'][$fileType];

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

    protected function doNetbankingKotakAuthAndCapturePayment($order = [])
    {
        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['bank'] = 'KKBK';

        if (empty($order) === false)
        {
            $payment['order_id'] = $order['id'];
        }

        $payment = $this->doAuthAndCapturePayment($payment);

        return $payment;
    }

    protected function movePaymentsToYesterday($payments)
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(10)->addMinutes(30)->timestamp;

        // Set payment dates to yesterday
        foreach ($payments['items'] as $payment)
        {
            $this->fixtures->edit('payment', $payment['id'], ['created_at' => $createdAt,
                'authorized_at' => $createdAt + 10,
                'captured_at' => $createdAt + 20]);
        }
    }

    protected function setPaymentsReconciledAtToday()
    {
        // Set the transactions to be reconciled today
        $transactions = $this->getEntities('transaction', [], true);

        $reconciledAt = Carbon::today(Timezone::IST)->addHours(5)->addMinutes(13)->timestamp;

        foreach ($transactions['items'] as $transaction)
        {
            $this->fixtures->edit('transaction', $transaction['id'], ['reconciled_at' => $reconciledAt]);
        }
    }

    protected function getTpvAndNonTpvPayments($payments)
    {
        $tpvPayments = array_filter($payments['items'], function($payment)
        {
            return $payment['terminal_id'] === 'TpvNbKotakTmnl';
        });

        $nonTpvPayments = array_filter($payments['items'], function($payment)
        {
            return $payment['terminal_id'] === 'DrctNbKtkTrmnl';
        });

        $tpvPayments = array_values($tpvPayments);

        $nonTpvPayments = array_values($nonTpvPayments);

        return [$tpvPayments, $nonTpvPayments];
    }

    protected function moveRefundsToYesteday()
    {
        $refunds = $this->getEntities('refund', [], true);

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(10)->addMinutes(45)->timestamp;

        // Mark refunds as created yesterday
        foreach ($refunds['items'] as $refund)
        {
            $this->fixtures->edit('refund', $refund['id'], ['created_at' => $createdAt]);
        }
    }

    protected function mockRazorxTreatment(string $returnValue = 'kotak_new_integration')
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn($returnValue);
    }
}
