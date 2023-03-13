<?php

namespace Unit\Models\Merchant;

use Mockery;
use Tests\Unit\TestCase;
use RZP\Models\Merchant\AccountV2\Response;

class MerchantAccountV2Test extends TestCase
{

    protected $merchantEntityMock;

    protected $merchantDetailEntityMock;

    protected $partnerEntityMock;

    protected $splitzService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestDependencyMocks();
    }

    public function testAccountV2ResponseWhenActivationStatusIsUnderReview()
    {
        $accountResponse = new Response();

        $this->merchantEntityMock->shouldReceive('getAttribute')->with('merchantDetail')->andReturn($this->merchantDetailEntityMock);

        $this->merchantEntityMock->shouldReceive('isSuspended')->andReturn(false);

        $this->merchantEntityMock->shouldReceive('getCreatedAt')->andReturn('1678107805');

        $this->merchantEntityMock->shouldReceive('isFundsOnHold')->andReturn(true);

        $this->merchantEntityMock->shouldReceive('getBillingLabel')->andReturn('billing_name');

        $this->merchantDetailEntityMock->shouldReceive('getActivationStatus')->andReturn('under_review');

        $this->partnerEntityMock->shouldReceive('getId')->andReturn('10000000000000');

        $result = $accountResponse->getAccountResponse($this->partnerEntityMock, $this->merchantEntityMock);

        $this->assertEquals('under_review', $result['status']);
        $this->assertEquals(true, $result['hold_funds']);
        $this->assertArrayNotHasKey('activated_at', $result);
    }

    public function testAccountV2ResponseWhenActivationStatusIsActivated()
    {
        $accountResponse = new Response();

        $this->merchantEntityMock->shouldReceive('getAttribute')->with('merchantDetail')->andReturn($this->merchantDetailEntityMock);

        $this->merchantEntityMock->shouldReceive('isSuspended')->andReturn(false);

        $this->merchantEntityMock->shouldReceive('getCreatedAt')->andReturn('1678107805');

        $this->merchantEntityMock->shouldReceive('isFundsOnHold')->andReturn(false);

        $this->merchantEntityMock->shouldReceive('getBillingLabel')->andReturn('billing_name');

        $this->merchantEntityMock->shouldReceive('getActivatedAt')->andReturn('1678107805');

        $this->merchantDetailEntityMock->shouldReceive('getActivationStatus')->andReturn('activated');

        $this->partnerEntityMock->shouldReceive('getId')->andReturn('10000000000000');

        $result = $accountResponse->getAccountResponse($this->partnerEntityMock, $this->merchantEntityMock);

        $this->assertEquals('activated', $result['status']);
        $this->assertEquals(false, $result['hold_funds']);
        $this->assertArrayHasKey('activated_at', $result);
    }

    public function testAccountV2ResponseWhenActivationStatusIsNeedsClarification()
    {
        $accountResponse = new Response();

        $this->merchantEntityMock->shouldReceive('getAttribute')->with('merchantDetail')->andReturn($this->merchantDetailEntityMock);

        $this->merchantEntityMock->shouldReceive('isSuspended')->andReturn(false);

        $this->merchantEntityMock->shouldReceive('getCreatedAt')->andReturn('1678107805');

        $this->merchantEntityMock->shouldReceive('isFundsOnHold')->andReturn(false);

        $this->merchantEntityMock->shouldReceive('getBillingLabel')->andReturn('billing_name');

        $this->merchantDetailEntityMock->shouldReceive('getActivationStatus')->andReturn('needs_clarification');

        $this->partnerEntityMock->shouldReceive('getId')->andReturn('10000000000000');

        $result = $accountResponse->getAccountResponse($this->partnerEntityMock, $this->merchantEntityMock);

        $this->assertEquals('needs_clarification', $result['status']);
        $this->assertEquals(false, $result['hold_funds']);
        $this->assertArrayNotHasKey('activated_at', $result);
    }

    public function testAccountV2ResponseWhenActivationStatusIsActivatedKycPending()
    {
        $accountResponse = new Response();

        $this->merchantEntityMock->shouldReceive('getAttribute')->with('merchantDetail')->andReturn($this->merchantDetailEntityMock);

        $this->merchantEntityMock->shouldReceive('isSuspended')->andReturn(false);

        $this->merchantEntityMock->shouldReceive('getCreatedAt')->andReturn('1678107805');

        $this->merchantEntityMock->shouldReceive('isFundsOnHold')->andReturn(false);

        $this->merchantEntityMock->shouldReceive('getBillingLabel')->andReturn('billing_name');

        $this->merchantEntityMock->shouldReceive('getActivatedAt')->andReturn('1678107805');

        $this->merchantDetailEntityMock->shouldReceive('getActivationStatus')->andReturn('activated_kyc_pending');

        $this->partnerEntityMock->shouldReceive('getId')->andReturn('10000000000000');

        $result = $accountResponse->getAccountResponse($this->partnerEntityMock, $this->merchantEntityMock);

        $this->assertEquals('activated_kyc_pending', $result['status']);
        $this->assertEquals(false, $result['hold_funds']);
        $this->assertArrayNotHasKey('activated_at', $result);
    }

    public function testAccountV2ResponseWhenActivationStatusIsSuspended()
    {
        $accountResponse = new Response();

        $this->merchantEntityMock->shouldReceive('getAttribute')->with('merchantDetail')->andReturn($this->merchantDetailEntityMock);

        $this->merchantEntityMock->shouldReceive('isSuspended')->andReturn(true);

        $this->merchantEntityMock->shouldReceive('getCreatedAt')->andReturn('1678107805');

        $this->merchantEntityMock->shouldReceive('isFundsOnHold')->andReturn(true);

        $this->merchantEntityMock->shouldReceive('getBillingLabel')->andReturn('billing_name');

        $this->merchantEntityMock->shouldReceive('getSuspendedAt')->andReturn('1678107805');

        $this->merchantDetailEntityMock->shouldReceive('getActivationStatus')->andReturn('activated_kyc_pending');

        $this->partnerEntityMock->shouldReceive('getId')->andReturn('10000000000000');

        $result = $accountResponse->getAccountResponse($this->partnerEntityMock, $this->merchantEntityMock);

        $this->assertEquals('suspended', $result['status']);
        $this->assertEquals(true, $result['hold_funds']);
        $this->assertArrayHasKey('suspended_at', $result);
    }

    private function createTestDependencyMocks()
    {
        $this->merchantEntityMock = Mockery::mock('RZP\Models\Merchant\Entity')->makePartial()->shouldAllowMockingProtectedMethods();

        $this->merchantDetailEntityMock = Mockery::mock('RZP\Models\Merchant\Detail\Entity')->makePartial();

        $this->partnerEntityMock = Mockery::mock('RZP\Models\Merchant\Entity');

        $this->merchantCore = Mockery::mock('RZP\Models\Merchant\Core');

        $this->mockSplitzExperiment();
    }

    private function mockSplitzExperiment()
    {
        $this->splitzService = Mockery::mock('RZP\Services\SplitzService');

        $this->app->instance('splitzService', $this->splitzService);

        $output["response"]["variant"]["name"] = "enable";

        $this->splitzService
            ->shouldReceive('evaluateRequest')
            ->andReturn($output);
    }
}
