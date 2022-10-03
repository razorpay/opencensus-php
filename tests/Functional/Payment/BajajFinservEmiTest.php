<?php

namespace RZP\Tests\Functional\Payment;

use Str;
use File;
use Mail;
use Queue;
use Redis;
use Carbon\Carbon;
use RZP\Jobs\BeamJob;
use RZP\Constants\Timezone;
use RZP\Mail\Emi as EmiMail;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class BajajFinservEmiTest extends TestCase
{
    use PaymentTrait;

    protected $emiPlan;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/BajajFinservEmiPaymentTestData.php';

        parent::setUp();

        $this->gateway = 'bajajfinserv';

        $this->setMockGatewayTrue();

        $this->ba->publicAuth();

        $this->payment = $this->getDefaultPaymentArray();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->emiPlan = $this->fixtures->create('emi_plan:default_emi_plans');

        $this->mockCardVault();
    }

    public function testBajajFinservEmiFailedRefundTest()
    {
        $emiPlan = $this->emiPlan;

        $this->fixtures->emiPlan->createMerchantSpecificEmiPlans();

        $card = $this->fixtures->create('card', ['name' => 'Test Name']);

        $this->fixtures->create('terminal',
            [
                'id' => 'AqdfGh5460opVt',
                'merchant_id' => '10000000000000',
                'gateway' => 'bajajfinserv',
                'gateway_merchant_id' => '250000002',
                'enabled' => 1,
                'emi' => 1,
                'emi_duration' => 9
            ]);

        $payment = $this->fixtures->create('payment',
            [
                'method'        => 'emi',
                'gateway'       => 'bajajfinserv',
                'otp_attempts'  => 0,
                'terminal_id'   => 'AqdfGh5460opVt',
                'emi_plan_id'   => '30111111111110',
                'card_id'       => $card->getId(),
                'status'        => 'created',
                'amount'        => 10000,
            ]);

        $this->fixtures->create('mozart',
            [
                'payment_id'        => $payment->getId(),
                'amount'            => 100,
                'action'            => 'authorize',
                'raw'               => '{}',
            ]);

        $this->fixtures->create('payment_analytics', ['ip' => '127.0.0.1', 'payment_id' => $payment->getId()]);

        $data = $this->testData[__FUNCTION__];

        $url = $this->getOtpSubmitUrl($payment);

        $data['request']['url'] = $url;

        $this->runRequestResponseFlow($data);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'authorized');

        $this->capturePayment($payment['id'], $payment['amount']);

        $refundAmount1 = '5000';

        $refund = $this->refundPayment($payment['id'], $refundAmount1);

        $refundAmount1 = '6000';

        try
        {
            $refund = $this->refundPayment($payment['id'], $refundAmount1);
        }
        catch (\Exception $e)
        {
            $this->assertEquals("BAD_REQUEST_TOTAL_REFUND_AMOUNT_IS_GREATER_THAN_THE_PAYMENT_AMOUNT", $e->getCode());
        }

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals(5000, $paymentEntity['amount_refunded']);

        $this->assertEquals('AqdfGh5460opVt', $paymentEntity['terminal_id']);

        $this->assertEquals($paymentEntity['emi_plan_id'], '30111111111110');

        $this->assertEquals(10000, $paymentEntity['base_amount']);
    }

    public function testBajajFinservEmiPartialRefundTest()
    {
        $emiPlan = $this->emiPlan;

        $this->fixtures->emiPlan->createMerchantSpecificEmiPlans();

        $card = $this->fixtures->create('card', ['name' => 'Test Name']);

        $this->fixtures->create('terminal',
            [
                'id' => 'AqdfGh5460opVt',
                'merchant_id' => '10000000000000',
                'gateway' => 'bajajfinserv',
                'gateway_merchant_id' => '250000002',
                'enabled' => 1,
                'emi' => 1,
                'emi_duration' => 9
            ]);

        $payment = $this->fixtures->create('payment',
            [
                'method'        => 'emi',
                'gateway'       => 'bajajfinserv',
                'otp_attempts'  => 0,
                'terminal_id'   => 'AqdfGh5460opVt',
                'emi_plan_id'   => '30111111111110',
                'card_id'       => $card->getId(),
                'status'        => 'created',
                'amount'        => 10000,
            ]);

        $this->fixtures->create('mozart',
            [
                'payment_id'        => $payment->getId(),
                'amount'            => 100,
                'action'            => 'authorize',
                'raw'               => '{}',
            ]);

        $this->fixtures->create('payment_analytics', ['ip' => '127.0.0.1', 'payment_id' => $payment->getId()]);

        $data = $this->testData[__FUNCTION__];

        $url = $this->getOtpSubmitUrl($payment);

        $data['request']['url'] = $url;

        $this->runRequestResponseFlow($data);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'authorized');

        $this->capturePayment($payment['id'], $payment['amount']);

        $refundAmount = '5000';

        $refund = $this->refundPayment($payment['id'], $refundAmount);

        $paymentEntity = $this->getLastEntity('payment', true);

        $txn = $this->getLastTransaction(true);

        $this->assertEquals(4940, $txn['debit']);

        $this->assertEquals(0, $txn['fee']);

        $this->assertEquals(0, $txn['tax']);

        $this->assertEquals(0, $txn['mdr']);

        $this->assertEquals(118, $paymentEntity['fee']);

        $this->assertEquals('captured', $paymentEntity['status']);

        $this->assertEquals('emi', $paymentEntity['method']);

        $this->assertEquals('partial', $paymentEntity['refund_status']);

        $this->assertEquals('AqdfGh5460opVt', $paymentEntity['terminal_id']);

        $this->assertEquals('processed', $refund['status']);

        $this->assertEquals($paymentEntity['emi_plan_id'], '30111111111110');

        $this->assertEquals(10000, $paymentEntity['base_amount']);

        $this->assertEquals(5000, $paymentEntity['amount_refunded']);

        $this->assertEquals(5000, $refund['amount']);
    }


    public function testBajajFinservEmiFullRefundTest()
    {
        $emiPlan = $this->emiPlan;

        $this->fixtures->emiPlan->createMerchantSpecificEmiPlans();

        $card = $this->fixtures->create('card', ['name' => 'Test Name']);

        $this->fixtures->create('terminal',
            [
                'id' => 'AqdfGh5460opVt',
                'merchant_id' => '10000000000000',
                'gateway' => 'bajajfinserv',
                'gateway_merchant_id' => '250000002',
                'enabled' => 1,
                'emi' => 1,
                'emi_duration' => 9
            ]);

        $payment = $this->fixtures->create('payment',
            [
                'method'        => 'emi',
                'gateway'       => 'bajajfinserv',
                'otp_attempts'  => 0,
                'terminal_id'   => 'AqdfGh5460opVt',
                'emi_plan_id'   => '30111111111110',
                'card_id'       => $card->getId(),
                'status'        => 'created',
                'amount'        => 10000,
            ]);

        $this->fixtures->create('mozart',
            [
                'payment_id'        => $payment->getId(),
                'amount'            => 100,
                'action'            => 'authorize',
                'raw'               => '{}',
            ]);

        $this->fixtures->create('payment_analytics', ['ip' => '127.0.0.1', 'payment_id' => $payment->getId()]);

        $data = $this->testData[__FUNCTION__];

        $url = $this->getOtpSubmitUrl($payment);

        $data['request']['url'] = $url;

        $this->runRequestResponseFlow($data);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'authorized');

        $this->capturePayment($payment['id'], $payment['amount']);

        $refundAmount = null;

        $refund = $this->refundPayment($payment['id'], $refundAmount);

        $paymentEntity = $this->getLastEntity('payment', true);

        $txn = $this->getLastTransaction(true);

        $this->assertEquals(9882, $txn['debit']);

        $this->assertEquals(0, $txn['fee']);

        $this->assertEquals(0, $txn['tax']);

        $this->assertEquals(0, $txn['mdr']);

        $this->assertEquals(118, $paymentEntity['fee']);
        $this->assertEquals('refunded', $paymentEntity['status']);

        $this->assertEquals('emi', $paymentEntity['method']);

        $this->assertEquals('full', $paymentEntity['refund_status']);

        $this->assertEquals('AqdfGh5460opVt', $paymentEntity['terminal_id']);

        $this->assertEquals('processed', $refund['status']);

        $this->assertEquals($paymentEntity['emi_plan_id'], '30111111111110');

        $this->assertEquals(10000, $paymentEntity['base_amount']);

        $this->assertEquals(10000, $paymentEntity['amount_refunded']);

        $this->assertEquals(10000, $refund['amount']);
    }

    public function testBajajFinservEmiPaymentCreate()
    {
        $emiPlan = $this->emiPlan;

        $this->fixtures->emiPlan->createMerchantSpecificEmiPlans();

        $terminal = $this->fixtures->create(
            'terminal',
            [
                'id' => 'AqdfGh5460opVt',
                'merchant_id' => '10000000000000',
                'gateway' => 'bajajfinserv',
                'gateway_merchant_id' => '250000002',
                'enabled' => 1,
                'emi' => 1,
                'emi_duration' => 9
            ]);

        $this->fixtures->merchant->enableEmi();
        $this->ba->publicAuth();
        $this->payment['amount'] = 500000;
        $this->payment['method'] = 'emi';
        $this->payment['emi_duration'] = 9;

        // Converting to a bajaj finserv card number
        $this->payment['card']['number'] = '2030400000121212';

        unset($this->payment['card']['cvv']);

        unset($this->payment['card']['expiry_month']);

        unset($this->payment['card']['expiry_year']);

        $res = $this->doAuthPayment($this->payment);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals('01', $card['expiry_month']);
        $this->assertEquals('2099', $card['expiry_year']);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals($paymentEntity['terminal_id'], 'AqdfGh5460opVt');
        $this->assertEquals($paymentEntity['emi_plan_id'], '30111111111110');
        $this->assertEquals($paymentEntity['method'], 'emi');
        $this->assertEquals($paymentEntity['status'], 'created');
    }

    public function testOtpSubmitPayment()
    {
        $this->ba->publicAuth();

        $this->fixtures->emiPlan->createMerchantSpecificEmiPlans();

        $card = $this->fixtures->create('card', ['name' => 'Test Name']);

        $this->fixtures->create('terminal',
            [
                'id' => 'AqdfGh5460opVt',
                'merchant_id' => '10000000000000',
                'gateway' => 'bajajfinserv',
                'gateway_merchant_id' => '250000002',
                'enabled' => 1,
                'emi' => 1,
                'emi_duration' => 9
            ]);

        $payment = $this->fixtures->create('payment',
            [
                'method'        => 'emi',
                'gateway'       => 'bajajfinserv',
                'otp_attempts'  => 0,
                'terminal_id'   => 'AqdfGh5460opVt',
                'emi_plan_id'   => '30111111111110',
                'card_id'       => $card->getId(),
                'status'        => 'created',
                'amount'        => 100,
            ]);

        $this->fixtures->create('mozart',
            [
                'payment_id'        => $payment->getId(),
                'amount'            => 100,
                'action'            => 'authorize',
                'raw'               => '{}',
            ]);

        $this->fixtures->create('payment_analytics', ['ip' => '127.0.0.1', 'payment_id' => $payment->getId()]);


        $data = $this->testData[__FUNCTION__];

        $url = $this->getOtpSubmitUrl($payment);

        $data['request']['url'] = $url;

        $this->runRequestResponseFlow($data);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'authorized');

        $this->assertEquals($payment['gateway_captured'], true);

    }


    public function testOtpRetryExceededPayment()
    {
        $this->ba->publicAuth();

        $this->fixtures->emiPlan->createMerchantSpecificEmiPlans();

        $card = $this->fixtures->create('card', ['name' => 'Test Name']);

        $this->fixtures->create(
            'terminal',
            [
                'id' => 'AqdfGh5460opVt',
                'merchant_id' => '10000000000000',
                'gateway' => 'bajajfinserv',
                'gateway_merchant_id' => '250000002',
                'enabled' => 1,
                'emi' => 1,
                'emi_duration' => 9
            ]);

        $payment = $this->fixtures->create('payment', [
            'method'        => 'emi',
            'gateway'       => 'bajajfinserv',
            'otp_attempts'  => 3,
            'terminal_id'   => 'AqdfGh5460opVt',
            'emi_plan_id'   => '30111111111110',
            'card_id'       => $card->getId(),
        ]);

        $this->fixtures->create('payment_analytics', ['ip' => '127.0.0.1', 'payment_id' => $payment->getId()]);


        $data = $this->testData[__FUNCTION__];

        $url = $this->getOtpSubmitUrl($payment);

        $data['request']['url'] = $url;

        $this->runRequestResponseFlow($data);
    }


    public function testWrongOtpSubmitPayment()
    {
        $this->ba->publicAuth();

        $this->fixtures->emiPlan->createMerchantSpecificEmiPlans();

        $card = $this->fixtures->create('card', ['name' => 'Test Name']);

        $this->fixtures->create(
            'terminal',
            [
                'id' => 'AqdfGh5460opVt',
                'merchant_id' => '10000000000000',
                'gateway' => 'bajajfinserv',
                'gateway_merchant_id' => '250000002',
                'enabled' => 1,
                'emi' => 1,
                'emi_duration' => 9
            ]);

        $payment = $this->fixtures->create('payment', [
            'method'        => 'emi',
            'gateway'       => 'bajajfinserv',
            'otp_attempts'  => 0,
            'terminal_id'   => 'AqdfGh5460opVt',
            'emi_plan_id'   => '30111111111110',
            'card_id'       => $card->getId(),
        ]);

        $this->fixtures->create('mozart',
            [
                'payment_id'        => $payment->getId(),
                'amount'            => 100,
                'action'            => 'authorize',
                'raw'               => '{}'
            ]);

        $this->fixtures->create('payment_analytics', ['ip' => '127.0.0.1', 'payment_id' => $payment->getId()]);


        $data = $this->testData[__FUNCTION__];

        $url = $this->getOtpSubmitUrl($payment);

        $data['request']['url'] = $url;

        $this->runRequestResponseFlow($data);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'failed');
    }

    public function testBajajFinservVerify()
    {
        $this->ba->cronAuth();

        $this->fixtures->emiPlan->createMerchantSpecificEmiPlans();

        $card = $this->fixtures->create('card', ['name' => 'Test Name']);

        $this->fixtures->create(
            'terminal',
            [
                'id' => 'AqdfGh5460opVt',
                'merchant_id' => '10000000000000',
                'gateway' => 'bajajfinserv',
                'gateway_merchant_id' => '250000002',
                'enabled' => 1,
                'emi' => 1,
                'emi_duration' => 9
            ]);

        $createdAt = time() - 180;

        $payment = $this->fixtures->create('payment', [
            'method'        => 'emi',
            'gateway'       => 'bajajfinserv',
            'otp_attempts'  => 0,
            'terminal_id'   => 'AqdfGh5460opVt',
            'emi_plan_id'   => '30111111111110',
            'card_id'       => $card->getId(),
            'created_at'    => $createdAt,
            'authorized_at' => $createdAt,
            'verify_at'     => $createdAt,
            'captured_at'   => $createdAt,
            'status'        => 'captured',
        ]);

        $this->fixtures->create('mozart',
            [
                'id'                => 1,
                'payment_id'        => $payment->getId(),
                'amount'            => 100,
                'action'            => 'authorize',
                'raw'               => '{}'
            ]);

        $this->fixtures->create('payment_analytics', ['ip' => '127.0.0.1', 'payment_id' => $payment->getId()]);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data);

        $mozart = $this->getLastEntity('mozart', true);

        $this->assertEquals($mozart['id'], 1);

        $this->assertEquals($mozart['action'], 'authorize');

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['verified'], true);
    }

    public function testCapturePaymentForBajaj()
    {
        $this->ba->privateAuth();

        $this->fixtures->emiPlan->createMerchantSpecificEmiPlans();

        $card = $this->fixtures->create('card', ['name' => 'Test Name']);

        $this->fixtures->create(
            'terminal',
            [
                'id' => 'AqdfGh5460opVt',
                'merchant_id' => '10000000000000',
                'gateway' => 'bajajfinserv',
                'gateway_merchant_id' => '250000002',
                'enabled' => 1,
                'emi' => 1,
                'emi_duration' => 9
            ]);

        $payment = $this->fixtures->create('payment', [
            'method'           => 'emi',
            'gateway'          => 'bajajfinserv',
            'otp_attempts'     => 0,
            'terminal_id'      => 'AqdfGh5460opVt',
            'emi_plan_id'      => '30111111111110',
            'amount'           => '10000',
            'card_id'          => $card->getId(),
            'authorized_at'    => time(),
            'status'           => 'authorized',
            'gateway_captured' => true,

        ]);

        $this->fixtures->create('mozart',
            [
                'payment_id'        => $payment->getId(),
                'amount'            => 100,
                'action'            => 'authorize',
            ]);

        $this->fixtures->create('payment_analytics', ['ip' => '127.0.0.1', 'payment_id' => $payment->getId()]);

        $data = $this->testData[__FUNCTION__];

        $data['request']['url'] = '/payments/pay_' . $payment->getId() . '/capture';

        $this->runRequestResponseFlow($data);

        $this->assertEquals($payment['captured'], false);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['captured'], true);
    }

    protected function setupRedisMock($paymentArray = [])
    {
        Redis::shouldReceive('hGetAll')
            ->andReturn([]);

        Redis::shouldReceive('hDel')
            ->andReturn([]);

        Redis::shouldReceive('hSet')
            ->andReturn(null);

        Redis::shouldReceive('incr')
            ->andReturn(1);

        Redis::shouldReceive('set')
            ->andReturnUsing(
                function ($arg) use ($paymentArray)
                {
                    foreach ($paymentArray as $payment)
                    {
                        if ('mutex:' . $payment['id'] . '_verify' === $arg)
                        {
                            return null;
                        }
                    }
                    return true;
                });

        Redis::shouldReceive('get')
            ->andReturn(true);
    }
}
