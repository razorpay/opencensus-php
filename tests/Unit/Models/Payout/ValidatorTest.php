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
    protected $mockPayoutCore;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock all dependencies with shouldIgnoreMissing to handle framework calls
        $this->mockTrace = Mockery::mock()->shouldIgnoreMissing();
        $this->mockDiag = Mockery::mock();
        $this->mockMerchant = Mockery::mock();
        $this->mockBasicAuth = Mockery::mock()->shouldIgnoreMissing();
        $this->mockPayoutCore = Mockery::mock('RZP\Models\Payout\Core')->shouldIgnoreMissing();

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
        // Mock the sanitizeDataForTracking method
        $this->mockPayoutCore
            ->shouldReceive('sanitizeDataForTracking')
            ->once()
            ->with([FundAccount\Entity::MOBILE => '123456789'])
            ->andReturn([FundAccount\Entity::MOBILE => 'xxxxx43210']);

        // Setup: Event tracking should succeed - payoutCore method called
        $this->mockPayoutCore
            ->shouldReceive('trackPhoneNumberPayoutEvents')
            ->once()
            ->with(
                Validator::PAYOUTS_TO_PHONE_NUMBER_MOBILE_NUMBER_FORMAT_INVALID,
                [
                    FundAccount\Entity::MOBILE => 'xxxxx43210',
                    Payout\Entity::MERCHANT_ID => 'test_merchant_123',
                    'failure_reason' => Validator::PAYOUTS_TO_PHONE_NUMBER_MOBILE_NUMBER_FORMAT_INVALID
                ]
            );

        // Create validator instance and inject mocked payoutCore
        $validator = new Validator();
        $reflection = new \ReflectionClass($validator);
        $property = $reflection->getProperty('payoutCore');
        $property->setAccessible(true);
        $property->setValue($validator, $this->mockPayoutCore);

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
        // Mock the sanitizeDataForTracking method
        $this->mockPayoutCore
            ->shouldReceive('sanitizeDataForTracking')
            ->once()
            ->with([FundAccount\Entity::MOBILE => 'abcd123456'])
            ->andReturn([FundAccount\Entity::MOBILE => 'xxxxx23456']);

        // Setup: PayoutCore service throws exception
        $this->mockPayoutCore
            ->shouldReceive('trackPhoneNumberPayoutEvents')
            ->once()
            ->andThrow(new \Exception('PayoutCore service failed'));

        // Setup: Error should be logged when event tracking fails
        $this->mockTrace
            ->shouldReceive('error')
            ->once()
            ->with(
                TraceCode::PAYOUT_TO_PHONE_NUMBER_EVENT_TRACKING_FAILED,
                [
                    'error_message' => 'PayoutCore service failed',
                    'context' => Validator::PAYOUTS_TO_PHONE_NUMBER_MOBILE_NUMBER_FORMAT_INVALID
                ]
            );

        // Create validator instance and inject mocked payoutCore
        $validator = new Validator();
        $reflection = new \ReflectionClass($validator);
        $property = $reflection->getProperty('payoutCore');
        $property->setAccessible(true);
        $property->setValue($validator, $this->mockPayoutCore);

        // Main business logic should still work - exception should be thrown
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
        $this->mockPayoutCore->shouldNotReceive('trackPhoneNumberPayoutEvents');
        $this->mockPayoutCore->shouldNotReceive('sanitizeDataForTracking');
        $this->mockTrace->shouldNotReceive('error')->with(TraceCode::PAYOUT_TO_PHONE_NUMBER_EVENT_TRACKING_FAILED, Mockery::any());

        // Create validator instance and inject mocked payoutCore
        $validator = new Validator();
        $reflection = new \ReflectionClass($validator);
        $property = $reflection->getProperty('payoutCore');
        $property->setAccessible(true);
        $property->setValue($validator, $this->mockPayoutCore);

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
        $property = $reflection->getProperty('payoutCore');
        $property->setAccessible(true);
        $property->setValue($validator, $this->mockPayoutCore);

        $invalidFormats = [
            '123456789',    // 9 digits
            '12345',        // 5 digits
            '12345678901',  // 11 digits
            'abcd123456',   // contains letters
            '123-456-7890', // contains special characters
        ];

        foreach ($invalidFormats as $invalidNumber) {
            // Setup fresh mocks for each iteration
            $this->mockPayoutCore
                ->shouldReceive('sanitizeDataForTracking')
                ->once()
                ->with([FundAccount\Entity::MOBILE => $invalidNumber])
                ->andReturn([FundAccount\Entity::MOBILE => 'xxxxx' . substr($invalidNumber, -5)]);

            $this->mockPayoutCore
                ->shouldReceive('trackPhoneNumberPayoutEvents')
                ->once()
                ->with(
                    Validator::PAYOUTS_TO_PHONE_NUMBER_MOBILE_NUMBER_FORMAT_INVALID,
                    [
                        FundAccount\Entity::MOBILE => 'xxxxx' . substr($invalidNumber, -5),
                        Payout\Entity::MERCHANT_ID => 'test_merchant_123',
                        'failure_reason' => Validator::PAYOUTS_TO_PHONE_NUMBER_MOBILE_NUMBER_FORMAT_INVALID
                    ]
                );

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

        // No event tracking should happen
        $this->mockPayoutCore->shouldNotReceive('trackPhoneNumberPayoutEvents');
        $this->mockPayoutCore->shouldNotReceive('sanitizeDataForTracking');
        $this->mockTrace->shouldNotReceive('error')->with(TraceCode::PAYOUT_TO_PHONE_NUMBER_EVENT_TRACKING_FAILED, Mockery::any());

        // Create validator instance and inject mocked payoutCore
        $validator = new Validator();
        $reflection = new \ReflectionClass($validator);
        $property = $reflection->getProperty('payoutCore');
        $property->setAccessible(true);
        $property->setValue($validator, $this->mockPayoutCore);

        // Should still throw exception but without event tracking
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
