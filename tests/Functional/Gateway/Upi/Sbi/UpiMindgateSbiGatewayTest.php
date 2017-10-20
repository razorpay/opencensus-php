<?php

namespace RZP\Tests\Functional\Gateway\Upi\Sbi;

use RZP\Models\Payment;
use RZP\Constants\Entity;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Status;
use RZP\Models\Payment\Gateway;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Upi\Base\Entity as Upi;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class UpiMindgateSbiGatewayTest extends TestCase
{
    use PaymentTrait;

    /**
     * @var Payment variable
     */
    protected $payment;

    /**
     * @var Upi Mindgate Sbi terminal
     */
    protected $sharedTerminal;

    const PAYMENT_ID = 'payment_id';

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/UpiMindgateSbiGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_upi_mindgate_sbi_terminal');

        $this->gateway = Gateway::UPI_SBI;

        $this->fixtures->merchant->enableMethod(Account::TEST_ACCOUNT, Method::UPI);

        $this->payment = $this->getDefaultUpiPaymentArray();
    }

    /**
     * Tests the flow where the customer is sent the collect request from the razorpay sbi vpa.
     * User then accepts the collect request, and a asynchronous callback is sent to API.
     * We assert the entity is being updated correctly in this flow
     */
    public function testPayment()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $paymentId = $response[self::PAYMENT_ID];

        // Coproto must be working
        $this->assertEquals('async', $response['type']);

        $this->checkPaymentStatus($paymentId, Status::CREATED);

        $upiEntity = $this->getLastEntity(Entity::UPI, true);

        $payment = $this->getEntityById(Entity::PAYMENT, $paymentId, true);

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content);

        // We should have gotten a successful response
        $this->assertEquals(['success' => true], $response);

        // The payment should now be authorized
        $payment = $this->getEntityById(Entity::PAYMENT, $paymentId, true);
        $this->assertEquals(Payment\Status::AUTHORIZED, $payment[Payment\Entity::STATUS]);

        $upiEntity = $this->getLastEntity(Entity::UPI, true);
        $this->assertNotNull($upiEntity[Upi::NPCI_REFERENCE_ID]);
        $this->assertNotNull($upiEntity[Upi::CUSTOMER_REFERENCE_ID]);
        $this->assertNotNull($upiEntity[Upi::GATEWAY_PAYMENT_ID]);

        // Add a capture as well, just for completeness sake
        $this->capturePayment($paymentId, $payment[Payment\Entity::AMOUNT]);
    }

    /**
     * This verifies the transaction status after a payment has been successfully made.
     */
    public function testPaymentVerify()
    {
        $this->testPayment();

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $verify = $this->verifyPayment($payment['id']);

        // TODO: Add more assertions

        $this->assertEquals(true, $verify['gateway']['apiSuccess']);
        $this->assertEquals(true, $verify['gateway']['gatewaySuccess']);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->assertEquals($verify[Entity::PAYMENT][Payment\Entity::ID], $payment[Payment\Entity::ID]);
        $this->assertEquals(1, $payment[Payment\Entity::VERIFIED]);
    }

    // TODO: Test case for when we verify a payment without getting async callback response

    public function testPaymentRefund()
    {
        $this->testPayment();

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        // Attempt a partial refund
        $this->refundPayment($payment[Payment\Entity::ID], 10000);
    }

    protected function checkPaymentStatus(string $id, string $status)
    {
        $response = $this->getPaymentStatus($id);

        $this->assertEquals($status, $response[Payment\Entity::STATUS]);
    }
}