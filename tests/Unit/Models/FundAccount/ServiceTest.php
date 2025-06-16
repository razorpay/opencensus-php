<?php

namespace Unit\Models\FundAccount;

use Mockery;
use RZP\Models\FundAccount\Service;
use RZP\Models\FundAccount\Entity;
use RZP\Models\Vpa;
use RZP\Models\Base;
use RZP\Models\LinkedNumber;
use RZP\Models\Payout;
use RZP\Tests\TestCase;
use RZP\Trace\TraceCode;

class ServiceTest extends TestCase
{
    protected $mockTrace;
    protected $mockLinkedNumberCore;
    protected $mockVpaCore;
    protected $mockPayoutCore;
    protected $mockFundAccount;
    protected $mockVpa;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock all dependencies
        $this->mockTrace = Mockery::mock()->shouldIgnoreMissing();
        $this->mockLinkedNumberCore = Mockery::mock('RZP\Models\LinkedNumber\Core');
        $this->mockVpaCore = Mockery::mock('RZP\Models\Vpa\Core');
        $this->mockPayoutCore = Mockery::mock('RZP\Models\Payout\Core');
        
        // Mock entities with shouldIgnoreMissing to handle Laravel methods
        $this->mockFundAccount = Mockery::mock('RZP\Models\FundAccount\Entity')->shouldIgnoreMissing();
        $this->mockVpa = Mockery::mock('RZP\Models\Vpa\Entity')->shouldIgnoreMissing();

        // Inject mocks into app container
        $this->app->instance('trace', $this->mockTrace);
    }

    /**
     * Test 1: VPA changes and event tracking succeeds
     */
    public function testUpdateMappedVpaForFundAccountWithVpaChangeTracksEventSuccessfully()
    {
        // Setup test data
        $merchantId = 'test_merchant_123';
        $mobileNumber = '9876543210';
        $accountHolderName = 'Test User';
        $existingUsername = 'olduser';
        $existingHandle = 'oldbank';
        $newUsername = 'newuser';
        $newHandle = 'newbank';
        $newCustomerName = 'Updated Customer Name';

        // Setup fund account mock - directly mock the methods
        $this->mockFundAccount
            ->shouldReceive('getLinkedNumber')
            ->once()
            ->andReturn($mobileNumber);

        $this->mockFundAccount
            ->shouldReceive('getCustomerName')
            ->once()
            ->andReturn($accountHolderName);

        // Mock getAttribute comprehensively for all possible Laravel attribute access
        $this->mockFundAccount
            ->shouldReceive('getAttribute')
            ->andReturnUsing(function($attribute) use ($mobileNumber, $accountHolderName) {
                switch ($attribute) {
                    case 'linked_number':
                        return $mobileNumber;
                    case 'customer_name':
                        return $accountHolderName;
                    case 'account':
                        return $this->mockVpa;
                    default:
                        return null;
                }
            });

        // Also handle direct property access
        $this->mockFundAccount->account = $this->mockVpa;

        $this->mockFundAccount
            ->shouldReceive('setCustomerName')
            ->once()
            ->with($newCustomerName);

        $this->mockFundAccount
            ->shouldReceive('saveOrFail')
            ->once();

        // Setup VPA mock - return different values to trigger VPA change
        $this->mockVpa
            ->shouldReceive('getUsername')
            ->andReturn($existingUsername);

        $this->mockVpa
            ->shouldReceive('getHandle')
            ->andReturn($existingHandle);

        // Setup linkedNumberCore mock - return mapped VPA data
        $mappedVpaData = [
            Entity::VPA => $newUsername . '@' . $newHandle,
            Entity::CUSTOMER_NAME => $newCustomerName
        ];

        $this->mockLinkedNumberCore
            ->shouldReceive('FetchMappedVpaFromLinkedNumber')
            ->once()
            ->with($mobileNumber, $accountHolderName, $merchantId)
            ->andReturn($mappedVpaData);

        // Setup payoutCore mocks for sanitization
        $sanitizedExistingData = [Entity::VPA => 'xxxxx@oldbank'];
        $sanitizedUpdatedData = [
            Entity::MOBILE => 'xxxxx43210',
            Entity::VPA => 'xxxxx@newbank'
        ];

        $this->mockPayoutCore
            ->shouldReceive('sanitizeDataForTracking')
            ->once()
            ->with([Entity::VPA => $existingUsername . '@' . $existingHandle])
            ->andReturn($sanitizedExistingData);

        $this->mockPayoutCore
            ->shouldReceive('sanitizeDataForTracking')
            ->once()
            ->with([
                Entity::MOBILE => $mobileNumber,
                Entity::VPA => $mappedVpaData[Entity::VPA]
            ])
            ->andReturn($sanitizedUpdatedData);

        // Setup payoutCore mock for event tracking
        $this->mockPayoutCore
            ->shouldReceive('trackPhoneNumberPayoutEvents')
            ->once()
            ->with(
                Service::PAYOUTS_TO_PHONE_NUMBER_VPA_UPDATED,
                [
                    Base\PublicEntity::MERCHANT_ID => $merchantId,
                    Entity::MOBILE => $sanitizedUpdatedData[Entity::MOBILE],
                    Entity::CUSTOMER_NAME => $accountHolderName,
                    'stored_vpa' => $sanitizedExistingData[Entity::VPA],
                    'updated_vpa' => $sanitizedUpdatedData[Entity::VPA],
                    'event_name' => Service::PAYOUTS_TO_PHONE_NUMBER_VPA_UPDATED
                ]
            );

        // Setup vpaCore mock for VPA update
        $expectedVpaInput = [
            Vpa\Entity::USERNAME => $newUsername,
            Vpa\Entity::HANDLE => $newHandle,
        ];

        $this->mockVpaCore
            ->shouldReceive('updateVpaWithPublicId')
            ->once()
            ->with($this->mockVpa, $expectedVpaInput);

        // Create service instance and inject mocked dependencies
        $service = new Service();
        $reflection = new \ReflectionClass($service);
        
        $linkedNumberCoreProperty = $reflection->getProperty('linkedNumberCore');
        $linkedNumberCoreProperty->setAccessible(true);
        $linkedNumberCoreProperty->setValue($service, $this->mockLinkedNumberCore);

        $vpaCoreProperty = $reflection->getProperty('vpaCore');
        $vpaCoreProperty->setAccessible(true);
        $vpaCoreProperty->setValue($service, $this->mockVpaCore);

        $payoutCoreProperty = $reflection->getProperty('payoutCore');
        $payoutCoreProperty->setAccessible(true);
        $payoutCoreProperty->setValue($service, $this->mockPayoutCore);

        // Execute the method
        $service->updateMappedVpaForFundAccount($this->mockFundAccount, $merchantId);

        // Verify all mock expectations were satisfied
        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());
        
        // Additional behavioral assertions
        $this->assertInstanceOf(Service::class, $service, 'Service instance should be properly created');
        
        // Verify the dependency injection worked correctly
        $reflection = new \ReflectionClass($service);
        $linkedNumberCoreProperty = $reflection->getProperty('linkedNumberCore');
        $linkedNumberCoreProperty->setAccessible(true);
        $this->assertSame($this->mockLinkedNumberCore, $linkedNumberCoreProperty->getValue($service), 
            'LinkedNumberCore dependency should be properly injected');
            
        $vpaCoreProperty = $reflection->getProperty('vpaCore');
        $vpaCoreProperty->setAccessible(true);
        $this->assertSame($this->mockVpaCore, $vpaCoreProperty->getValue($service),
            'VpaCore dependency should be properly injected');
            
        $payoutCoreProperty = $reflection->getProperty('payoutCore');
        $payoutCoreProperty->setAccessible(true);
        $this->assertSame($this->mockPayoutCore, $payoutCoreProperty->getValue($service),
            'PayoutCore dependency should be properly injected');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
} 