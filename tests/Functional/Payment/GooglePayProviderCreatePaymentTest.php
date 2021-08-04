<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Feature;
use RZP\Models\Payment;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class GooglePayProviderCreatePaymentTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/GooglePayProviderTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures([Feature\Constants::GPAY]);

        parent::setUp();
    }

    public function testCreateGooglePaymentWithoutMerchantFeature()
    {
        $payment = $this->createGpayRequest();

        $this->fixtures->merchant->removeFeatures([Feature\Constants::GPAY]);

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testCreateGooglePaymentWithMethod()
    {
        $payment = $this->createGpayRequest();

        $payment['method'] = 'upi';

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testCreateGooglePaymentWithAmountGreaterThanMaxAmount()
    {
        $payment = $this->createGpayRequest();

        $payment['amount'] = '50000000';

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testCreateGooglePayPaymentSuccess()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $googlePayPaymentCreateRequestData = $this->createGpayRequest();

        $methods = ['card', 'upi'];

        $this->createTerminalsForGpay($methods);

        $response = $this->doAuthPayment($googlePayPaymentCreateRequestData);

        $this->assertGpayResponse($response, $methods);

        $this->assertCardsResponse($response);

        $this->assertUpiResponse($response, true);

        $this->assertGpayPayment($response, $methods);
    }

    public function testCreateGooglePayPaymentWithCardTerminal()
    {
        $googlePayPaymentCreateRequestData = $this->createGpayRequest();

        $methods = ['card'];

        $this->createTerminalsForGpay($methods);

        $response = $this->doAuthPayment($googlePayPaymentCreateRequestData);

        $this->assertGpayResponse($response, $methods);

        $this->assertCardsResponse($response);

        $this->assertGpayPayment($response, $methods);
    }

    public function testCreateGooglePayPaymentWithUpiTerminal()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $googlePayPaymentCreateRequestData = $this->createGpayRequest();

        $methods = ['upi'];

        $this->createTerminalsForGpay($methods);

        $response = $this->doAuthPayment($googlePayPaymentCreateRequestData);

        $methods = ['card', 'upi'];

        $this->assertGpayResponse($response, $methods);

        $this->assertCardsResponse($response, true);

        $this->assertUpiResponse($response, true);

        $this->assertGpayPayment($response, $methods);
    }

    public function testCreateGooglePayPaymentMethodUpiDisabled()
    {
        $this->fixtures->merchant->disableMethod('10000000000000', 'upi');

        $googlePayPaymentCreateRequestData = $this->createGpayRequest();

        $methods = ['card', 'upi'];

        $this->createTerminalsForGpay($methods);

        $response = $this->doAuthPayment($googlePayPaymentCreateRequestData);

        $this->assertCount(1, $response['request']['content'][0]['allowedPaymentMethods']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($response['payment_id'], $payment['id']);

        $this->assertEquals('google_pay', $payment['authentication_gateway']);

        $this->assertEquals("unselected", $payment['method']);

        $this->assertEquals(0, $payment['cps_route']);

        $this->assertNull($payment['terminal_id']);

        $this->assertEquals('created', $payment['status']);

        $this->assertCardsResponse($response);
    }

    public function testCreateGooglePaymentNoTerminal()
    {
        $payment = $this->createGpayRequest();

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testCreateGooglePayPaymentS2SRedirectPrivateAuth()
    {
        $this->ba->privateAuth();

        $googlePayPaymentCreateRequestData = $this->createGpayRequest();

        $methods = ['card', 'upi'];

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->addFeatures(['s2s']);

        $this->createTerminalsForGpay($methods);

        $response = $this->doS2SPrivateAuthPayment($googlePayPaymentCreateRequestData);

        $payment = $this->getLastEntity('payment', true);

        /** Assert redirect response */
        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $this->assertArrayHasKey('provider', $response);

        $this->assertEquals('google_pay', $response['provider']);

        $this->assertArrayHasKey('data', $response);

        $this->assertArrayHasKey('google_pay', $response['data']);

        $this->assertArrayHasKey('card', $response['data']['google_pay']);

        $this->assertArrayHasKey('gateway_reference_id', $response['data']['google_pay']['card']);

        $this->assertArrayHasKey('supported_networks', $response['data']['google_pay']['card']);

        $this->assertEquals(['MASTERCARD', 'VISA'], $response['data']['google_pay']['card']['supported_networks']);

        $this->assertArrayHasKey('upi', $response['data']['google_pay']);

        $this->assertArrayHasKey('payee_vpa', $response['data']['google_pay']['upi']);
        /** Assert redirect response finish */

        $this->assertGpayPayment($response, $methods, true);

        $this->assertEquals('created', $payment['status']);
    }

    public function testCreateGooglePayPaymentS2SJsonPrivateAuth()
    {
        $this->ba->privateAuth();

        $googlePayPaymentCreateRequestData = $this->createGpayRequest();

        $methods = ['card', 'upi'];

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->addFeatures(['s2s','s2s_json']);

        $this->createTerminalsForGpay($methods);

        $response = $this->doS2SPrivateAuthJsonPayment($googlePayPaymentCreateRequestData);

        $payment = $this->getLastEntity('payment', true);

        /** Assert redirect response */
        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $this->assertEquals($payment['id'], $response['razorpay_payment_id']);

        $this->assertArrayHasKey('next', $response);

        $this->assertEquals('invoke_sdk', $response['next'][0]['action']);

        $this->assertEquals('google_pay', $response['next'][0]['provider']);

        $this->assertArrayHasKey('google_pay', $response['next'][0]['data']);

        $this->assertArrayHasKey('card', $response['next'][0]['data']['google_pay']);

        $this->assertArrayHasKey('upi', $response['next'][0]['data']['google_pay']);

        $this->assertEquals('poll', $response['next'][1]['action']);
        /** Assert redirect response finish */

        $this->assertGpayPayment($response, $methods, true);

        $this->assertEquals('created', $payment['status']);
    }

    protected function createGpayRequest()
    {
        $order = $this->fixtures->create('order');

        $googlePayPaymentCreateRequestData = $this->testData['googlePayProviderPaymentCreateRequestData'];

        $checkoutId = UniqueIdEntity::generateUniqueIdWithCheckDigit();

        $googlePayPaymentCreateRequestData['order_id'] = $order->getPublicId();

        $googlePayPaymentCreateRequestData['amount'] = $order['amount'];

        $googlePayPaymentCreateRequestData['_']['checkout_id'] = $checkoutId;

        return $googlePayPaymentCreateRequestData;
    }

    protected function createTerminalsForGpay($methods)
    {
        foreach ($methods as $method)
        {
            switch ($method)
            {
                case Payment\Method::UPI:

                    $this->fixtures->create('terminal:shared_upi_icici_intent_terminal');
                    $this->mockServerContentFunction(function (& $content, $action = null)
                    {
                        if ($action === 'authorize')
                        {
                            $content['refId'] = 'ICICIRefId';
                        }
                    }, 'upi_icici');

                    break;

                case Payment\Method::CARD:

                    $this->fixtures->create(
                        'terminal',
                        [
                            'merchant_id' => '10000000000000',
                            'gateway'     => 'cybersource',
                        ]
                    );

                    break;
            }
        }
    }

    /**
     * @param $response
     * @param bool $isEmptyCardNetworks
     */
    protected function assertCardsResponse($response, $isEmptyCardNetworks = false): void
    {
        $cardMethodResponse = $response['request']['content'][0]['allowedPaymentMethods'][0];

        $this->assertEquals('CARD', $cardMethodResponse['type']);

        if ($isEmptyCardNetworks)
        {
            $this->assertEquals([], $cardMethodResponse['parameters']['allowedCardNetworks']);
        }
        else
        {
            $this->assertEquals(['MASTERCARD', 'VISA'], $cardMethodResponse['parameters']['allowedCardNetworks']);
        }
    }

    /**
     * @param $response
     * @param bool $isCard
     */
    protected function assertUpiResponse($response, $isCard = false): void
    {
        if ($isCard)
        {
            $methodOrder = 1;
        }
        else
        {
            $methodOrder = 0;
        }

        $upiMethodResponse = $response['request']['content'][0]['allowedPaymentMethods'][$methodOrder];

        $this->assertEquals('UPI', $upiMethodResponse['type']);

        $this->assertEquals('ICICIRefId', $upiMethodResponse['parameters']['transactionReferenceId']);
    }

    /**
     * @param $response
     */
    protected function assertGpayResponse($response, $methods): void
    {
        $this->assertEquals('application', $response['type']);

        $this->assertEquals('google_pay', $response['application_name']);

        $this->assertEquals('sdk', $response['request']['method']);

        $this->assertCount(count($methods), $response['request']['content'][0]['allowedPaymentMethods']);
    }

    /**
     * @param $response
     * @param $methods
     * @param bool $s2s
     */
    protected function assertGpayPayment($response, $methods, $s2s = false): void
    {
        $payment = $this->getLastEntity('payment', true);

        if($s2s)
        {
            $this->assertEquals($response['razorpay_payment_id'], $payment['id']);
        }
        else
        {
            $this->assertEquals($response['payment_id'], $payment['id']);
        }

        $this->assertEquals('google_pay', $payment['authentication_gateway']);

        $this->assertEquals("unselected", $payment['method']);

        $this->assertEquals(0, $payment['cps_route']);

        if (in_array('upi', $methods) === true)
        {
            $this->assertNotNull($payment['terminal_id']);
        }

        else
        {
            $this->assertNull($payment['terminal_id']);
        }

        $this->assertEquals('created', $payment['status']);
    }
}
