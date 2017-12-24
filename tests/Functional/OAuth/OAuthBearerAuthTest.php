<?php

namespace RZP\Tests\Functional\OAuth;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Tests\Functional\Helpers\MocksDnsTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;

/**
 * @group dns-sensitive
 */
class OAuthBearerAuthTest extends OAuthTestCase
{
    use OAuthTrait;
    use MocksDnsTrait;
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/OAuthBearerAuthTestData.php';

        $this->setupMockDns();

        parent::setUp();
    }

    public function testBearerAuth()
    {
        $accessToken = $this->generateOAuthAccessToken();

        $this->ba->oauthBearerAuth($accessToken);

        $this->fixtures->create('payment', ['id' => '10000000000000']);

        $this->startTest();
    }

    public function testBearerAuthProdClient()
    {
        $accessToken = $this->generateOAuthAccessToken([], 'prod');

        $this->ba->oauthBearerAuth($accessToken);

        $this->fixtures->create('payment', ['id' => '10000000000000']);

        $this->startTest();
    }

    public function testBearerAuthDummyRouteScope()
    {
        $this->fixtures->create(
            'feature',
            [
                'entity_id' => '10000000000000',
                'name' => 'dummy'
            ]);

        $accessToken = $this->generateOAuthAccessToken(['scopes' => ['dummy.read']]);

        $this->ba->oauthBearerAuth($accessToken);

        $this->startTest();
    }

    public function testBearerAuthDummyRouteScopeFail()
    {
        $this->fixtures->create(
            'feature',
            [
                'entity_id' => '10000000000000',
                'name' => 'dummy'
            ]);

        $accessToken = $this->generateOAuthAccessToken(['scopes' => ['dummy.read']]);

        $this->ba->oauthBearerAuth($accessToken);

        $this->startTest();
    }

    public function testBearerAuthWriteAccess()
    {
        $accessToken = $this->generateOAuthAccessToken(['scopes' => ['read_write']]);

        $this->ba->oauthBearerAuth($accessToken);

        $this->startTest();
    }

    public function testBearerAuthOutsideOfScope()
    {
        $accessToken = $this->generateOAuthAccessToken();

        $this->ba->oauthBearerAuth($accessToken);

        $this->startTest();
    }

    public function testBearerAuthWithTamperedToken()
    {
        $accessToken = $this->generateOAuthAccessToken();

        $this->ba->oauthBearerAuth($accessToken . 'bad');

        $this->startTest();
    }

    public function testBearerAuthWithTamperedJWTPayload()
    {
        $accessToken = $this->generateOAuthAccessToken(['scopes' => ['read_write']]);

        $accessToken = $this->tamperExpiryOfAccessToken($accessToken);

        $this->ba->oauthBearerAuth($accessToken);

        $this->startTest();
    }

    public function testBearerAuthExpiredToken()
    {
        $pastExpiry = Carbon::today(Timezone::IST)->subDays(10)->timestamp;

        $accessToken = $this->generateOAuthAccessToken(['expires_at' => $pastExpiry]);

        $this->ba->oauthBearerAuth($accessToken);

        $this->startTest();
    }
}
