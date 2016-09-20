<?php

namespace RZP\Tests\Functional\Gateway\Upi\Icici;

use Closure;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class UPIGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/UPIGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_upi_terminal');

        $this->gateway = 'upi_icici';

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->payment = $this->getDefaultUpiPaymentArray();
    }

    public function testPayment($status = 'created')
    {
        $res = $this->doAuthPayment($this->payment);
        $paymentId = $res['payment_id'];

        // Co Proto must be working
        $this->assertEquals('async', $res['type']);

        $this->checkPaymentStatus($paymentId, $status);

        return $paymentId;
    }

    public function testPaymentWithXmlResponse()
    {
        $this->setContent(function (& $content)
        {
            $content = <<<EOT
'<?xml version="1.0" encoding="UTF-8"?>
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
</soapenv:Envelope>'
EOT;
        });

        $payment = $this->getDefaultUpiPaymentArray();
        $payment['vpa'] = 'dontencrypt@icici';
        $payment['notes']['status'] = 'created';

        $response = $this->doAuthPayment($payment);

        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('async', $response['type']);

        $status = 'created';
        $this->checkPaymentStatus($paymentId, $status);

        return $paymentId;
    }

    public function testPaymentWithRandomResponseCode()
    {
        $this->payment['vpa'] = 'unknownresponse@icici';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() {
            $this->testPayment('failed');
        });
    }

    public function testUnencryptedResponsePayment()
    {
        $this->payment['vpa'] = 'dontencrypt@icici';

        $this->testPayment();
    }

    public function testInvalidResponsePayment()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        $this->setContent(function (& $content)
        {
            $content = null;
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->doAuthPayment($payment);
        });
    }

    public function testPaymentWithS2S($assert = true)
    {
        $paymentId = $this->testPayment();

        $upiEntity = $this->getLastEntity('upi_icici', true);
        $payment = $this->getEntityById('payment', $paymentId, true);

        $mockServer = $this->mockServer();

        $content = $mockServer->makeS2SRequest($upiEntity, $payment);

        $request = [
            'raw'      => $content,
            'url'       => '/callback/upi_icici',
            'method'    => 'post'
        ];

        $response = $this->makeRequestAndGetContent($request);

        if ($assert)
        {
            $this->assertEquals($response, ['success' => true]);
        }

        return $payment;
    }

    protected function checkPaymentStatus($id, $expectedStatus)
    {
        $request = [
            'url'       => "/payments/$id/status",
            'method'    => 'get'
        ];

        $this->ba->publicAuth();

        $data = $this->makeRequestAndGetContent($request);

        $status = $data['status'];

        $this->assertEquals($expectedStatus, $status);
    }

    public function testPaymentRefund()
    {
        $payment = $this->testPaymentWithS2S();

        $this->capturePayment($payment['id'], 50000);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->refundPayment($payment['id']);
        });
    }

    public function testVerifyPayment()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        $authPayment = $this->doAuthPayment($payment);

        $upiEntity = $this->getLastEntity('upi', true);
        $payment = $this->getEntityById('payment', $authPayment['payment_id'], true);

        $mockServer = $this->mockServer();

        $content = $mockServer->makeS2SRequest($upiEntity, $payment);

        $request = [
            'raw'      => $content,
            'url'       => '/callback/upi_icici',
            'method'    => 'post'
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->payment = $this->verifyPayment($payment['id']);

        $this->assertSame($this->payment['payment']['verified'], 1);
    }

    public function testVerifyPaymentWithEncryptedResponse()
    {
        $payment = $this->getDefaultUpiPaymentArray();
        $payment['notes']['encrypt'] = 'true';

        $authPayment = $this->doAuthPayment($payment);

        $upiEntity = $this->getLastEntity('upi', true);
        $payment = $this->getEntityById('payment', $authPayment['payment_id'], true);

        $mockServer = $this->mockServer();

        $content = $mockServer->makeS2SRequest($upiEntity, $payment);

        $request = [
            'raw'      => $content,
            'url'       => '/callback/upi_icici',
            'method'    => 'post'
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->payment = $this->verifyPayment($payment['id']);

        $this->assertSame($this->payment['payment']['verified'], 1);
    }

    public function testVerifyFailedPayment()
    {
        $this->ba->publicAuth();

        $data = $this->testData[__FUNCTION__];

        $payment = $this->getDefaultUpiPaymentArray();
        $payment['notes']['status'] = 'success';

        $authPayment = $this->doAuthPayment($payment);

        $payment = $this->getEntityById('payment', $authPayment['payment_id'], true);

        $this->authorizeFailedPayment($payment['id']);

        $upi = $this->getLastEntity('upi', true);
        $this->assertTestResponse($upi, 'testPaymentUpiEntity');
        $this->assertArrayHasKey('gateway_payment_id', $upi);
    }

    protected function setContent(Closure $closure)
    {
        $server = $this->mockServer()
                        ->shouldReceive('content')
                        ->andReturnUsing($closure)
                        ->mock();

        $this->setMockServer($server);
    }
}
