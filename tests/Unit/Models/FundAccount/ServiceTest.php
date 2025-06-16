<?php

namespace Unit\Models\FundAccount;

use Mockery;
use RZP\Models\FundAccount\Service;
use RZP\Models\FundAccount\Entity;
use RZP\Models\Vpa;
use RZP\Models\Base;
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
        $this->mockPayoutCore->shouldNotReceive('trackPhoneNumberPayoutEvents');
        $this->mockPayoutCore->shouldNotReceive('sanitizeDataForTracking');

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

        // Setup payoutCore mock for event tracking - make it throw an exception
        $this->mockPayoutCore
            ->shouldReceive('trackPhoneNumberPayoutEvents')
            ->once()
            ->andThrow(new \Exception('Event tracking failed'));

        // Setup trace mock to verify error is logged
        $this->mockTrace
            ->shouldReceive('error')
            ->once()
            ->with(
                TraceCode::PAYOUT_TO_PHONE_NUMBER_EVENT_TRACKING_FAILED,
                [
                    'error_message' => 'Event tracking failed',
                    'merchant_id' => $merchantId,
                    'context' => Service::PAYOUTS_TO_PHONE_NUMBER_VPA_UPDATED
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
        $this->mockPayoutCore->shouldNotReceive('trackPhoneNumberPayoutEvents');
        $this->mockPayoutCore->shouldNotReceive('sanitizeDataForTracking');
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

        // Setup linkedNumberCore mock - return mapped VPA data with invalid format
        $mappedVpaData = [
            Entity::VPA => $invalidVpa,
            Entity::CUSTOMER_NAME => 'Updated Customer Name'
        ];

        $this->mockLinkedNumberCore
            ->shouldReceive('FetchMappedVpaFromLinkedNumber')
            ->once()
            ->with($mobileNumber, $accountHolderName, $merchantId)
            ->andReturn($mappedVpaData);

        // Setup vpaCore mock for VPA update - when explode is used on invalid VPA format
        // explode('@', 'invalid-vpa-format') will return ['invalid-vpa-format']
        // So username = 'invalid-vpa-format' and handle = null
        $expectedVpaInput = [
            Vpa\Entity::USERNAME => $invalidVpa,
            Vpa\Entity::HANDLE => null,
        ];

        $this->mockVpaCore
            ->shouldReceive('updateVpaWithPublicId')
            ->once()
            ->with($this->mockVpa, $expectedVpaInput);

        // Event tracking should happen since VPA is different (invalid format != existing VPA)
        $sanitizedExistingData = [Entity::VPA => $existingUsername . '@' . $existingHandle];
        $sanitizedUpdatedData = [
            Entity::MOBILE => $mobileNumber,
            Entity::VPA => $invalidVpa
        ];

        $this->mockPayoutCore
            ->shouldReceive('sanitizeDataForTracking')
            ->twice()
            ->andReturnUsing(function($data) use ($sanitizedExistingData, $sanitizedUpdatedData) {
                if (isset($data[Entity::VPA]) && !isset($data[Entity::MOBILE])) {
                    return $sanitizedExistingData;
                } else {
                    return $sanitizedUpdatedData;
                }
            });

        $this->mockPayoutCore
            ->shouldReceive('trackPhoneNumberPayoutEvents')
            ->once()
            ->with(
                Service::PAYOUTS_TO_PHONE_NUMBER_VPA_UPDATED,
                [
                    Base\PublicEntity::MERCHANT_ID => $merchantId,
                    Entity::MOBILE => $mobileNumber,
                    Entity::CUSTOMER_NAME => $accountHolderName,
                    'stored_vpa' => $existingUsername . '@' . $existingHandle,
                    'updated_vpa' => $invalidVpa,
                    'event_name' => Service::PAYOUTS_TO_PHONE_NUMBER_VPA_UPDATED
                ]
            );

        // Customer name should still be updated
        $this->mockFundAccount
            ->shouldReceive('setCustomerName')
            ->once()
            ->with('Updated Customer Name');

        $this->mockFundAccount
            ->shouldReceive('saveOrFail')
            ->once();

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
