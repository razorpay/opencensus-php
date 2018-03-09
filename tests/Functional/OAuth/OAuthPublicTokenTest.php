<?php

namespace RZP\Tests\Functional\OAuth;

use Razorpay\OAuth\Token;

use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class OAuthPublicTokenTest extends OAuthTestCase
{
    use OAuthTrait;
    use PaymentTrait;

    /**
     * @var string
     */
    protected $publicToken;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/OAuthPublicTokenTestData.php';

        parent::setUp();

        $token = factory(Token\Entity::class)->create(['type' => 'access_token', 'scopes' => ['read_only']]);

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

        $token = factory(Token\Entity::class)->create($tokenData);

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

        $token = factory(Token\Entity::class)->create($tokenData);

        $publicToken = $token->getPublicTokenWithPrefix();

        $this->ba->oauthPublicTokenAuth($publicToken);

        $this->startTest();
    }
}
