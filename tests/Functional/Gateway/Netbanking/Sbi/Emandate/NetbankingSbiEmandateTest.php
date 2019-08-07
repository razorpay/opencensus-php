<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Sbi\EMandate;

use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Constants\Entity;
use RZP\Gateway\Netbanking\Sbi;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\GatewayTimeoutException;
use RZP\Gateway\Base\Action as GatewayAction;
use RZP\Models\Customer\Token\RecurringStatus;
use RZP\Models\Customer\Token\Entity as TokenEntity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;

class NetbankingSbiEmandateTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected $payment;

    const ACCOUNT_NUMBER    = '12345678901234';
    const IFSC              = 'SBIN0000001';
    const NAME              = 'Test account';

    public function setUp()
    {
        $this->gateway = Payment\Gateway::NETBANKING_SBI;

        $this->testDataFilePath = __DIR__.'/NetbankingSbiEMandateTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:shared_emandate_sbi_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->create('customer');

        $this->fixtures->merchant->addFeatures([Feature\Constants::CHARGE_AT_WILL]);

        $this->fixtures->merchant->enableEmandate();

        $this->payment = $this->getEmandateNetbankingRecurringPaymentArray('SBIN');

        $this->payment['bank_account'] = [
            'account_number'    => self::ACCOUNT_NUMBER,
            'ifsc'              => self::IFSC,
            'name'              => self::NAME,
        ];

        unset($this->payment[Entity::CARD]);

        $this->setMockGatewayTrue();
    }

    public function testEmandateInitialPayment()
    {
        $payment = $this->createRegistrationPayment();

        $this->assertArraySelectiveEquals(
            [
                Payment\Entity::AMOUNT => 0,
                Payment\Entity::STATUS => Payment\Status::AUTHORIZED,
            ],
            $payment
        );

        $token = $this->getLastEntity(Entity::TOKEN, true);

        $this->assertArraySelectiveEquals(
            [
                TokenEntity::RECURRING_STATUS => RecurringStatus::INITIATED,
                TokenEntity::METHOD           => 'emandate',
                TokenEntity::BANK             => 'SBIN',
            ],
            $token
        );

        $netbanking = $this->getLastEntity(Entity::NETBANKING, true);

        $this->assertNotNull($netbanking[NetbankingEntity::BANK_PAYMENT_ID]);

        $this->assertTrue($netbanking[NetbankingEntity::RECEIVED]);

        $this->assertEquals(Sbi\Status::SUCCESS, $netbanking[NetbankingEntity::STATUS]);
    }

    public function testEmandateInitialPaymentLateAuth()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === GatewayAction::AUTHORIZE)
            {
                throw new GatewayTimeoutException('Gateway timed out');
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->authorizedFailedPayment($payment['id']);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $this->assertArraySelectiveEquals(
            [
                Payment\Entity::AMOUNT => 0,
                Payment\Entity::STATUS => Payment\Status::AUTHORIZED,
            ],
            $payment
        );

        $token = $this->getLastEntity(Entity::TOKEN, true);

        $this->assertArraySelectiveEquals(
            [
                TokenEntity::RECURRING_STATUS => RecurringStatus::INITIATED,
                TokenEntity::METHOD           => 'emandate',
                TokenEntity::BANK             => 'SBIN',
            ],
            $token
        );

        $netbanking = $this->getLastEntity(Entity::NETBANKING, true);

        $this->assertNotNull($netbanking[NetbankingEntity::BANK_PAYMENT_ID]);

        $this->assertTrue($netbanking[NetbankingEntity::RECEIVED]);

        $this->assertEquals(Sbi\Status::SUCCESS, $netbanking[NetbankingEntity::STATUS]);

    }

    public function testEmandateInitialPaymentFailure()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 0]);
        $payment['order_id'] = $order->getPublicId();

        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === GatewayAction::AUTHORIZE)
            {
                $content[Sbi\ResponseFields::MANDATE_SBI_STATUS]      = Sbi\Status::FAILURE;
                $content[Sbi\ResponseFields::MANDATE_SBI_REF]         = '';
                $content[Sbi\ResponseFields::MANDATE_SBI_DESCRIPTION] = 'failed';
            }
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals(Payment\Status::FAILED, $payment[Payment\Entity::STATUS]);

        $token = $this->getDbLastEntityToArray(Entity::TOKEN);

        $this->assertNull($token[TokenEntity::RECURRING_STATUS]);
        $this->assertNull($token[TokenEntity::GATEWAY_TOKEN]);

        $netbanking = $this->getDbLastEntityToArray(Entity::NETBANKING);
         $this->assertEquals(Sbi\Status::FAILURE, $netbanking[NetbankingEntity::STATUS]);
    }

    public function testPaymentIdMismatch()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 0]);
        $payment['order_id'] = $order->getPublicId();

        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === GatewayAction::AUTHORIZE)
            {
                $content[Sbi\ResponseFields::MANDATE_PAYMENT_ID] = 'ABCD1234567890';
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $paymentEntity = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals(Payment\Status::FAILED, $paymentEntity[Payment\Entity::STATUS]);
    }

    public function testPaymentVerify()
    {
        $payment = $this->createRegistrationPayment();

        $verify = $this->verifyPayment($payment['public_id']);

        assert($verify['payment']['verified'] === 1);

        $gatewayPayment = $this->getDbLastEntityToArray('netbanking', 'test');

        $this->assertTestResponse($gatewayPayment, 'testPaymentVerifySuccessEntity');
    }

    protected function createRegistrationPayment()
    {
        $payment = $this->payment;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => 0]);
        $payment['order_id'] = $order->getPublicId();

        $response = $this->doAuthPayment($payment);

        return $this->getDbEntityById('payment', $response['razorpay_payment_id'])->toArray();
    }
}
