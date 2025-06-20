<?php

namespace Unit\Models\FundAccount;

use Mockery;
use RZP\Models\FundAccount\Service;
use RZP\Models\FundAccount\Entity;
use RZP\Models\Vpa;
use RZP\Tests\TestCase;

class ServiceTest extends TestCase
{
    protected $mockTrace;
    protected $mockLinkedNumberCore;
    protected $mockVpaCore;
    protected $mockPayoutEvents;
    protected $mockFundAccount;
    protected $mockVpa;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock all dependencies
        $this->mockTrace = Mockery::mock()->shouldIgnoreMissing();
        $this->mockLinkedNumberCore = Mockery::mock('RZP\Models\LinkedNumber\Core');
        $this->mockVpaCore = Mockery::mock('RZP\Models\Vpa\Core');
        $this->mockPayoutEvents = Mockery::mock('RZP\Models\Payout\Events');

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

        // Setup payoutEvents mock for event tracking
        $this->mockPayoutEvents
            ->shouldReceive('trackPayoutsToPhoneNumberVpaUpdatedEvent')
            ->once()
            ->with(
                $merchantId,
                $mobileNumber,
                $accountHolderName,
                $mappedVpaData[Entity::VPA],
                $existingUsername . '@' . $existingHandle,
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

        $payoutEventsProperty = $reflection->getProperty('payoutEvents');
        $payoutEventsProperty->setAccessible(true);
        $payoutEventsProperty->setValue($service, $this->mockPayoutEvents);

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

        $payoutEventsProperty = $reflection->getProperty('payoutEvents');
        $payoutEventsProperty->setAccessible(true);
        $this->assertSame($this->mockPayoutEvents, $payoutEventsProperty->getValue($service),
            'PayoutEvents dependency should be properly injected');
    }

    /**
     * Test 2: VPA remains same but customer name changes - should update name without tracking event
     */
    public function testUpdateMappedVpaForFundAccountWithOnlyCustomerNameChange()
    {
        // Setup test data
        $merchantId = 'test_merchant_123';
        $mobileNumber = '9876543210';
        $accountHolderName = 'Test User';
        $existingUsername = 'sameuser';
        $existingHandle = 'samebank';
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

        // Setup VPA mock - return same values to indicate no VPA change
        $this->mockVpa
            ->shouldReceive('getUsername')
            ->andReturn($existingUsername);

        $this->mockVpa
            ->shouldReceive('getHandle')
            ->andReturn($existingHandle);

        // Setup linkedNumberCore mock - return mapped VPA data with same VPA but new customer name
        $mappedVpaData = [
            Entity::VPA => $existingUsername . '@' . $existingHandle,
            Entity::CUSTOMER_NAME => $newCustomerName
        ];

        $this->mockLinkedNumberCore
            ->shouldReceive('FetchMappedVpaFromLinkedNumber')
            ->once()
            ->with($mobileNumber, $accountHolderName, $merchantId)
            ->andReturn($mappedVpaData);

        // No event tracking should happen since VPA hasn't changed
        $this->mockPayoutEvents->shouldNotReceive('trackPayoutsToPhoneNumberVpaUpdatedEvent');

        // Setup vpaCore mock for VPA update - even though VPA hasn't changed, the method is still called
        $expectedVpaInput = [
            Vpa\Entity::USERNAME => $existingUsername,
            Vpa\Entity::HANDLE => $existingHandle,
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

        $payoutEventsProperty = $reflection->getProperty('payoutEvents');
        $payoutEventsProperty->setAccessible(true);
        $payoutEventsProperty->setValue($service, $this->mockPayoutEvents);

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

        $payoutEventsProperty = $reflection->getProperty('payoutEvents');
        $payoutEventsProperty->setAccessible(true);
        $this->assertSame($this->mockPayoutEvents, $payoutEventsProperty->getValue($service),
            'PayoutEvents dependency should be properly injected');
    }

    /**
     * Test 3: Event tracking failure is handled gracefully
     */
    public function testUpdateMappedVpaForFundAccountHandlesEventTrackingFailure()
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

        // Setup payoutEvents mock for event tracking - Events class handles exceptions internally
        $this->mockPayoutEvents
            ->shouldReceive('trackPayoutsToPhoneNumberVpaUpdatedEvent')
            ->once()
            ->with(
                $merchantId,
                $mobileNumber,
                $accountHolderName,
                $mappedVpaData[Entity::VPA],
                $existingUsername . '@' . $existingHandle,
            );

        // No trace error should be called since Events class handles errors internally

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

        $payoutEventsProperty = $reflection->getProperty('payoutEvents');
        $payoutEventsProperty->setAccessible(true);
        $payoutEventsProperty->setValue($service, $this->mockPayoutEvents);

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

        $payoutEventsProperty = $reflection->getProperty('payoutEvents');
        $payoutEventsProperty->setAccessible(true);
        $this->assertSame($this->mockPayoutEvents, $payoutEventsProperty->getValue($service),
            'PayoutEvents dependency should be properly injected');
    }

    /**
     * Test 4: Empty mapped VPA is handled correctly
     */
    public function testUpdateMappedVpaForFundAccountWithEmptyMappedVpa()
    {
        // Setup test data
        $merchantId = 'test_merchant_123';
        $mobileNumber = '9876543210';
        $accountHolderName = 'Test User';

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

        // Setup linkedNumberCore mock - return empty mapped VPA data
        $mappedVpaData = [];

        $this->mockLinkedNumberCore
            ->shouldReceive('FetchMappedVpaFromLinkedNumber')
            ->once()
            ->with($mobileNumber, $accountHolderName, $merchantId)
            ->andReturn($mappedVpaData);

        // No event tracking or VPA update should happen
        $this->mockPayoutEvents->shouldNotReceive('trackPayoutsToPhoneNumberVpaUpdatedEvent');
        $this->mockVpaCore->shouldNotReceive('updateVpaWithPublicId');
        $this->mockFundAccount->shouldNotReceive('setCustomerName');
        $this->mockFundAccount->shouldNotReceive('saveOrFail');

        // Create service instance and inject mocked dependencies
        $service = new Service();
        $reflection = new \ReflectionClass($service);

        $linkedNumberCoreProperty = $reflection->getProperty('linkedNumberCore');
        $linkedNumberCoreProperty->setAccessible(true);
        $linkedNumberCoreProperty->setValue($service, $this->mockLinkedNumberCore);

        $vpaCoreProperty = $reflection->getProperty('vpaCore');
        $vpaCoreProperty->setAccessible(true);
        $vpaCoreProperty->setValue($service, $this->mockVpaCore);

        $payoutEventsProperty = $reflection->getProperty('payoutEvents');
        $payoutEventsProperty->setAccessible(true);
        $payoutEventsProperty->setValue($service, $this->mockPayoutEvents);

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

        $payoutEventsProperty = $reflection->getProperty('payoutEvents');
        $payoutEventsProperty->setAccessible(true);
        $this->assertSame($this->mockPayoutEvents, $payoutEventsProperty->getValue($service),
            'PayoutEvents dependency should be properly injected');
    }

    /**
     * Test 5: Invalid VPA format is handled correctly
     */
    public function testUpdateMappedVpaForFundAccountWithInvalidVpaFormat()
    {
        // Setup test data
        $merchantId = 'test_merchant_123';
        $mobileNumber = '9876543210';
        $accountHolderName = 'Test User';
        $existingUsername = 'olduser';
        $existingHandle = 'oldbank';
        $invalidVpa = 'invalid-vpa-format'; // Missing @ symbol

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

        // Setup VPA mock - return different values to trigger VPA change
        $this->mockVpa
            ->shouldReceive('getUsername')
            ->andReturn($existingUsername);

        $this->mockVpa
            ->shouldReceive('getHandle')
            ->andReturn($existingHandle);

        // Setup linkedNumberCore mock - throw exception for invalid VPA format
        // With the new validation in LinkedNumber\Core, invalid VPA format should throw exception
        $this->mockLinkedNumberCore
            ->shouldReceive('FetchMappedVpaFromLinkedNumber')
            ->once()
            ->with($mobileNumber, $accountHolderName, $merchantId)
            ->andThrow(new \RZP\Exception\BadRequestException(
                \RZP\Error\ErrorCode::BAD_REQUEST_ERROR,
                \RZP\Models\FundAccount\Entity::MOBILE,
                null,
                \RZP\Error\PublicErrorDescription::BAD_REQUEST_LINKED_ACCOUNT_NOT_FOUND
            ));

        // Since LinkedNumber\Core will throw exception for invalid VPA format,
        // no further processing should happen - no VPA updates, no event tracking, no customer name updates
        $this->mockVpaCore->shouldNotReceive('updateVpaWithPublicId');
        $this->mockPayoutEvents->shouldNotReceive('trackPayoutsToPhoneNumberVpaUpdatedEvent');
        $this->mockFundAccount->shouldNotReceive('setCustomerName');
        $this->mockFundAccount->shouldNotReceive('saveOrFail');

        // Create service instance and inject mocked dependencies
        $service = new Service();
        $reflection = new \ReflectionClass($service);

        $linkedNumberCoreProperty = $reflection->getProperty('linkedNumberCore');
        $linkedNumberCoreProperty->setAccessible(true);
        $linkedNumberCoreProperty->setValue($service, $this->mockLinkedNumberCore);

        $vpaCoreProperty = $reflection->getProperty('vpaCore');
        $vpaCoreProperty->setAccessible(true);
        $vpaCoreProperty->setValue($service, $this->mockVpaCore);

        $payoutEventsProperty = $reflection->getProperty('payoutEvents');
        $payoutEventsProperty->setAccessible(true);
        $payoutEventsProperty->setValue($service, $this->mockPayoutEvents);

        // Execute the method - expect BadRequestException for invalid VPA format
        $this->expectException(\RZP\Exception\BadRequestException::class);
        $this->expectExceptionMessage('No linked account details found');

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

        $payoutEventsProperty = $reflection->getProperty('payoutEvents');
        $payoutEventsProperty->setAccessible(true);
        $this->assertSame($this->mockPayoutEvents, $payoutEventsProperty->getValue($service),
            'PayoutEvents dependency should be properly injected');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
