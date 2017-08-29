<?php

namespace RZP\Tests\Functional\OAuth;

use Razorpay\OAuth\Token;

use RZP\Tests\Functional\RequestResponseFlowTrait;

class OAuthPublicTokenTest extends OAuthTestCase
{
    use OAuthTrait;
    use RequestResponseFlowTrait;

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
        $this->ba->publicAuth();

        $this->ba->oauthPublicToken($this->publicToken);

        $this->startTest();
    }

    public function testOAuthPublicTokenPrivateRoute()
    {
        $this->ba->privateAuth();

        $this->ba->oauthPublicToken($this->publicToken);

        $this->startTest();
    }

    public function testOAuthInvalidPublicToken()
    {
        $token = 'rzp_test_oauth_10000000Random';

        $this->ba->publicAuth();

        $this->ba->oauthPublicToken($token);

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

        $this->ba->publicAuth();

        $this->ba->oauthPublicToken($publicToken);

        $this->startTest();
    }
}
