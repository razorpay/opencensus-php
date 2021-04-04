<?php

namespace RZP\Tests\Functional\Gateway\Mozart\Phonepe;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class PhonepeSwitchGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    const WALLET = 'phonepeswitch';

    protected $payment;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/PhonepeSwitchGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_phonepeswitch_terminal');

        $this->gateway = 'mozart';

        $this->setMockGatewayTrue();

        $this->fixtures->merchant->enableWallet('10000000000000', 'phonepeswitch');
    }

    public function testPayment()
    {
        $input['phonepe_switch_context'] = '{"transactionContext":{"orderContext":{"trackingInfo":{"type":"HTTPS","url":"https://google.com"}},"fareDetails":{"payableAmount":3900,"totalAmount":3900},"cartDetails":{"cartItems":[{"quantity":1,"address":{"addressString":"TEST","city":"TEST","pincode":"TEST","country":"TEST","latitude":1,"longitude":1},"shippingInfo":{"deliveryType":"STANDARD","time":{"timestamp":1561540218,"zoneOffSet":"+05:30"}},"category":"SHOPPING","itemId":"1234567890","price":3900,"itemName":"TEST"}]}}}';

        $order = $this->createOrder($input);

        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $payment['order_id'] = $order['id'];

        $authPayment = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment, 'testPayment');

        $this->assertEquals('1ShrdPhnpeSTrm', $payment['terminal_id']);

        $mozartEntity = $this->getLastEntity('mozart', true);

        $this->assertTestResponse($mozartEntity, 'testPaymentMozartEntity');
    }

    public function testRequestTampering()
    {
        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'pay_verify')
            {
                $content['data']['amount'] = '1230';
            }
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $paymentEntity = $this->getDbLastEntityToArray('payment', 'test');

        $this->assertEquals('failed', $paymentEntity['status']);
    }

    public function testPaymentIdMismatch()
    {
        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'pay_verify')
            {
                $content['data']['paymentId'] = 'ABCD1234567890'; //some random payment_id
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function()
        {
            $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

            $this->doAuthPayment($payment);
        });

        $paymentEntity = $this->getDbLastEntityToArray('payment', 'test');

        $this->assertEquals('failed', $paymentEntity['status']);
    }


    public function testVerifyPayment()
    {
        $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

        $authPayment = $this->doAuthPayment($payment);

        $this->payment = $this->verifyPayment($authPayment['razorpay_payment_id']);

        $this->assertSame($this->payment['payment']['verified'], 1);
    }

    public function testAuthFailed()
    {
        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'pay_verify')
            {
                $content['success'] = false;
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function ()
        {
            $payment = $this->getDefaultWalletPaymentArray(self::WALLET);

            $this->doAuthPayment($payment);
        });
    }

    public function testAuthFailedVerifySuccess()
    {
        $data = $this->testData[__FUNCTION__];

        $this->testAuthFailed();

        $payment = $this->getLastEntity('payment');

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->verifyPayment($payment['id']);
        });
    }


    protected function runPaymentCallbackFlowWalletPhonepeswitch($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        $this->response = $response;

        if ($mock)
        {
            $request = $this->makeFirstGatewayPaymentMockRequest($url, $method, $content);

            return $this->submitPaymentCallbackData($request['url'],$request['method'],$request['content']);
        }

        return null;
    }
}
