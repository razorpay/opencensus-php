<?php

namespace RZP\Tests\Functional\Gateway\Upi\Mindgate;

use RZP\Models\Payment;
use RZP\Models\Merchant\Account;
use RZP\Models\Payment\Method;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\UpiMetadata;
use RZP\Gateway\Upi\Base\Entity as UpiEntity;
use RZP\Tests\Functional\Fixtures\Entity\Terminal;
use RZP\Tests\Functional\Helpers\MocksMetricTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class UpiMindgateOtmTest extends TestCase
{
    use PaymentTrait;
    use MocksMetricTrait;
    use DbEntityFetchTrait;

    /**
     * @var Terminal
     */
    protected $sharedTerminal;

    /**
     * Payment array
     * @var array
     */
    protected $payment;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/UpiMindgateOtmTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_upi_mindgate_terminal', [
            'type' => [
                'otm_collect' => '1',
                'non_recurring' => '1'
            ]
        ]);

        $this->gateway = 'mozart';

        $this->setMockGatewayTrue();

        $this->fixtures->merchant->enableMethod(Account::TEST_ACCOUNT, Method::UPI);

        $this->fixtures->merchant->activate();

        $this->payment = $this->getDefaultUpiOtmPayment();
    }

    // Scenario : Mandate create is successful in gateway
    public function testUpiOtmCreate()
    {
        $this->createOtmPayment();
    }

    // Scenario: Mandate create is unsuccessful in gateway.
    public function testUpiOtmCreateFailed()
    {
        $this->payment['description'] = 'mandateCreateFailed';

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function ()
        {
            $this->doAuthPaymentViaAjaxRoute($this->payment);
        });

        $payment = $this->getDbLastEntity('payment');
        $upiMetadata = $this->getDbLastEntity('upi_metadata');
        $upi = $this->getDbLastEntity('upi');
        $mozartEntity = $this->getDbLastEntity('mozart');

        $this->assertSame('failed', $payment->getStatus());
        $this->assertNotNull($upiMetadata);

        $this->assertArraySubset([
            UpiEntity::ACTION     => 'authorize',
            UpiEntity::GATEWAY    => 'upi_mindgate',
            UpiEntity::PAYMENT_ID => $payment->getId(),
            UpiEntity::ACQUIRER   => 'hdfc',
            UpiEntity::AMOUNT     => $payment->getAmount(),
            UpiEntity::VPA        => $payment->getVpa(),
            UpiEntity::PROVIDER   => 'icici',
            UpiEntity::TYPE       => 'collect',
        ], $upi->toArray());

        $this->assertSame('authorize', $mozartEntity->getAction());
    }

    // Scenario: Mandate create with successful callback
    public function testUpiOtmSuccessCallback()
    {
        $payment = $this->createOtmPayment();

        $content = $this->mockServer()->getAsyncCallbackResponseMandateCreate($payment);

        $this->makeS2sCallbackAndGetContent($content, 'upi_mindgate');

        $payment->refresh();

        $this->assertSame('authorized', $payment->getStatus());

        $mozart = $this->getDbLastMozart();
        $this->assertSame('authorize', $mozart->getAction());
    }

    // Scenario: Mandate create with failure callback
    public function testUpiOtmFailedCallback()
    {
        $payment = $this->createOtmPayment('failedCallback');

        $content = $this->mockServer()->getAsyncCallbackResponseMandateCreate($payment);

        $response = $this->makeS2sCallbackAndGetContent($content, 'upi_mindgate');

        $payment->refresh();

        $this->assertSame('failed', $payment->getStatus());

        $mozart = $this->getDbLastMozart();
        $this->assertSame('authorize', $mozart->getAction());
    }

    public function testUpiOtmFailedCallbackValidations()
    {
        $payment = $this->createOtmPayment('failedValidations');

        $content = $this->mockServer()->getAsyncCallbackResponseMandateCreate($payment);

        $response = $this->makeS2sCallbackAndGetContent($content, 'upi_mindgate');

        $payment->refresh();

        $this->assertSame('failed', $payment->getStatus());

        $mozart = $this->getDbLastMozart();
        $this->assertSame('authorize', $mozart->getAction());
    }

    // Helpers: Create successful Payment, Return payment
    /**
     * @param string $description
     * @return Payment\Entity
     */
    protected function createOtmPayment($description = "")
    {
        $this->payment['description'] = $description;

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $paymentId = $response['payment_id'];

        $this->assertEquals('async', $response['type']);

        $payment = $this->getDbLastEntity('payment');
        $upiMetadata = $this->getDbLastEntity('upi_metadata');
        $upi = $this->getDbLastEntity('upi');
        $mozartEntity = $this->getDbLastEntity('mozart');


        $this->assertSame('created', $payment->getStatus());

        $this->assertArraySubset([
            UpiMetadata\Entity::TYPE       => 'otm',
            UpiMetadata\Entity::FLOW       => 'collect',
            UpiMetadata\Entity::PAYMENT_ID => $payment->getId(),
            UpiMetadata\Entity::END_TIME   => $this->payment['upi']['end_time'],
        ], $upiMetadata->toArray());

        $this->assertArraySubset([
            UpiEntity::ACTION     => 'authorize',
            UpiEntity::GATEWAY    => 'upi_mindgate',
            UpiEntity::PAYMENT_ID => $payment->getId(),
            UpiEntity::ACQUIRER   => 'hdfc',
            UpiEntity::AMOUNT     => $payment->getAmount(),
            UpiEntity::VPA        => $payment->getVpa(),
            UpiEntity::PROVIDER   => 'icici',
            UpiEntity::TYPE       => 'collect',
        ], $upi->toArray());

        $this->assertNotNull($upi->getNpciReferenceId());
        $this->assertNotNull($upi->getGatewayPaymentId());

        // Mozart entity should have been created
        $this->assertSame('authorize', $mozartEntity->getAction());
        return $payment;
    }
}
