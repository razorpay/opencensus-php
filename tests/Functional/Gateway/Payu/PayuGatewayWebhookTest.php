<?php

namespace RZP\Tests\Functional\Gateway\Payu;

use DB;
use Mockery;

use RZP\Constants\Mode;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Traits\MocksSplitz;


class PayuGatewayWebhookTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    use MocksSplitz;


    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/PayuGatewayWebhookTesttData.php';

        parent::setUp();
    }

    public function testPayuCardsStaticCallback()
    {
        $this->fixtures->create('payment:authorized',
            [
                'method'     => 'card',
                'gateway'    => 'payu',
            ]);

        $payment = $this->getLastEntity('payment', true);

        $request =  $this->testData['testPayuCardsStaticCallback'];

        $request['content']['txnid'] = substr($payment['id'], strlen('pay_'));

        $this->ba->directAuth();

        $this->mockAllSplitzTreatment();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(true, $response['success']);
    }

}
