<?php

namespace RZP\Tests\Functional\Gateway\Enach\Netbanking;

use Mockery;
use RZP\Models\Feature;
use RZP\Constants\Entity;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class EnachNetbankingNpciGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/EnachNetbankingNpciGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_enach_npci_netbanking_terminal');
        $this->fixtures->create(Entity::CUSTOMER);

        $this->fixtures->merchant->enableEmandate();
        $this->fixtures->merchant->addFeatures([Feature\Constants::CHARGE_AT_WILL]);

        $this->gateway = 'enach_npci_netbanking';

        //$this->setupMockDns();
    }

    public function testPayment()
    {
        $payment                 = $this->getEmandatePaymentArray('YESB', 'netbanking', 0);
        $payment['bank_account'] = [
            'account_number' => '914010009305862',
            'ifsc'           => 'yesb0000123',
            'name'           => 'Test account',
        ];

        $order               = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(0, $payment['amount']);

        $this->assertEquals('captured', $payment['status']);

        $this->assertEquals('initial', $payment['recurring_type']);

        $enach = $this->getLastEntity('enach', true);

        $this->assertNotNull($enach['gateway_reference_id']);

        $this->assertEquals('true', $enach['status']);

        //$this->assertNotNull($enach['registration_date']);

        $token = $this->getLastEntity('token', true);

        $this->assertEquals('netbanking', $token['auth_type']);

        $this->assertEquals('initiated', $token['recurring_status']);
    }

    public function testPaymentRejectResponse()
    {
        $payment                 = $this->getEmandatePaymentArray('YESB', 'netbanking', 0);
        $payment['bank_account'] = [
            'account_number' => '914010009305862',
            'ifsc'           => 'yesb0000123',
            'name'           => 'Test account',
        ];

        $order               = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->mockRejectCallbackResponse();

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment) {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(0, $payment['amount']);

        $this->assertEquals('failed', $payment['status']);

        $this->assertEquals('initial', $payment['recurring_type']);

        $enach = $this->getLastEntity('enach', true);

        $this->assertEquals('false', $enach['status']);

        $token = $this->getLastEntity('token', true);

        $this->assertEquals('netbanking', $token['auth_type']);

        $this->assertEquals(null , $token['recurring_status']);
    }

    public function testPaymentErrorResponse()
    {
        $payment                 = $this->getEmandatePaymentArray('YESB', 'netbanking', 0);
        $payment['bank_account'] = [
            'account_number' => '914010009305862',
            'ifsc'           => 'yesb0000123',
            'name'           => 'Test account',
        ];

        $order               = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->mockFailedCallbackResponse();

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment) {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('failed', $payment['status']);

        $this->assertEquals(0, $payment['amount']);

        $this->assertEquals('initial', $payment['recurring_type']);

        $enach = $this->getLastEntity('enach', true);

        $this->assertEquals('false', $enach['status']);

        $token = $this->getLastEntity('token', true);

        $this->assertEquals('netbanking', $token['auth_type']);

        $this->assertEquals(null, $token['recurring_status']);
    }

    protected function mockRejectCallbackResponse()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'authorize_get_secure_data')
            {
                $content['Accptd'] = 'false';
                $content['ReasonCode'] = '1022';
                $content['ReasonDesc'] = 'Invalid Authentication';
                $content['RejectBy'] = 'Bank';
            }
        });
    }

    protected function mockFailedCallbackResponse()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content = 'ErrorXML';
            }
        });
    }

    protected function runPaymentCallbackFlowNetbanking($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        if ($mock)
        {
            $request = $this->makeFirstGatewayPaymentMockRequest(
                $url, $method, $content);
        }

        $response = $this->sendRequest($request);

        $this->assertEquals($response->getStatusCode(), '302');

        $data = array(
            'url' => $response->headers->get('location'),
            'method' => 'post');

        if (filter_var($data['url'], FILTER_VALIDATE_URL))
        {
            return $this->submitPaymentCallbackRedirect($data['url']);
        }

        return $this->submitPaymentCallbackRequest($request);
    }
}
