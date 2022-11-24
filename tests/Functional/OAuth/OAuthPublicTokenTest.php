<?php

namespace RZP\Tests\Functional\OAuth;

use Razorpay\OAuth\Token;
use Razorpay\OAuth\Client;

use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class OAuthPublicTokenTest extends OAuthTestCase
{
    use OAuthTrait;
    use PaymentTrait;

    /**
     * @var string
     */
    protected $publicToken;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/OAuthPublicTokenTestData.php';

        parent::setUp();

        $token = Token\Entity::factory()->create(['type' => 'access_token', 'scopes' => ['read_only']]);

        $this->publicToken = $token->getPublicTokenWithPrefix();
    }

    public function testAuthenticatePublicTokenViaKeyIdParam()
    {
        $this->ba->oauthPublicTokenAuth($this->publicToken);

        $this->startTest();
    }

    public function testCreatePaymentOAuth()
    {
        $this->generateOAuthAccessToken(['public_token' => 'TheTestAuthKey']);

        $this->ba->oauthPublicTokenAuth();

        $payment = $this->getDefaultPaymentArray();

        $this->doAuthPaymentOAuth($payment);
    }

    public function testOAuthPublicTokenPrivateRoute()
    {
        $this->ba->privateAuth($this->publicToken);

        $this->startTest();
    }

    public function testOAuthInvalidPublicToken()
    {
        $token = 'rzp_test_oauth_10000000Random';

        $this->ba->oauthPublicTokenAuth($token);

        $this->startTest();
    }

    public function testOAuthPublicTokenExpired()
    {
        $tokenData = [
            'type'       => 'access_token',
            'scopes'     => ['read_only'],
            'expires_at' => time() - 50,
        ];

        $token = Token\Entity::factory()->create($tokenData);

        $publicToken = $token->getPublicTokenWithPrefix();

        $this->ba->oauthPublicTokenAuth($publicToken);

        $this->startTest();
    }

    public function testOAuthPublicTokenInvalidScope()
    {
        $tokenData = [
            'type'   => 'access_token',
            'scopes' => ['dummy'],
        ];

        $token = Token\Entity::factory()->create($tokenData);

        $publicToken = $token->getPublicTokenWithPrefix();

        $this->ba->oauthPublicTokenAuth($publicToken);

        $this->startTest();
    }

    public function testStatusAfterPaymentOAuth()
    {
        $client = Client\Entity::factory()->create();

        $this->fixtures->create('order', ['amount' => 50000]);
        $order = $this->getLastEntity('order');
        $this->assertEquals($order['status'], 'created');

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];

        $this->generateOAuthAccessToken(
            [
                'public_token' => 'TheTestAuthKey',
                'scopes'       => ['read_write'],
                'client_id'    => $client->getId(),
            ]
        );
        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_id'   => $client->application_id,
            ]);

        $rzpPayment = $this->doAuthPaymentOAuth($payment);

        $this->assertArrayHasKey('razorpay_order_id', $rzpPayment);
        $this->assertArrayHasKey('razorpay_signature', $rzpPayment);

        $this->getLastEntity('payment');
        $this->assertEquals($order['id'], $rzpPayment['razorpay_order_id']);

        $order = $this->getLastEntity('order', true);
        $this->assertEquals($order['status'], 'attempted');
        $this->assertEquals($order['authorized'], true);

        // If a payment is requested for an already authorised order
        // That will fail with a BadRequestValidationFailureException
        $testData = $this->testData[__FUNCTION__];
        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];
        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPaymentOAuth($payment);
        });

        $this->capturePayment($rzpPayment['razorpay_payment_id'], $payment['amount']);

        $order = $this->getLastEntity('order');
        $this->assertEquals($order['status'], 'paid');

        // If a payment is requested for an already paid order
        // That will fail with a BadRequestValidationFailureException
        $testData = $this->testData[__FUNCTION__];
        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testStatusAfterAutoCapturePaymentWCallbackOAuth()
    {
        $this->markTestSkipped();
        // Sharp gateway will make payment go via callback flow
        $this->setUpSharpGateway();

        $this->testStatusAfterAutoCapturePaymentWoCallbackOAuth();
    }

    public function testStatusAfterAutoCapturePaymentWoCallbackOAuth()
    {
        $this->markTestSkipped();
        $order = $this->fixtures->create('order', ['amount' => 50000, 'payment_capture' => 1]);

        $payment = $this->getDefaultPaymentArray();
        $order['id'] = $payment['order_id'] = 'order_' . $order['id'];

        $token = $this->generateOAuthAccessToken(['scopes' => ['read_write']]);
        $response = $this->doAuthPaymentOAuth($payment, null, null, $token);

        $this->assertAutoCaptureResponse($response, $payment, $order);
    }

    public function setUpSharpGateway()
    {
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'sharp';
    }

    protected function assertAutoCaptureResponse(array $response, $payment, $order)
    {
        $actualSignature = $response['razorpay_signature'];

        unset($response['razorpay_signature']);

        ksort($response);
        $exceptedSignature = $this->getSignature($response, 'TheKeySecretForTests');

        $this->assertEquals($actualSignature, $exceptedSignature);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($order['id'], $payment['order_id']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(true, $payment['auto_captured']);

        $order = $this->getLastEntity('order', true);
        $this->assertEquals($order['status'], 'paid');
        $this->assertEquals($order['authorized'], true);
    }
}
