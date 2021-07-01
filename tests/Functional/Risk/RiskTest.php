<?php

namespace RZP\Tests\Functional\Risk;


use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Models\Risk;

class RiskTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/RiskTestData.php';

        parent::setUp();

        // Note: Routes have been removed and test cases have been skipped
        // $this->ba->appAuth();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');
    }

    private function makeFraudulentPayment()
    {
        //
        // Running testFraudDetected test again till we can do
        // runDependencyTest across test files
        //
        $this->mockMaxmind();

        $this->fixtures->merchant->enableInternational();

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4012010000000007';

        $data = $this->testData['testFraudDetected'];

        $result = $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity('payment', true);

        return $payment;
    }

    public function testFetchMultiple()
    {
        $this->markTestSkipped('Maxmind code removed');
        $payment = $this->makeFraudulentPayment();
        $this->testData[__FUNCTION__]['request']['content']['payment_id'] = $payment['id'];
        $this->startTest();
    }

    public function testGetGrievanceEntityDetailsForPayment()
    {
        $this->fixtures->connection('live');

        $payment = $this->fixtures->create('payment');

        $this->ba->directAuth();

        $pid = $payment->getPublicId();

        $request = array(
            'url'     => '/customer_flagging/entity_details/'.$pid,
            'method'  => 'GET',
            'content' => []
        );

        $response = $this->makeRequestAndGetContent($request, $callback);

        $this->assertArrayHasKey('entity', $response);

        $this->assertArrayHasKey('entity_id', $response);

        $this->assertArrayHasKey('merchant_id', $response);

        $this->assertArrayHasKey('merchant_label', $response);

        $this->assertArrayHasKey('merchant_logo', $response);

        $this->assertArrayHasKey('subject', $response);

    }

    public function testPostCustomerFlaggingToRiskService()
    {
        $this->fixtures->connection('live');

        $payment = $this->fixtures->create('payment');

        $this->ba->directAuth();

        $pid = $payment->getPublicId();

        $request = array(
            'url'     => '/customer_flagging/entity_details/'.$pid,
            'method'  => 'GET',
            'content' => []
        );

        $entityDetails = $this->makeRequestAndGetContent($request, $callback);

        $input = [
            'email_id' => 'test@gmail.com',
            'comments' => 'test report',
            'entity_id'=> $pid,
            'source' => 'txn_confirm_mail'
        ];

        $response = (new Risk\Core())->postCustomerFlaggingToRiskService( $input ,$entityDetails);

        $this->assertArrayHasKey('status', $response);

    }

}
