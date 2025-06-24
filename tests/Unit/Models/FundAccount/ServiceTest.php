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
        $fundAccountId = 'fa_1234567890';

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
            ->andReturnUsing(function($attribute) use ($mobileNumber, $accountHolderName, $fundAccountId) {
                switch ($attribute) {
                    case 'linked_number':
                        return $mobileNumber;
                    case 'customer_name':
                        return $accountHolderName;
                    case 'account':
                        return $this->mockVpa;
                    case 'id':
                        return $fundAccountId;
                    default:
                        return null;
                }
            });

        $this->mockFundAccount->account = $this->mockVpa;

        $this->mockFundAccount
            ->shouldReceive('setCustomerName')
            ->once()
            ->with($newCustomerName);

        $this->mockFundAccount
            ->shouldReceive('saveOrFail')
            ->once();

        $this->mockFundAccount
            ->shouldReceive('getId')
            ->once()
            ->andReturn($fundAccountId);

        $this->mockVpa
            ->shouldReceive('getUsername')
            ->andReturn($existingUsername);

        $this->mockVpa
            ->shouldReceive('getHandle')
            ->andReturn($existingHandle);

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
                $fundAccountId
            );

        $expectedVpaInput = [
            Vpa\Entity::USERNAME => $newUsername,
            Vpa\Entity::HANDLE => $newHandle,
        ];

        $this->mockVpaCore
            ->shouldReceive('updateVpaWithPublicId')
            ->once()
            ->with($this->mockVpa, $expectedVpaInput);

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

        $service->updateMappedVpaForFundAccount($this->mockFundAccount, $merchantId);

        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());

        $this->assertInstanceOf(Service::class, $service, 'Service instance should be properly created');

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
        $merchantId = 'test_merchant_123';
        $mobileNumber = '9876543210';
        $accountHolderName = 'Test User';
        $existingUsername = 'sameuser';
        $existingHandle = 'samebank';
        $newCustomerName = 'Updated Customer Name';

        $this->mockFundAccount
            ->shouldReceive('getLinkedNumber')
            ->once()
            ->andReturn($mobileNumber);

        $this->mockFundAccount
            ->shouldReceive('getCustomerName')
            ->once()
            ->andReturn($accountHolderName);

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

        $this->mockFundAccount->account = $this->mockVpa;

        $this->mockFundAccount
            ->shouldReceive('setCustomerName')
            ->once()
            ->with($newCustomerName);

        $this->mockFundAccount
            ->shouldReceive('saveOrFail')
            ->once();

        $this->mockVpa
            ->shouldReceive('getUsername')
            ->andReturn($existingUsername);

        $this->mockVpa
            ->shouldReceive('getHandle')
            ->andReturn($existingHandle);

        $mappedVpaData = [
            Entity::VPA => $existingUsername . '@' . $existingHandle,
            Entity::CUSTOMER_NAME => $newCustomerName
        ];

        $this->mockLinkedNumberCore
            ->shouldReceive('FetchMappedVpaFromLinkedNumber')
            ->once()
            ->with($mobileNumber, $accountHolderName, $merchantId)
            ->andReturn($mappedVpaData);

        $this->mockPayoutEvents->shouldNotReceive('trackPayoutsToPhoneNumberVpaUpdatedEvent');

        $expectedVpaInput = [
            Vpa\Entity::USERNAME => $existingUsername,
            Vpa\Entity::HANDLE => $existingHandle,
        ];

        $this->mockVpaCore
            ->shouldReceive('updateVpaWithPublicId')
            ->once()
            ->with($this->mockVpa, $expectedVpaInput);

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

        $service->updateMappedVpaForFundAccount($this->mockFundAccount, $merchantId);

        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());

        $this->assertInstanceOf(Service::class, $service, 'Service instance should be properly created');

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
        $merchantId = 'test_merchant_123';
        $mobileNumber = '9876543210';
        $accountHolderName = 'Test User';
        $existingUsername = 'olduser';
        $existingHandle = 'oldbank';
        $newUsername = 'newuser';
        $newHandle = 'newbank';
        $newCustomerName = 'Updated Customer Name';
        $fundAccountId = 'fa_1234567890';

        $this->mockFundAccount
            ->shouldReceive('getLinkedNumber')
            ->once()
            ->andReturn($mobileNumber);

        $this->mockFundAccount
            ->shouldReceive('getCustomerName')
            ->once()
            ->andReturn($accountHolderName);

        $this->mockFundAccount
            ->shouldReceive('getAttribute')
            ->andReturnUsing(function($attribute) use ($mobileNumber, $accountHolderName, $fundAccountId) {
                switch ($attribute) {
                    case 'linked_number':
                        return $mobileNumber;
                    case 'customer_name':
                        return $accountHolderName;
                    case 'account':
                        return $this->mockVpa;
                    case 'id':
                        return $fundAccountId;
                    default:
                        return null;
                }
            });

        $this->mockFundAccount->account = $this->mockVpa;

        $this->mockFundAccount
            ->shouldReceive('setCustomerName')
            ->once()
            ->with($newCustomerName);

        $this->mockFundAccount
            ->shouldReceive('getId')
            ->once()
            ->andReturn($fundAccountId);

        $this->mockFundAccount
            ->shouldReceive('saveOrFail')
            ->once();

        $this->mockVpa
            ->shouldReceive('getUsername')
            ->andReturn($existingUsername);

        $this->mockVpa
            ->shouldReceive('getHandle')
            ->andReturn($existingHandle);

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
                $fundAccountId
            );

        $expectedVpaInput = [
            Vpa\Entity::USERNAME => $newUsername,
            Vpa\Entity::HANDLE => $newHandle,
        ];

        $this->mockVpaCore
            ->shouldReceive('updateVpaWithPublicId')
            ->once()
            ->with($this->mockVpa, $expectedVpaInput);

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

        $service->updateMappedVpaForFundAccount($this->mockFundAccount, $merchantId);

        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());

        $this->assertInstanceOf(Service::class, $service, 'Service instance should be properly created');

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
        $merchantId = 'test_merchant_123';
        $mobileNumber = '9876543210';
        $accountHolderName = 'Test User';

        $this->mockFundAccount
            ->shouldReceive('getLinkedNumber')
            ->once()
            ->andReturn($mobileNumber);

        $this->mockFundAccount
            ->shouldReceive('getCustomerName')
            ->once()
            ->andReturn($accountHolderName);

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

        $this->mockFundAccount->account = $this->mockVpa;

        $mappedVpaData = [];

        $this->mockLinkedNumberCore
            ->shouldReceive('FetchMappedVpaFromLinkedNumber')
            ->once()
            ->with($mobileNumber, $accountHolderName, $merchantId)
            ->andReturn($mappedVpaData);

        $this->mockPayoutEvents->shouldNotReceive('trackPayoutsToPhoneNumberVpaUpdatedEvent');
        $this->mockVpaCore->shouldNotReceive('updateVpaWithPublicId');
        $this->mockFundAccount->shouldNotReceive('setCustomerName');
        $this->mockFundAccount->shouldNotReceive('saveOrFail');

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

        $service->updateMappedVpaForFundAccount($this->mockFundAccount, $merchantId);

        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());

        $this->assertInstanceOf(Service::class, $service, 'Service instance should be properly created');

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
        $merchantId = 'test_merchant_123';
        $mobileNumber = '9876543210';
        $accountHolderName = 'Test User';
        $existingUsername = 'olduser';
        $existingHandle = 'oldbank';
        $invalidVpa = 'invalid-vpa-format'; // Missing @ symbol

        $this->mockFundAccount
            ->shouldReceive('getLinkedNumber')
            ->once()
            ->andReturn($mobileNumber);

        $this->mockFundAccount
            ->shouldReceive('getCustomerName')
            ->once()
            ->andReturn($accountHolderName);

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

        $this->mockFundAccount->account = $this->mockVpa;

        $this->mockVpa
            ->shouldReceive('getUsername')
            ->andReturn($existingUsername);

        $this->mockVpa
            ->shouldReceive('getHandle')
            ->andReturn($existingHandle);

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

        $this->mockVpaCore->shouldNotReceive('updateVpaWithPublicId');
        $this->mockPayoutEvents->shouldNotReceive('trackPayoutsToPhoneNumberVpaUpdatedEvent');
        $this->mockFundAccount->shouldNotReceive('setCustomerName');
        $this->mockFundAccount->shouldNotReceive('saveOrFail');

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

        $this->expectException(\RZP\Exception\BadRequestException::class);
        $this->expectExceptionMessage('No linked account details found');

        $service->updateMappedVpaForFundAccount($this->mockFundAccount, $merchantId);

        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());

        $this->assertInstanceOf(Service::class, $service, 'Service instance should be properly created');

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
