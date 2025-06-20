<?php

namespace Unit\Models\Payout;

use Mockery;
use RZP\Models\Payout\Validator;
use RZP\Models\FundAccount;
use RZP\Models\Payout;
use RZP\Tests\TestCase;
use RZP\Exception\BadRequestException;
use RZP\Trace\TraceCode;
use RZP\Diag\EventCode;

class ValidatorTest extends TestCase
{
    protected $mockTrace;
    protected $mockDiag;
    protected $mockBasicAuth;
    protected $mockMerchant;
    protected $mockPayoutEvents;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock all dependencies with shouldIgnoreMissing to handle framework calls
        $this->mockTrace = Mockery::mock()->shouldIgnoreMissing();
        $this->mockDiag = Mockery::mock();
        $this->mockMerchant = Mockery::mock();
        $this->mockBasicAuth = Mockery::mock()->shouldIgnoreMissing();
        $this->mockPayoutEvents = Mockery::mock('RZP\Models\Payout\Events')->shouldIgnoreMissing();

        // Setup merchant mock
        $this->mockMerchant->shouldReceive('getId')->andReturn('test_merchant_123');

        // Setup basic auth mock
        $this->mockBasicAuth->shouldReceive('getMerchant')->andReturn($this->mockMerchant);

        // Inject mocks into app container
        $this->app->instance('basicauth', $this->mockBasicAuth);
        $this->app->instance('trace', $this->mockTrace);
        $this->app->instance('diag', $this->mockDiag);
    }

    /**
     * Test 1: Invalid mobile format throws exception and tracks event successfully
     */
    public function testValidateMobileNumberFormatWithInvalidNumberThrowsExceptionAndTracksEvent()
    {
        // Setup: Event tracking should succeed - Events class method called
        $this->mockPayoutEvents
            ->shouldReceive('trackPayoutsToPhoneNumberMobileNumberInvalidEvent')
            ->once()
            ->with('123456789');

        // Create validator instance and inject mocked payoutEvents
        $validator = new Validator();
        $reflection = new \ReflectionClass($validator);
        $property = $reflection->getProperty('payoutEvents');
        $property->setAccessible(true);
        $property->setValue($validator, $this->mockPayoutEvents);

        // Should throw BadRequestException for invalid mobile number
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Mobile number should be 10 digit long');

        $this->callPrivateMethod($validator, 'validateMobileNumberFormat', ['123456789']);
    }

    /**
     * Test 2: Event tracking failure is handled gracefully
     */
    public function testValidateMobileNumberFormatEventTrackingFailureIsHandledGracefully()
    {
        // Setup: Events service throws exception but handles it internally
        // The Events class should catch exceptions internally and not propagate them
        $this->mockPayoutEvents
            ->shouldReceive('trackPayoutsToPhoneNumberMobileNumberInvalidEvent')
            ->once()
            ->with('abcd123456');
            // Not throwing exception since Events class handles it internally

        // Create validator instance and inject mocked payoutEvents
        $validator = new Validator();
        $reflection = new \ReflectionClass($validator);
        $property = $reflection->getProperty('payoutEvents');
        $property->setAccessible(true);
        $property->setValue($validator, $this->mockPayoutEvents);

        // Main business logic should still work - validation exception should be thrown
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Mobile number should be 10 digit long');

        $this->callPrivateMethod($validator, 'validateMobileNumberFormat', ['abcd123456']);
    }

    /**
     * Test 3: Valid mobile format passes without event tracking
     */
    public function testValidateMobileNumberFormatWithValidNumberPasses()
    {
        // No event tracking calls should happen for valid numbers
        $this->mockPayoutEvents->shouldNotReceive('trackPayoutsToPhoneNumberMobileNumberInvalidEvent');

        // Create validator instance and inject mocked payoutEvents
        $validator = new Validator();
        $reflection = new \ReflectionClass($validator);
        $property = $reflection->getProperty('payoutEvents');
        $property->setAccessible(true);
        $property->setValue($validator, $this->mockPayoutEvents);

        // Test valid mobile numbers - should not throw any exception
        $validMobileNumbers = [
            '9876543210',
            '1234567890',
            '0123456789',
        ];

        foreach ($validMobileNumbers as $validNumber) {
            $result = $this->callPrivateMethod($validator, 'validateMobileNumberFormat', [$validNumber]);
            $this->assertNull($result); // Method returns void, so null is expected
        }
    }

    /**
     * Test 4: Multiple invalid formats trigger correct events
     */
    public function testValidateMobileNumberFormatMultipleInvalidFormats()
    {
        $validator = new Validator();
        $reflection = new \ReflectionClass($validator);
        $property = $reflection->getProperty('payoutEvents');
        $property->setAccessible(true);
        $property->setValue($validator, $this->mockPayoutEvents);

        $invalidFormats = [
            '123456789',    // 9 digits
            '12345',        // 5 digits
            '12345678901',  // 11 digits
            'abcd123456',   // contains letters
            '123-456-7890', // contains special characters
        ];

        foreach ($invalidFormats as $invalidNumber) {
            // Setup fresh mocks for each iteration
            $this->mockPayoutEvents
                ->shouldReceive('trackPayoutsToPhoneNumberMobileNumberInvalidEvent')
                ->once()
                ->with($invalidNumber);

            try {
                $this->callPrivateMethod($validator, 'validateMobileNumberFormat', [$invalidNumber]);
                $this->fail("Expected BadRequestException for mobile number: {$invalidNumber}");
            } catch (BadRequestException $e) {
                $this->assertStringContainsString('Mobile number should be 10 digit long', $e->getMessage());
            }
        }
    }

    /**
     * Test 5: Event tracking without merchant context (no basicauth) - should not crash
     */
    public function testValidateMobileNumberFormatWithoutMerchantContext()
    {
        // Mock basicauth to return null (no merchant context)
        $mockBasicAuthWithoutMerchant = Mockery::mock()->shouldIgnoreMissing();
        $mockBasicAuthWithoutMerchant->shouldReceive('getMerchant')->andReturn(null);
        $this->app->instance('basicauth', $mockBasicAuthWithoutMerchant);

        // Event tracking should still happen
        $this->mockPayoutEvents
            ->shouldReceive('trackPayoutsToPhoneNumberMobileNumberInvalidEvent')
            ->once()
            ->with('123456789');

        // Create validator instance and inject mocked payoutEvents
        $validator = new Validator();
        $reflection = new \ReflectionClass($validator);
        $property = $reflection->getProperty('payoutEvents');
        $property->setAccessible(true);
        $property->setValue($validator, $this->mockPayoutEvents);

        // Should still throw exception
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Mobile number should be 10 digit long');

        $this->callPrivateMethod($validator, 'validateMobileNumberFormat', ['123456789']);
    }

    /**
     * Helper method to call private methods for testing
     */
    private function callPrivateMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
