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

    protected function checkPaymentStatus(string $id, string $status)
    {
        $response = $this->getPaymentStatus($id);

        $this->assertEquals($status, $response[Payment\Entity::STATUS]);
    }
}