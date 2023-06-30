<?php

namespace RZP\Tests\Unit\Request\Edge;

use Razorpay\Edge\Passport;

use RZP\Http\BasicAuth\ClientAuthCreds;
use RZP\Http\BasicAuth\KeyAuthCreds;
use RZP\Http\Edge\Metric;
use RZP\Tests\TestCase;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Http\Edge\PostAuthenticate;
use RZP\Tests\Unit\Request\Traits\HasRequestCases;
use \Mockery;
use RZP\Trace\TraceCode;

class PostAuthenticateTest extends TestCase
{
    use HasRequestCases;

    protected function setUp(): void
    {
        parent::setUp();
        restore_error_handler();
    }

    /**
     * @param Passport\Passport|null $passport
     * @param boolean|null $expectedAuthenticated
     * @param boolean|null $expectedIdentified
     * @param string|null $expectedMode
     * @param string|null $expectedMerchantId
     * @param string|null $expectedAuth
     * @param boolean|null $expectedProxy
     * @param boolean|null $expectPassportAttrsMismatch
     * @return void
     *
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \PHPUnit\Framework\MockObject\RuntimeException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @dataProvider getRequestCases
     */
    public function testHandleWhenNoPassport(
        $passport,
        $expectedAuthenticated,
        $expectedIdentified,
        $expectedMode,
        $expectedMerchantId,
        $expectedAuth,
        $expectedProxy,
        $expectPassportAttrsMismatch)
    {
        $request = $this->mockPrivateRoute();
        app('request.ctx')->init();
        $reqCtx = app('request.ctx.v2');

        // Sets up request.ctx.v2's passport which could be null i.e. not
        // received from edge service.
        $reqCtx->passport = $passport;

        // Sets up basic auth expectations.
        $ba = $this->mockBasicAuth();
        $authCredsMock = Mockery::mock(KeyAuthCreds::class);
        $keyEntityMock = Mockery::mock('RZP\Models\Key\Entity');
        $ba->authCreds = $authCredsMock;
        $authCredsMock->shouldReceive('getKeyEntity')->andReturn($keyEntityMock);
        $keyEntityMock->shouldReceive('getMerchantId')->andReturn($expectedMerchantId);
        $ba->expects($this->any())->method('getMode')->willReturn($expectedMode);
        $ba->expects($this->any())->method('getMerchantId')->willReturn($expectedMerchantId);
        $ba->expects($this->any())->method('getPublicKey')->willReturn('rzp_live_10000000000000');
        $ba->expects($this->atLeastOnce())->method('getAuthType')->willReturn($expectedAuth);
        $ba->expects($this->atLeastOnce())->method('isProxyAuth')->willReturn($expectedProxy);
        $ba->expects($this->once())->method('setPassportDomain')->with($this->equalTo('razorpay'));

        // Asserts that no exceptions are thrown.
        (new PostAuthenticate)->handle($expectedAuthenticated, $request);
        // Asserts request.ctx.v2.
        $reqCtx = app('request.ctx.v2');
        $this->assertSame($reqCtx->authType, $expectedAuth);
        $this->assertSame($reqCtx->proxy, $expectedProxy);
        // Asserts passport.
        $passport = $reqCtx->passport;
        $this->assertNotNull($passport);
        $this->assertSame($passport->identified, $expectedIdentified);
        $this->assertSame($passport->authenticated, $expectedAuthenticated);
        if ($expectedAuthenticated === true) {
            $this->assertSame($passport->mode, $expectedMode);
        }
        $this->assertSame($passport->consumer !== null, $expectedMerchantId !== null);
        if ($expectedMerchantId !== null) {
            $this->assertSame($passport->consumer->id, $expectedMerchantId);
            $this->assertSame($passport->consumer->type, 'merchant');
        }
        // Asserts if mismatch errors were expected and logged.
        $this->assertSame($reqCtx->passportAttrsMismatch, $expectPassportAttrsMismatch);
    }

    public function getRequestCases(): array
    {
        // These are different passports for each request cases below.
        $passport3 = new Passport\Passport;
        $passport3->identified = true;
        $passport3->authenticated = true;
        $passport3->mode = 'live';
        $passport3->consumer = new Passport\ConsumerClaims;
        $passport3->consumer->id = '10000000000000';
        $passport3->consumer->type = 'merchant';
        $passport3->domain = 'razorpay';
        $passport3->credential = new Passport\CredentialClaims;
        $passport3->credential->username = 'rzp_live_10000000000000';
        $passport3->credential->publicKey = 'rzp_live_10000000000000';

        $passport4 = clone $passport3;

        $passport5 = clone $passport3;
        $passport5->mode = 'test';

        $passport6 = clone $passport3;
        $passport6->consumer = null;

        // Returns list of [passport, expectedAuthenticated, expectedIdentified, expectedMode, expectedMerchant, expectedAuth, expectedProxy, expectPassportAttrsMismatch].
        return [
            // Case 1: Private route.
            [null, true, true, 'live', '10000000000000', 'private', false, false],
            // Case 2: Direct route.
            [null, false, false, null, null, 'direct', false, false],
            // Case 3: Direct route and invalid passport comes form edge.
            [$passport3, false, false, null, null, 'direct', false, true],
            // Case 4: Private route and passport comes from edge.
            [$passport4, true, true, 'live', '10000000000000', 'private', false, false],
            // Case 5: Private route and invalid passport comes from edge.
            [$passport5, true, true, 'live', '10000000000000', 'private', false, true],
            // Case 6: Private route and invalid passport comes from edge.
            [$passport6, true, true, 'live', '10000000000000', 'private', false, true],
        ];
    }

    protected function mockBasicAuth()
    {
        $mock = $this->getMockBuilder(BasicAuth::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getMode', 'getMerchantId', 'getAuthType', 'isProxyAuth', 'getAccountId',
                'getPartnerMerchantId', 'getOAuthClientId', 'getOAuthApplicationId',
                'getPublicKey', 'getPassport', 'setPassportDomain', 'getRequestMetricDimensions', 'getPassportImpersonationClaims',
                'isKeylessPublicAuth', 'isPublicAuth', 'isOAuth'])
            ->getMock();
        $this->app->instance('basicauth', $mock);


        return $mock;
    }

    /**
     * @dataProvider getOAuthCases
     *
     * @param Passport\Passport $passport
     * @param                   $expectedMode
     * @param                   $expectedOAuthClientId
     * @param                   $expectedOAuthApplicationId
     * @param                   $expectedPartnerMerchantId
     * @param                   $expectedMerchantId
     * @param                   $expectedMismatch
     */
    public function testBearerAuth(Passport\Passport $passport,
                                                     $expectedMode,
                                                     $expectedOAuthClientId,
                                                     $expectedOAuthApplicationId,
                                                     $expectedPartnerMerchantId,
                                                     $expectedMerchantId,
                                                     $expectedMismatch)
    {
        $request = $this->mockPrivateRouteWithOAuthBearerToken();
        app('request.ctx')->init();
        $reqCtx = app('request.ctx.v2');

        $ba = $this->mockBasicAuth();
        $ba->expects($this->atLeastOnce())->method('getMode')->willReturn($expectedMode);
        $ba->expects($this->atLeastOnce())->method('getOAuthClientId')->willReturn($expectedOAuthClientId);
        $ba->expects($this->atLeastOnce())->method('getOAuthApplicationId')->willReturn($expectedOAuthApplicationId);
        $ba->expects($this->atLeastOnce())->method('getPartnerMerchantId')->willReturn($expectedPartnerMerchantId);
        $ba->expects($this->atLeastOnce())->method('getMerchantId')->willReturn($expectedMerchantId);


        $reqCtx->passport = $passport;

        (new PostAuthenticate)->handle(true, $request);
        $this->assertSame($passport->mode, $expectedMode);
        $this->assertSame($reqCtx->passportAttrsMismatch, $expectedMismatch);
    }

    public function getOAuthCases()
    {
        $passport                 = new Passport\Passport;
        $passport->identified     = true;
        $passport->authenticated  = true;
        $passport->mode           = "live";
        $passport->consumer       = new Passport\ConsumerClaims;
        $passport->consumer->id   = "partner_id";
        $passport->consumer->type = "merchant";

        $passport->oauth            = new Passport\OAuthClaims;
        $passport->oauth->ownerId   = "merchant_id";
        $passport->oauth->ownerType = "merchant";
        $passport->oauth->clientId  = "client_id";
        $passport->oauth->appId     = "app_id";
        return [
            // Case 1 - Successful case.
            [$passport, $passport->mode, $passport->oauth->clientId, $passport->oauth->appId, $passport->consumer->id, $passport->oauth->ownerId, false],
            // Case 2 - Mismatch client_id
            [$passport, $passport->mode, 'i_client_id', $passport->oauth->appId, $passport->consumer->id, $passport->oauth->ownerId, true],
            // Case 3 - Mismatch app_id
            [$passport, $passport->mode, $passport->oauth->clientId, 'i_app_id', $passport->consumer->id, $passport->oauth->ownerId, true],
            // Case 4 - Mismatch partner_id
            [$passport, $passport->mode, $passport->oauth->clientId, $passport->oauth->appId, 'i_partner_id', $passport->oauth->ownerId, true],
            // Case 5 - Mismatch merchant_id
            [$passport, $passport->mode, $passport->oauth->clientId, $passport->oauth->appId, $passport->consumer->id, 'i_merchant_id', true],
        ];
    }

    /**
     * @dataProvider getKeyAuthCases
     *
     * @param Passport\Passport $passport
     * @param string $expectedConsumerId
     * @param string $expectedConsumerType
     * @param string $expectedCredentialPublicKey
     * @param bool $expectedMismatch
     */
    public function testKeyAuth(Passport\Passport $passport,
                                string            $expectedMode,
                                string            $expectedConsumerId,
                                string            $expectedConsumerType,
                                string            $expectedImpersonatedConsumerId,
                                string            $expectedImpersonatedConsumerType,
                                string            $expectedImpersonationType,
                                string            $expectedCredentialPublicKey,
                                bool              $expectedMismatch)
    {
        $request = $this->mockPrivateRouteWithLiveMode();
        app('request.ctx')->init();
        app('request.ctx')->resolveKeyIdIfApplicable();
        $reqCtx = app('request.ctx.v2');

        $trace = Mockery::mock('Razorpay\Trace\Logger');
        $this->app->instance("trace", $trace);
        $trace->shouldReceive('histogram')->times(1);
        //there shouldn't be any exceptions
        $trace->shouldReceive('error')->times(0);

        $ba = $this->mockBasicAuth();
        $authCredsMock = Mockery::mock(KeyAuthCreds::class);
        $keyEntityMock = Mockery::mock('RZP\Models\Key\Entity');
        $ba->authCreds = $authCredsMock;
        $authCredsMock->shouldReceive('getKeyEntity')->andReturn($keyEntityMock);
        $keyEntityMock->shouldReceive('getMerchantId')->andReturn($expectedConsumerId);
        $ba->expects($this->once())->method('getMode')->willReturn($expectedMode);
        $ba->expects($this->any())->method('getMerchantId')->willReturn($expectedImpersonatedConsumerId);
        $ba->expects($this->any())->method('getAccountId')->willReturn("10000000000000");
        $ba->expects($this->atLeast(1))->method('getPublicKey')->willReturn($expectedCredentialPublicKey);
        $ba->expects($this->once())->method('getAuthType')->willReturn('private');
        $ba->expects($this->once())->method('isProxyAuth')->willReturn(false);

        // overwrite passport type attributes
        $passport->consumer->type = $expectedConsumerType;
        $passport->impersonation->type = $expectedImpersonationType;
        $passport->impersonation->consumer->type = $expectedImpersonatedConsumerType;
        $reqCtx->passport = $passport;

        if ($expectedMismatch === true)
        {
            $traceCount = ($expectedConsumerType == 'partner') ? 2 : 1;
            $trace->shouldReceive('count')->times($traceCount);
            $trace->shouldReceive('warning')->with(TraceCode::PASSPORT_ATTRS_MISMATCH, Mockery::type('array'));
            if ($expectedConsumerType == 'partner')
            {
                $trace->shouldReceive('warning')->with(TraceCode::EDGE_AUTHFLOW_MISMATCH, Mockery::type('array'));
            }
        }

        (new PostAuthenticate)->handle(true, $request);
        $this->assertSame($passport->mode, $expectedMode);
        $this->assertSame($reqCtx->passportAttrsMismatch, $expectedMismatch);
    }

    public function getKeyAuthCases()
    {
        $passport = new Passport\Passport;
        $passport->identified = true;
        $passport->authenticated = true;
        $passport->mode = "live";
        $passport->consumer = new Passport\ConsumerClaims;
        $passport->consumer->id = "10000000000000";
        $passport->consumer->type = "merchant";

        $passport->impersonation = new Passport\ImpersonationClaims;
        $passport->impersonation->consumer = new Passport\ConsumerClaims;
        $passport->impersonation->consumer->id = "20000000000000";
        $passport->impersonation->consumer->type = "merchant";
        $passport->impersonation->type = "merchant";

        $passport->credential = new Passport\CredentialClaims;
        $passport->credential->username = "rzp_live_10000000000000";
        $passport->credential->publicKey = "rzp_live_10000000000000";

        return [
            // Case 1 - Successful case.
            [$passport, $passport->mode, $passport->consumer->id, $passport->consumer->type,
                $passport->impersonation->consumer->id, $passport->impersonation->consumer->type, $passport->impersonation->type,
                $passport->credential->publicKey, false],
            // Case 2 - Mismatch consumer id
            [$passport, $passport->mode, '20000000000000', $passport->consumer->type,
                $passport->impersonation->consumer->id, $passport->impersonation->consumer->type, $passport->impersonation->type,
                $passport->credential->publicKey, true],
            // Case 3 - Mismatch consumer type
            [$passport, $passport->mode, $passport->consumer->id, 'partner',
                $passport->impersonation->consumer->id, $passport->impersonation->consumer->type, $passport->impersonation->type,
                $passport->credential->publicKey, true],
            // Case 4 - Mismatch impersonation consumer id
            [$passport, $passport->mode, $passport->consumer->id, $passport->consumer->type,
                '40000000000000', $passport->impersonation->consumer->type, $passport->impersonation->type,
                $passport->credential->publicKey, true],
            // Case 5 - Mismatch impersonation consumer type
            [$passport, $passport->mode, $passport->consumer->id, $passport->consumer->type,
                $passport->impersonation->consumer->id, 'partner', $passport->impersonation->type,
                $passport->credential->publicKey, true],
            // Case 6 - Mismatch impersonation type
            [$passport, $passport->mode, $passport->consumer->id, $passport->consumer->type,
                $passport->impersonation->consumer->id, $passport->impersonation->consumer->type, "merchat",
                $passport->credential->publicKey, true],
            // Case 7 - Mismatch credential publickey
            [$passport, $passport->mode, $passport->consumer->id, $passport->consumer->type,
                $passport->impersonation->consumer->id, $passport->impersonation->consumer->type, $passport->impersonation->type,
                "rzp_live_30000000000000", true],
        ];
    }

    /**
     * @dataProvider getKeyAuthOnInvalidRequestsCases
     *
     * @param Passport\Passport $passport
     * @param string|null $expectedMode
     * @param bool $mockAuthCreds
     * @param bool $mockGetKeyEntity
     * @param bool $mockPassportConsumer
     * @param bool $mockPassportImpersonation
     * @param bool $mockPassportCredential
     * @param bool $expectedAuthFlowMismatch
     * @param bool $expectedMismatch
     */
    public function testKeyAuthOnInvalidRequests(Passport\Passport $passport,
                                                 $expectedMode,
                                                 bool $mockAuthCreds,
                                                 bool $mockGetKeyEntity,
                                                 bool $mockPassportConsumer,
                                                 bool $mockPassportImpersonation,
                                                 bool $mockPassportCredential,
                                                 bool $expectedAuthFlowMismatch,
                                                 bool $expectedMismatch)
    {

        $request = $this->mockPrivateRouteWithLiveMode();
        app('request.ctx')->init();
        app('request.ctx')->resolveKeyIdIfApplicable();
        $reqCtx = app('request.ctx.v2');

        $trace = Mockery::mock('Razorpay\Trace\Logger');
        $this->app->instance("trace", $trace);
        $trace->shouldReceive('histogram')->times(1);
        //there shouldn't be any exceptions
        $trace->shouldReceive('error')->times(0);

        $ba = $this->mockBasicAuth();
        $authCredsMock = Mockery::mock(KeyAuthCreds::class);
        $ba->authCreds = $mockAuthCreds ? $authCredsMock : null;
        if ($mockGetKeyEntity === true) {
            $authCredsMock->shouldReceive('getKeyEntity')->andReturn('rzp_test_10000000000000');
        }
        $ba->expects($this->once())->method('getMode')->willReturn('live');
        $ba->expects($this->any())->method('getMerchantId')->willReturn('10000000000000');
        $ba->expects($this->any())->method('getAccountId')->willReturn("");
        $ba->expects($this->atLeast(1))->method('getPublicKey')->willReturn('');
        $ba->expects($this->once())->method('getAuthType')->willReturn('private');
        $ba->expects($this->once())->method('isProxyAuth')->willReturn(false);

        // overwrite passport attributes
        $passport->mode = $expectedMode;
        $passport->consumer = null;
        $passport->impersonation = null;
        $passport->credential = null;

        if ($mockPassportConsumer) {
            $passport->consumer = new Passport\ConsumerClaims;
            $passport->consumer->id = '123';
            $passport->consumer->type = 'merchant';
        }
        if ($mockPassportImpersonation) {
            $passport->impersonation = new Passport\ImpersonationClaims;
            $passport->impersonation->type = 'partner';
            $passport->impersonation->consumer = new Passport\ConsumerClaims;
            $passport->impersonation->consumer->id = "20000000000000";
            $passport->impersonation->consumer->type = "merchant";
        }
        if ($mockPassportCredential) {
            $passport->credential = new Passport\CredentialClaims;
            $passport->credential->username = "rzp_live_10000000000000";
            $passport->credential->publicKey = "rzp_live_10000000000000";
        }
        $reqCtx->passport = $passport;

        if ($expectedMismatch === true)
        {
            $trace->shouldReceive('count')->times(1);
            $trace->shouldReceive('warning')->with(TraceCode::PASSPORT_ATTRS_MISMATCH, Mockery::type('array'));
        }
        if ($expectedAuthFlowMismatch === true)
        {
            $trace->shouldReceive('count')->times(1);
            $trace->shouldReceive('warning')->with(TraceCode::EDGE_AUTHFLOW_MISMATCH, Mockery::type('array'));
        }

        (new PostAuthenticate)->handle(true, $request);
        $this->assertSame($reqCtx->passportAttrsMismatch, $expectedMismatch);
        // ensure values are not set by post-authenticate methods on mismatch if not exists
        if (!$mockPassportConsumer) {
            $this->assertSame($passport->consumer, null);
        }
        if (!$mockPassportImpersonation) {
            $this->assertSame($passport->impersonation, null);
        }
        if (!$mockPassportCredential) {
            $this->assertSame($passport->credential, null);
        }
    }

    public function getKeyAuthOnInvalidRequestsCases()
    {
        // unauthenticated and unidentified
        $passport = new Passport\Passport;
        $passport->identified = false;
        $passport->authenticated = false;
        $passport->mode = null;
        $passport->consumer = null;
        $passport->impersonation = null;
        $passport->credential = null;

        return [
            // case 1 - not identified not authenticated authCreds not set due to key and secret not passed
            [$passport, $passport->mode, false, false, false, false, false, false, true],
            // case 2 - not identified not authenticated keyEntity not set due to invalid apikey
            [$passport, $passport->mode, true, true, false, false, false, true, true],
            // case 3 - passport consumer not present
            [$passport, $passport->mode, true, true, false, false, true, true, true],
            // case 4 - passport credential not present
            [$passport, $passport->mode, true, true, true, true, false, true, true],
            // case 5 - passport impersonation not present
            [$passport, $passport->mode, true, true, true, false, true, false, true],
        ];
    }

    /**
     * @dataProvider getPartnerAuthCases
     *
     * @param Passport\Passport $passport
     * @param string $expectedMode
     * @param string $expectedConsumerId
     * @param string $expectedConsumerType
     * @param string $expectedImpersonatedConsumerId
     * @param string $expectedImpersonatedConsumerType
     * @param string $expectedImpersonationType
     * @param string $expectedCredentialPublicKey
     * @param bool $expectedMismatch
     */
    public function testPartnerAuth(Passport\Passport $passport,
                                    string            $expectedMode,
                                    string            $expectedConsumerId,
                                    string            $expectedConsumerType,
                                    string            $expectedImpersonatedConsumerId,
                                    string            $expectedImpersonatedConsumerType,
                                    string            $expectedImpersonationType,
                                    string            $expectedCredentialPublicKey,
                                    bool              $expectedMismatch)
    {
        $request = $this->mockPrivateRouteWithPartnerAuthToken();
        app('request.ctx')->init();
        app('request.ctx')->resolveKeyIdIfApplicable();
        $reqCtx = app('request.ctx.v2');

        $trace = Mockery::mock('Razorpay\Trace\Logger');
        $this->app->instance("trace", $trace);
        $trace->shouldReceive('histogram')->times(1);
        $trace->shouldReceive('count')->times(1);
        //there shouldn't be any exceptions
        $trace->shouldReceive('error')->times(0);
        $trace->shouldReceive('warning')->with(TraceCode::EDGE_AUTHFLOW_MISMATCH, Mockery::type('array'));

        $ba = $this->mockBasicAuth();
        $ba->expects($this->any())->method('getMode')->willReturn($expectedMode);
        $ba->expects($this->once())->method('getPartnerMerchantId')->willReturn($expectedConsumerId);
        $ba->expects($this->once())->method('getAccountId')->willReturn("10000000000000");
        $ba->expects($this->any())->method('getMerchantId')->willReturn($expectedImpersonatedConsumerId);
        $ba->expects($this->atLeast(1))->method('getPublicKey')->willReturn($expectedCredentialPublicKey);
        $ba->expects($this->once())->method('getAuthType')->willReturn('private');
        $ba->expects($this->atLeastOnce())->method('isProxyAuth')->willReturn(false);

        // overwrite passport type attributes
        $passport->consumer->type = $expectedConsumerType;
        $passport->impersonation->type = $expectedImpersonationType;
        $passport->impersonation->consumer->type = $expectedImpersonatedConsumerType;
        $reqCtx->passport = $passport;

        if ($expectedMismatch === true)
        {
            $trace->shouldReceive('count')->times(1);
            $trace->shouldReceive('warning')->with(TraceCode::PASSPORT_ATTRS_MISMATCH, Mockery::type('array'));
        }

        (new PostAuthenticate)->handle(true, $request);
        $this->assertSame($passport->mode, $expectedMode);
        $this->assertSame($reqCtx->passportAttrsMismatch, $expectedMismatch);
    }

    public function getPartnerAuthCases()
    {
        $passport = new Passport\Passport;
        $passport->identified = true;
        $passport->authenticated = true;
        $passport->mode = "live";
        $passport->consumer = new Passport\ConsumerClaims;
        $passport->consumer->id = "partner_id";
        $passport->consumer->type = "partner";

        $passport->impersonation = new Passport\ImpersonationClaims;
        $passport->impersonation->consumer = new Passport\ConsumerClaims;
        $passport->impersonation->consumer->id = "merchant_id";
        $passport->impersonation->consumer->type = "merchant";
        $passport->impersonation->type = "partner";

        $passport->credential = new Passport\CredentialClaims;
        $passport->credential->username = "rzp_live_10000000000000";
        $passport->credential->publicKey = "rzp_live_10000000000000-acc_20000000000000";

        return [
            // Case 1 - Successful case.
            [$passport, $passport->mode, $passport->consumer->id, $passport->consumer->type,
                $passport->impersonation->consumer->id, $passport->impersonation->consumer->type, $passport->impersonation->type,
                $passport->credential->publicKey, false],
            // Case 2 - Mismatch consumer id
            [$passport, $passport->mode, 'i_partner_id', $passport->consumer->type,
                $passport->impersonation->consumer->id, $passport->impersonation->consumer->type, $passport->impersonation->type,
                $passport->credential->publicKey, true],
            // Case 3 - Mismatch consumer type
            [$passport, $passport->mode, $passport->consumer->id, 'test',
                $passport->impersonation->consumer->id, $passport->impersonation->consumer->type, $passport->impersonation->type,
                $passport->credential->publicKey, true],
            // Case 4 - Mismatch impersonation consumer id
            [$passport, $passport->mode, $passport->consumer->id, $passport->consumer->type,
                'merchant2', $passport->impersonation->consumer->type, $passport->impersonation->type,
                $passport->credential->publicKey, true],
            // Case 5 - Mismatch impersonation consumer type
            [$passport, $passport->mode, $passport->consumer->id, $passport->consumer->type,
                $passport->impersonation->consumer->id, 'partner', $passport->impersonation->type,
                $passport->credential->publicKey, true],
            // Case 6 - Mismatch impersonation type
            [$passport, $passport->mode, $passport->consumer->id, $passport->consumer->type,
                $passport->impersonation->consumer->id, $passport->impersonation->consumer->type, 'merchant',
                $passport->credential->publicKey, true],
            // Case 7 - Mismatch credential
            [$passport, $passport->mode, $passport->consumer->id, $passport->consumer->type,
                $passport->impersonation->consumer->id, $passport->impersonation->consumer->type, $passport->impersonation->type,
                "rzp_live_20000000000000-acc_30000000000000", true],

        ];
    }

    /**
     * @dataProvider getAuthenticationMismatchesCases
     *
     * @param Passport\Passport $passport
     * @param                   $consumerId
     * @param                   $edgeAuthenticated
     * @param                   $consumerType
     * @param                   $publicKey
     * @param                   $apiAuthenticated
     * @param                   $mode
     * @param                   $isAuthenticatedPathRoute
     * @param string            $routeName
     */
    public function testAuthenticationMismatches(Passport\Passport $passport,
                                                                   $consumerId,
                                                                   $consumerType,
                                                                   $publicKey,
                                                                   $mode,
                                                                   $edgeAuthenticated,
                                                                   $apiAuthenticated,
                                                                   $isAuthenticatedPathRoute,
                                                 string            $routeName = 'invoice_fetch_multiple')
    {

        $edgeAuthenticatedBool = $edgeAuthenticated === 'true';
        $passport->mode = $mode;
        if ($consumerType !== NULL || $consumerId !== NULL) {
            $passport->consumer = new Passport\ConsumerClaims;
            $passport->consumer->type = $consumerType;
            $passport->consumer->id = $consumerId;
        }

        if ($publicKey !== NULL) {
            $passport->credential = new Passport\CredentialClaims;
            $passport->credential->username = $publicKey;
            $passport->credential->publicKey = $publicKey;
        }

        $passport->authenticated = $edgeAuthenticatedBool;
        $passport->identified = true;

        if ($isAuthenticatedPathRoute) {
            $request = $this->mockPrivateRouteWithLiveMode($routeName);
        } else {
            $request = $this->mockPublicRouteWithKeyInHeaders();
        }

        $request->headers->set('X-AUTHENTICATION-RESULT', $edgeAuthenticated);

        app('request.ctx')->init();
        app('request.ctx')->resolveKeyIdIfApplicable();

        $reqCtx = app('request.ctx.v2');
        $reqCtx->passport = $passport;

        $trace = Mockery::mock('Razorpay\Trace\Logger');

        $this->app->instance("trace", $trace);

        $ba = $this->mockBasicAuth();

        $trace->shouldReceive('histogram')->times(1);
        //there shouldn't be any exceptions
        $trace->shouldReceive('error')->times(0);

        if ($isAuthenticatedPathRoute && $edgeAuthenticated !== NULL) {
            $count = $edgeAuthenticatedBool !== $apiAuthenticated ? 1 : 0;
            $trace->shouldReceive('warning')->times($count)->with(TraceCode::EDGE_AUTHENTICATION_MISMATCH, Mockery::type('array'));

            $ba->expects($this->any())->method('getMode')->willReturn($mode);

            if ($edgeAuthenticatedBool !== $apiAuthenticated) {
                $ba->expects($this->atLeastOnce())->method('getMerchantId')->willReturn($consumerId);
                $ba->expects($this->atLeastOnce())->method('getPublicKey')->willReturn($publicKey);
            }
        }

        (new PostAuthenticate)->handle($apiAuthenticated, $request);
        $this->assertSame($passport->mode, "live");
        Mockery::close();
    }

    public function getAuthenticationMismatchesCases()
    {
        $passport = new Passport\Passport;
        $passport->identified = true;
        return [
            // Case 1 - Successful case.
            [$passport, "merchant_id", "merchant", "rzp_live_TheLiveAuthKey", "live", "true", false, true],
            // Case 2 - Edge unauthenticated
            [$passport, "merchant_id", "merchant", "rzp_live_TheLiveAuthKey", "live", "false", true, true],
            // Case 3 - Successful authenticated case on payouts_summary
            [$passport, "merchant_id", "merchant", "rzp_live_TheLiveAuthKey", "live", "true", true, true, "payouts_summary"],
            // Case 4 - Successful unauthenticated case on payouts_summary
            [$passport, "merchant_id", "merchant", "rzp_live_TheLiveAuthKey", "live", "false", false, true, "payouts_summary"],
            // Case 5 - Edge unidentified and authenticated
            [$passport, NULL, NULL, "rzp_live_TheLiveAuthKey", "live", "true", false, true],
            // Case 6 - Edge unidentified and authenticated on authenticated path
            [$passport, NULL, NULL, NULL, "live", "true", false, true],
            // Case 7 - Edge unidentified and authenticated on non-authenticated path
            [$passport, NULL, NULL, NULL, "live", "true", false, false],
            // Case 8 - no edge passport
            [$passport, NULL, NULL, NULL, "live", NULL, false, false],
        ];
    }

    /**
     * @dataProvider getImpersonationMismatchesCases
     *
     * @param Passport\Passport $passport
     * @param                   $consumerId
     * @param                   $consumerType
     * @param                   $publicKey
     * @param                   $mode
     * @param                   $edgeImpersonated
     * @param                   $apiAuthenticated
     * @param                   $impersonationType
     * @param                   $subMerchant
     * @param string $routeName
     */
    public function testImpersonationMismatchesWithImpersonation(Passport\Passport $passport,
                                                                                   $consumerId, $consumerType,
                                                                                   $publicKey, $mode,
                                                                                   $edgeImpersonated, $apiAuthenticated,
                                                                                   $impersonationType, $subMerchant)
    {

        $edgeImpersonatedBool = $edgeImpersonated === 'true';
        $passport->mode = $mode;
        $passport->consumer->id = $consumerId;
        $passport->consumer->type = $consumerType;
        $passport->credential->username = $publicKey;
        $passport->impersonation->type = $impersonationType;
        $passport->impersonation->consumer->id = $subMerchant;
        $passport->impersonation->consumer->type = $consumerType;
        $passport->authenticated = $edgeImpersonatedBool;
        $passport->identified = true;

        $request = $this->mockPrivateRouteWithLiveMode();

        $request->headers->set('X-IMPERSONATION-RESULT', $edgeImpersonated);

        app('request.ctx')->init();
        app('request.ctx')->resolveKeyIdIfApplicable();

        $reqCtx = app('request.ctx.v2');
        $reqCtx->passport = $passport;

        $trace = Mockery::mock('Razorpay\Trace\Logger');

        $trace->shouldReceive('histogram')->times(1);

        $trace->shouldReceive('error')->times(0);

        $ba = $this->mockBasicAuth();


        $count = $edgeImpersonatedBool !== $apiAuthenticated ? 1 : 0;

        $trace->shouldReceive('warning')->times($count);

        $ba->expects($this->any())->method('getMode')->willReturn($mode);

        if ($edgeImpersonatedBool !== $apiAuthenticated) {
            $ba->expects($this->atLeastOnce())->method('getMerchantId')->willReturn($consumerId);
            $ba->expects($this->atLeastOnce())->method('getPublicKey')->willReturn($publicKey);
            $ba->expects($this->any())->method('getPassport')->willReturn([
                'impersonation' => [
                    'type' => $impersonationType,
                    'consumer' => [
                        'type' => $consumerType,
                        'id' => $subMerchant
                    ]
                ]
            ]);
        }


        $this->app->instance("trace", $trace);

        (new PostAuthenticate)->handle($apiAuthenticated, $request);
        $this->assertSame($passport->mode, "live");
        Mockery::close();
    }

    /**
     * @dataProvider getImpersonationMismatchesCases
     *
     * @param Passport\Passport $passport
     * @param                   $consumerId
     * @param                   $consumerType
     * @param                   $publicKey
     * @param                   $mode
     * @param                   $edgeImpersonated
     * @param                   $apiAuthenticated
     * @param                   $impersonationType
     * @param                   $subMerchant
     */
    public function testImpersonationMismatchesWithoutImpersonation(Passport\Passport $passport,
                                                                                      $consumerId, $consumerType,
                                                                                      $publicKey, $mode,
                                                                                      $edgeImpersonated, $apiAuthenticated,
                                                                                      $impersonationType, $subMerchant)
    {

        $edgeImpersonatedBool = $edgeImpersonated === 'true';
        $passport->mode = $mode;
        $passport->consumer->id = $consumerId;
        $passport->consumer->type = $consumerType;
        $passport->credential->username = $publicKey;
        $passport->impersonation->type = $impersonationType;
        $passport->impersonation->consumer->id = $subMerchant;
        $passport->impersonation->consumer->type = $consumerType;
        $passport->authenticated = $edgeImpersonatedBool;
        $passport->identified = true;

        $request = $this->mockPrivateRouteWithLiveMode();

        $request->headers->set('X-IMPERSONATION-RESULT', $edgeImpersonated);

        app('request.ctx')->init();
        app('request.ctx')->resolveKeyIdIfApplicable();

        $reqCtx = app('request.ctx.v2');
        $reqCtx->passport = $passport;

        $trace = Mockery::mock('Razorpay\Trace\Logger');


        $count = $edgeImpersonatedBool !== $apiAuthenticated ? 1 : 0;

        $trace->shouldReceive('warning')->times($count);

        $trace->shouldReceive('histogram')->times(1);

        $trace->shouldReceive('error')->times(0);

        $this->app->instance("trace", $trace);

        $ba = $this->mockBasicAuth();

        $ba->expects($this->any())->method('getMode')->willReturn($mode);

        if ($edgeImpersonatedBool !== $apiAuthenticated) {
            $ba->expects($this->atLeastOnce())->method('getMerchantId')->willReturn($consumerId);
            $ba->expects($this->atLeastOnce())->method('getPublicKey')->willReturn($publicKey);
            $ba->expects($this->any())->method('getPassport')->willReturn([]);
        }


        (new PostAuthenticate)->handle($apiAuthenticated, $request);
        $this->assertSame($passport->mode, "live");
        Mockery::close();
    }

    public function getImpersonationMismatchesCases()
    {
        $passport = new Passport\Passport;
        $passport->identified = true;
        $passport->consumer = new Passport\ConsumerClaims;
        $passport->credential = new Passport\CredentialClaims;
        $passport->impersonation = new Passport\ImpersonationClaims;
        $passport->impersonation->consumer = new Passport\ConsumerClaims;
        return [
            // Case 1 - Api authentication false
            [$passport, "merchant_id", "merchant", "rzp_live_partner_TheLiveAuthKey", "live", "true", false, "partner", "account_id"],
            // Case 2 - Edge impersonation false
            [$passport, "merchant_id", "merchant", "rzp_live_partner_TheLiveAuthKey", "live", "false", true, "partner", "account_id"],
            // Case 3 - Successful case for impersonation true on edge and api
            [$passport, "merchant_id", "merchant", "rzp_live_partner_TheLiveAuthKey", "live", "true", true, "partner", "account_id"],
            // Case 4 - Successful case for impersonation false on edge and api
            [$passport, "merchant_id", "merchant", "rzp_live_partner_TheLiveAuthKey", "live", "false", false, "partner", "account_id"],
            // Case 5 - Edge identified and impersonated
            [$passport, "merchant_id", "merchant", "rzp_live_partner_TheLiveAuthKey", "live", "true", false, "partner", "account_id"],
            // Case 6 - Edge identified and non-impersonated
            [$passport, "merchant_id", "merchant", "rzp_live_partner_TheLiveAuthKey", "live", NULL, false, "partner", "account_id"],
            // Case 7 - Edge unidentified and non-impersonated
            [$passport, NULL, NULL, "rzp_live_partner_TheLiveAuthKey", "live", NULL, false, "partner", "account_id"],
            // Case 8 - no edge passport
            [$passport, NULL, NULL, NULL, "live", NULL, false, "partner", "account_id"]
        ];
    }

    /**
     * @dataProvider getLogsPassportMismatchMetricsCases
     * @param string $requestType
     * @param string $authType
     */
    public function testLogsPassportMismatchMetrics(string $requestType, string $authType)
    {
        $passport = new Passport\Passport;
        $passport->identified = true;
        $passport->authenticated = true;
        $passport->mode = "live";

        $request = $this->{$requestType}();

        app('request.ctx')->init();
        app('request.ctx')->resolveKeyIdIfApplicable();
        $reqCtx = app('request.ctx.v2');
        $reqCtx->passport = $passport;

        $trace = Mockery::mock('Razorpay\Trace\Logger');
        $this->app->instance("trace", $trace);
        $trace->shouldReceive('histogram')->times(1);
        //there shouldn't be any exceptions
        $trace->shouldReceive('error')->times(0);
        $trace->shouldReceive('warning')->once()->with(TraceCode::EDGE_AUTHFLOW_MISMATCH, Mockery::type('array'));

        $ba = $this->mockBasicAuth();
        $authCredsMock = Mockery::mock(KeyAuthCreds::class);
        $keyEntityMock = Mockery::mock('RZP\Models\Key\Entity');
        $ba->authCreds = $authCredsMock;
        $authCredsMock->shouldReceive('getKeyEntity')->andReturn(null);
        $keyEntityMock->shouldReceive('getMerchantId')->andReturn("testmerchantid");
        $ba->expects($this->once())->method('getMode')->willReturn("test");
        $ba->expects($this->atLeastOnce())->method('getMerchantId')->willReturn("");
        $ba->expects($this->any())->method('getPartnerMerchantId')->willReturn(null);
        $ba->expects($this->once())->method('getAuthType')->willReturn($authType);
        if ($authType === 'public')
        {
            $ba->expects($this->any())->method('isKeylessPublicAuth')->willReturn(false);
        }
        $ba->expects($this->atLeastOnce())->method('isProxyAuth')->willReturn(false);

        $trace->shouldReceive('count')->times(2);
        $trace->shouldReceive('warning')->with(TraceCode::PASSPORT_ATTRS_MISMATCH, Mockery::type('array'));

        (new PostAuthenticate)->handle(true, $request);
    }

    public function getLogsPassportMismatchMetricsCases()
    {
        return [
            // Case 1 - private auth
            ['mockPrivateRoute', 'private'],
            // Case 2 - public auth
            ['mockPublicRouteWithKeyInHeaders', 'public'],
            // Case 3 - oauth
            ['mockPrivateRouteWithOAuthBearerToken', 'private'],
            // Case 4 - partner auth
            ['mockPrivateRouteWithPartnerAuthToken', 'private']
        ];
    }

    /**
     * @dataProvider getAuthFlowMismatchesCases
     *
     * @param string                            $requestType
     * @param Passport\Passport|null            $passport
     * @param string                            $authType
     * @param string|null                       $consumerType
     * @param Passport\ConsumerClaims|null      $consumerClaims
     * @param Passport\OauthClaims|null         $oauthClaims
     * @param bool                              $expectedMismatch
     */
    public function testAuthFlowMismatches($requestType, $passport, $authType, $consumerType, $consumerClaims, $oauthClaims, $expectedMismatch)
    {
        if (! empty($passport))
        {
            $passport->consumer = $consumerClaims;
            $passport->oauth = empty($oauthClaims) ? null : $oauthClaims;
        }

        $request = $this->{$requestType}();
        app('request.ctx')->init();
        app('request.ctx')->resolveKeyIdIfApplicable();
        $reqCtx = app('request.ctx.v2');
        $reqCtx->passport = $passport;

        $trace = Mockery::mock('Razorpay\Trace\Logger');
        $this->app->instance('trace', $trace);
        $trace->shouldReceive('histogram')->times(1);
        //there shouldn't be any exceptions
        $trace->shouldReceive('error')->times(0);
        if (! empty($consumerType))
        {
            $trace->shouldReceive('count')->once()->with(Metric::PASSPORT_ATTRS_MISMATCH_TOTAL, Mockery::type('array'));
            $trace->shouldReceive('warning')->once()->with(TraceCode::PASSPORT_ATTRS_MISMATCH, Mockery::type('array'));
        }

        $ba = $this->mockBasicAuth();
        $authCredsMock = ($consumerType == 'partner') ? Mockery::mock(ClientAuthCreds::class) : Mockery::mock(KeyAuthCreds::class) ;
        $ba->authCreds = $authCredsMock;
        $authCredsMock->shouldReceive('getKeyEntity')->andReturn(null);
        $ba->expects($this->any())->method('getMode')->willReturn('live');
        $ba->expects($this->once())->method('getAuthType')->willReturn($authType);
        if ($authType === 'public')
        {
            $ba->expects($this->any())->method('isKeylessPublicAuth')->willReturn(false);
        }
        $ba->expects($this->atLeastOnce())->method('isProxyAuth')->willReturn(false);

        if ($expectedMismatch)
        {
            $trace->shouldReceive('count')->once()->with(Metric::EDGE_AUTHFLOW_MISMATCH_TOTAL, Mockery::type('array'));
            $trace->shouldReceive('warning')->once()->with(TraceCode::EDGE_AUTHFLOW_MISMATCH, Mockery::type('array'));
        }

        if (empty($passport))
        {
            $trace->shouldReceive('warning')->once()->with(TraceCode::PASSPORT_NOT_FOUND, Mockery::type('array'));
        }

        (new PostAuthenticate)->handle(true, $request);
        Mockery::close();
    }

    public function getAuthFlowMismatchesCases()
    {
        $passport = new Passport\Passport;
        $passport->identified = true;
        $passport->authenticated = true;
        $passport->mode = 'live';
        $passport->credential = new Passport\CredentialClaims;
        $passport->credential->username = 'rzp_live_10000000000000';
        $passport->credential->publicKey = 'rzp_live_10000000000000';

        $consumer = new Passport\ConsumerClaims;
        $consumer->id = '10000000000000';
        $consumer->type = 'merchant';

        $oauth = new Passport\OauthClaims;
        $oauth->appId = 'oauth_application_id';
        $oauth->clientId = 'oauth_client_id';
        $oauth->accessTokenId = 'oauth_access_token_id';
        $oauth->ownerType = 'merchant';
        $oauth->ownerId = 'oauth_owner_id';
        $oauth->userId = 'oauth_user_id';
        $oauth->env = 'test';

        // $requestType, $passport, $authType, $consumerType, $consumerClaims, $oauthClaims, $expectedMismatch
        return [
            // Case 1 - Edge passport empty
            ['mockPrivateRoute', null, 'private', null, $consumer, null, false],
            // Case 2 - not private or public or oauth.
            ['mockDirectRoute', $passport, 'direct', null, $consumer, null, false],
            // Case 3 - no mismatch
            ['mockPrivateRoute', $passport, 'private', 'merchant', $consumer, null, false],
            // Case 4 - mismatch public auth
            ['mockPublicRouteWithKeyInHeaders', $passport, 'public', 'partner', $consumer, null, true],
            // Case 5 - mismatch private partner auth
            ['mockPrivateRouteWithPartnerAuthToken', $passport, 'partner', 'partner', $consumer, null, true],
            // Case 6 - mismatch oauth
            ['mockPrivateRouteWithOAuthBearerToken', $passport, 'oauth', 'merchant', $consumer, $oauth, true],
        ];
    }

    /**
     * @dataProvider getAuthTypeAtAPICases
     *
     * @param class-string|null                 $authCredsClass
     * @param bool                              $isKeylessAuth
     * @param bool                              $isPublicAuth
     * @param bool                              $isOauth
     * @param string|null                       $accoundId
     * @param string                            $expectedValue
     */
    public function testGetAuthTypeAtAPI($authCredsClass, $isKeylessAuth, $isPublicAuth, $isOauth, $accoundId, $expectedValue)
    {
        $request = $this->mockPrivateRouteWithLiveMode();
        app('request.ctx')->init();
        app('request.ctx')->resolveKeyIdIfApplicable();
        $reqCtx = app('request.ctx.v2');
        $reqCtx->passport = new Passport\Passport;

        $trace = Mockery::mock('Razorpay\Trace\Logger');
        $this->app->instance('trace', $trace);
        $trace->shouldReceive('histogram')->times(1);
        //there shouldn't be any exceptions
        $trace->shouldReceive('error')->times(0);

        $ba = $this->mockBasicAuth();
        $ba->expects($this->any())->method('getMode')->willReturn('live');
        $ba->expects($this->once())->method('getAuthType')->willReturn('private');
        $ba->expects($this->atLeastOnce())->method('isProxyAuth')->willReturn(false);
        $ba->expects($this->any())->method('isKeylessPublicAuth')->willReturn($isKeylessAuth);
        $ba->expects($this->any())->method('isPublicAuth')->willReturn($isPublicAuth);
        $ba->expects($this->any())->method('isOAuth')->willReturn($isOauth);
        $ba->expects($this->any())->method('getAccountId')->willReturn($accoundId);

        $traceCount = empty($authCredsClass) ? 1 : 2;
        $trace->shouldReceive('count')->times($traceCount);
        $trace->shouldReceive('warning')->once()->with(TraceCode::PASSPORT_ATTRS_MISMATCH, Mockery::type('array'));

        if (! empty($authCredsClass))
        {
            $authCredsMock = Mockery::mock($authCredsClass);
            $ba->authCreds = $authCredsMock;
            $authCredsMock->shouldReceive('getKeyEntity')->andReturn(null);
        }

        $trace->shouldReceive('warning')->andReturnUsing(function ($message, $context) use ($expectedValue) {
           if ($message == TraceCode::EDGE_AUTHFLOW_MISMATCH) {
               self::assertEquals($context['api_auth_flow'], $expectedValue);
           }
        });

        (new PostAuthenticate)->handle(true, $request);
        Mockery::close();
    }

    public function getAuthTypeAtAPICases()
    {
        // $authCredsClass, $isKeylessAuth, $isPublicAuth, $isOauth, $accoundId, $expectedValue
        return [
            // Case 1 - unauthenticated unidentified request
            [null, false, false, false, null, ''],
            // Case 2 - keyless_auth
            [KeyAuthCreds::class, true, false, false, null, 'keyless_auth'],
            // Case 3 - public_merchant_auth
            [KeyAuthCreds::class, false, true, false, null, 'public_merchant_auth_without_impersonation'],
            // Case 4 - public_partner_auth
            [ClientAuthCreds::class, false, true, false, null, 'public_partner_auth_without_impersonation'],
            // Case 5 - public_partner_auth_with_impersonation
            [ClientAuthCreds::class, false, true, false, '123', 'public_partner_auth_with_impersonation'],
            // Case 6 - public_oauth_without_impersonation
            [KeyAuthCreds::class, false, true, true, null, 'public_oauth_without_impersonation'],
            // Case 7 - public_oauth_with_impersonation
            [KeyAuthCreds::class, false, true, true, '123', 'public_oauth_with_impersonation'],
            // Case 8 - merchant_auth
            [KeyAuthCreds::class, false, false, false, null, 'merchant_auth_without_impersonation'],
            // Case 9 - merchant_auth_with_impersonation
            [KeyAuthCreds::class, false, false, false, '123', 'merchant_auth_with_impersonation'],
            // Case 10 - partner_auth
            [ClientAuthCreds::class, false, false, false, null, 'partner_auth_without_impersonation'],
            // Case 11 - partner_auth_with_impersonation
            [ClientAuthCreds::class, false, false, false, '123', 'partner_auth_with_impersonation'],
            // Case 12 - oauth_without_impersonation
            [KeyAuthCreds::class, false, false, true, null, 'oauth_without_impersonation'],
            // Case 13 - oauth_with_impersonation
            [KeyAuthCreds::class, false, false, true, '123', 'oauth_with_impersonation'],
        ];
    }
}
