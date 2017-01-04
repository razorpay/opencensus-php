<?php

namespace RZP\Tests\Functional\Gateway\Upi\Icici;

use Cache;
use Closure;
use Carbon\Carbon;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class ICICIGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/ICICIGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_upi_terminal');

        $this->gateway = 'upi_icici';

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->payment = $this->getDefaultUpiPaymentArray();
    }

    public function testPayment($status = 'created')
    {
        unset($this->payment['description']);

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);
        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('async', $response['type']);

        $this->checkPaymentStatus($paymentId, $status);

        return $paymentId;
    }

    public function testPaymentViaRedirection()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        $response = $this->doAuthPayment($payment);

        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('async', $response['type']);

        // Payment status is a polling API which checkout hits
        // continously. Replicating the same in test case
        $this->checkPaymentStatus($paymentId, 'created');
        $this->checkPaymentStatus($paymentId, 'created');

        return $paymentId;
    }

    public function testPaymentWithXmlResponse()
    {
        $this->mockServerContentFunction(function (& $content)
        {
            $content = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Body>
        <soapenv:Fault>
            <faultcode>soapenv:Server</faultcode>
            <faultstring>Policy Falsified</faultstring>
            <faultactor>https://apigwuat.icicibank.com:8443/newCollectPay</faultactor>
            <detail>
                <l7:policyResult status="Assertion Falsified" xmlns:l7="http://www.layer7tech.com/ws/policy/fault"/>
            </detail>
        </soapenv:Fault>
    </soapenv:Body>
</soapenv:Envelope>
EOT;
        });

        $payment = $this->getDefaultUpiPaymentArray();
        $payment['vpa'] = 'dontencrypt@icici';
        $payment['notes']['status'] = 'created';

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('async', $response['type']);

        $status = 'created';
        $this->checkPaymentStatus($paymentId, $status);

        return $paymentId;
    }

    public function testPaymentS2S()
    {
        $this->fixtures->merchant->addFeatures(['s2supi']);

        $payment = $this->getDefaultUpiPaymentArray();

        $response = $this->doS2SUpiPayment($payment);

        $paymentId = $response['razorpay_payment_id'];

        $this->checkPaymentStatus($paymentId, 'created');

        return $paymentId;
    }

    public function testPaymentWithRandomResponseCode()
    {
        $this->payment['vpa'] = 'unknownresponse@icici';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function()
        {
            $this->testPayment('failed');
        });
    }

    public function testLongVPA()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        $payment['vpa'] = 'thisisaverylongvpathisisaverylongvpathisisaverylongvpathisisaverylongvpathisisaverylongvpathisisaverylongvpa@icici';

        $data = $this->testData['testLongVPA'];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPaymentViaAjaxRoute($payment);
        });
    }

    public function testUpiVPA()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        $payment['vpa'] = 'nemo@upi';

        Cache::forever('excluded_psps', '["upi"]');

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testInvalidVPA()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        // Emails are not VPAs
        $payment['vpa'] = 'nemo@razorpay@com';

        $data = $this->testData['testInvalidVPA'];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPaymentViaAjaxRoute($payment);
        });
    }

    public function testInvalidVPAError()
    {
        $vpas = [
            'user@invalidbank',
            'invalidvpa@icici'
        ];

        foreach ($vpas as $vpa)
        {
            $payment = $this->getDefaultUpiPaymentArray();

            $payment['vpa'] = $vpa;

            $data = $this->testData['testInvalidVPAError'];

            $this->runRequestResponseFlow($data, function() use ($payment)
            {
                $this->doAuthPaymentViaAjaxRoute($payment);
            });

        }
    }

    public function testSingleWordVPA()
    {
        $payment = $this->getDefaultUpiPaymentArray();
        $payment['vpa'] = 's@dcb';

        $this->doAuthPaymentViaAjaxRoute($payment);
    }

    public function testInvalidResponsePayment()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        $this->mockServerContentFunction(function (& $content)
        {
            $content = null;
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->doAuthPaymentViaAjaxRoute($payment);
        });
    }

    public function testPaymentWithS2S($assert = true)
    {
        $paymentId = $this->testPayment();

        $upiEntity = $this->getLastEntity('upi_icici', true);
        $payment = $this->getEntityById('payment', $paymentId, true);

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content);

        if ($assert)
        {
            $this->assertEquals($response, ['success' => true]);
        }

        $payment = $this->getEntityById('payment', $paymentId, true);

        return $payment;
    }

    public function testRejectedPayment($assert = true)
    {
        $paymentId = $this->testPayment();

        $upiEntity = $this->getLastEntity('upi_icici', true);
        $payment = $this->getEntityById('payment', $paymentId, true);

        $data = $this->testData[__FUNCTION__];

        $server = $this->mockServerContentFunction(function (&$content)
        {
            $content['TxnStatus'] = 'REJECT';
        });

        $content = $server->getAsyncCallbackContent($upiEntity, $payment);

        $this->runRequestResponseFlow($data, function () use ($content)
        {
            $this->makeS2SCallbackAndGetContent($content);
        });

        $data = $this->testData['testStatusRejectPayment'];

        $this->runRequestResponseFlow($data, function () use ($payment)
        {
            $this->getPaymentStatus($payment['id']);
        });
    }

    protected function checkPaymentStatus($id, $expectedStatus)
    {
        $response = $this->getPaymentStatus($id);

        $status = $response['status'];

        $this->assertEquals($expectedStatus, $status);
    }

    public function testPaymentRefund()
    {
        $payment = $this->testPaymentWithS2S();

        $this->capturePayment($payment['id'], 50000);

        $this->refundPayment($payment['id']);
    }

    public function testVerifyPayment()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        $authPayment = $this->doAuthPaymentViaAjaxRoute($payment);

        $upiEntity = $this->getLastEntity('upi', true);
        $payment = $this->getEntityById('payment', $authPayment['payment_id'], true);

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);
        $response = $this->makeS2SCallbackAndGetContent($content);

        $this->payment = $this->verifyPayment($payment['id']);

        $this->assertSame($this->payment['payment']['verified'], 1);
    }

    /**
     * Make sure a 5006 is taken as a gateway failure
     */
    public function testVerifyMissingPayment()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        // TODO: Stop using notes for status
        // Instead use something like `status_code_success_etc@icici`
        // To encode all expected information in the VPA itself
        //
        // Will work on this in #1997
        $payment['notes']['status'] = 'failed';
        $payment['vpa'] = 'missingpayment@icici';

        $authPayment = $this->doAuthPaymentViaAjaxRoute($payment);

        $upiEntity = $this->getLastEntity('upi', true);
        $payment = $this->getEntityById('payment', $authPayment['payment_id'], true);

        $this->payment = $this->verifyPayment($payment['id']);

        $this->assertSame($this->payment['payment']['verified'], 1);
    }

    public function testVerifyPaymentWithEncryptedResponse()
    {
        $payment = $this->getDefaultUpiPaymentArray();
        $payment['notes']['encrypt'] = 'true';

        $authPayment = $this->doAuthPaymentViaAjaxRoute($payment);

        $upiEntity = $this->getLastEntity('upi', true);
        $payment = $this->getEntityById('payment', $authPayment['payment_id'], true);

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);
        $response = $this->makeS2SCallbackAndGetContent($content);

        $this->payment = $this->verifyPayment($payment['id']);

        $this->assertSame($this->payment['payment']['verified'], 1);
    }

    public function testVerifyFailedPayment()
    {
        $this->ba->publicAuth();

        $data = $this->testData[__FUNCTION__];

        $payment = $this->getDefaultUpiPaymentArray();
        $payment['notes']['status'] = 'success';

        $authPayment = $this->doAuthPaymentViaAjaxRoute($payment);

        $payment = $this->getEntityById('payment', $authPayment['payment_id'], true);

        $this->authorizeFailedPayment($payment['id']);

        $upi = $this->getLastEntity('upi', true);
        $this->assertTestResponse($upi, 'testPaymentUpiEntity');
        $this->assertArrayHasKey('gateway_payment_id', $upi);
    }

    public function testRefundExcelFile()
    {
        $payment = $this->testPaymentWithS2S();
        $this->capturePayment($payment['id'], 50000);

        $refund = $this->refundPayment($payment['id']);

        $payment = $this->testPaymentWithS2S();
        $this->capturePayment($payment['id'], 50000);
        $refund = $this->refundPayment($payment['id'], 10000);
        $refund = $this->refundPayment($payment['id']);

        $refunds = $this->getEntities('refund', [], true);

        // Convert the created_at dates to yesterday's so that they are picked
        // up during refund excel generation
        foreach ($refunds['items'] as $refund)
        {
            $createdAt = Carbon::yesterday('Asia/Kolkata')->timestamp + 5;
            $this->fixtures->edit('refund', $refund['id'], ['created_at' => $createdAt]);
        }

        $payment = $this->testPaymentWithS2S();
        $this->capturePayment($payment['id'], 50000);
        $this->refundPayment($payment['id']);

        $data = $this->generateRefundsExcelForIciciUpi();

        $this->assertEquals(3, $data['upi_icici']['count']);
        $this->assertTrue(file_exists($data['upi_icici']['file']));

        unlink($data['upi_icici']['file']);
    }

    protected function generateRefundsExcelForIciciUpi($date = false)
    {
        $this->ba->appAuth();

        $request = array(
            'url' => '/refunds/excel',
            'method' => 'post',
            'content' => [
                'method'    => 'upi',
                'bank'      => 'icici',
                'frequency' => 'daily'
            ],
        );

        if ($date)
        {
            $request['content']['on'] = Carbon::now()->format('Y-m-d');
        }

        return $this->makeRequestAndGetContent($request);
    }

    /**
     * TODO: Move this test to Payment Test
     * But we can only do that once we have sharp support for async
     */
    public function testAsyncPaymentAutoCaptured()
    {
        $this->ba->privateAuth();

        $res = $this->startTest($this->testData['testCreateAutoCaptureOrder']);

        $this->payment['order_id'] = $res['id'];

        $payment = $this->testPaymentWithS2S(true);

        $this->assertEquals('captured', $payment['status']);

        $response = $this->getPaymentStatus($payment['id']);

        $this->assertEquals([
            'razorpay_payment_id',
            'razorpay_order_id',
            'razorpay_signature'],

        array_keys($response));
    }
}
