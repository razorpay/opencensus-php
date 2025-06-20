<?php

namespace Unit\Models\Payout;

use Mockery;
use RZP\Models\Payout\Events;
use RZP\Models\Payout\Constants;
use RZP\Tests\TestCase;

class EventsTest extends TestCase
{
    protected $events;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock trace dependency and inject into the app container
        $mockTrace = Mockery::mock();
        $mockTrace->shouldIgnoreMissing(); // Allow unexpected method calls
        $this->app->instance('trace', $mockTrace);

        // Create Events instance - it will use the mocked trace from the container
        $this->events = new Events();
    }

    /**
     * Test mobile number sanitization with various scenarios
     */
    public function testMobileSanitization()
    {
        $testCases = [
            // [input, expected_output, description]
            ['9876543210', 'xxxxx43210', 'more than 5 digits'],
            ['12345', '12345', 'exactly 5 digits'],
            ['123', 'xxx', 'less than 5 digits'],
            ['', '', 'empty string'],
            [null, '', 'null value'],
            ['+91-987-654-3210', 'xxxxxxxxxxx-3210', 'special characters'],
            [str_repeat('1', 20), str_repeat('x', 15) . '11111', 'very long string'],
        ];

        foreach ($testCases as [$input, $expected, $description]) {
            $data = [Constants::MOBILE => $input];
            $result = $this->events->sanitizeDataForTracking($data);
            
            $this->assertEquals($expected, $result[Constants::MOBILE], 
                "Failed for mobile: $description");
        }
    }

    /**
     * Test VPA sanitization with various scenarios
     */
    public function testVpaSanitization()
    {
        $testCases = [
            // [input, expected_output, description]
            ['john.doe@paytm', 'xxxxxxxx@paytm', 'standard format'],
            ['invalidvpa', 'xxxxxxxxxx', 'without @ symbol'],
            ['user@domain@extra', 'xxxx@domain@extra', 'multiple @ symbols'],
            ['@', '@', 'only @ symbol'],
            ['@paytm', '@paytm', '@ at beginning'],
            ['', '', 'empty string'],
            [null, '', 'null value'],
            ['123456@bank', 'xxxxxx@bank', 'numeric prefix'],
            [str_repeat('a', 50) . '@domain.com', str_repeat('x', 50) . '@domain.com', 'very long prefix'],
        ];

        foreach ($testCases as [$input, $expected, $description]) {
            $data = [Constants::VPA => $input];
            $result = $this->events->sanitizeDataForTracking($data);
            
            $this->assertEquals($expected, $result[Constants::VPA], 
                "Failed for VPA: $description");
        }
    }

    /**
     * Test mixed data sanitization scenarios
     */
    public function testMixedDataSanitization()
    {
        // Test with both mobile and VPA
        $data = [
            Constants::MOBILE => '9876543210',
            Constants::VPA => 'user@domain'
        ];
        $result = $this->events->sanitizeDataForTracking($data);
        
        $this->assertEquals('xxxxx43210', $result[Constants::MOBILE]);
        $this->assertEquals('xxxx@domain', $result[Constants::VPA]);

        // Test with unknown data types (should pass through unchanged)
        $data = [
            'unknown_field' => 'some_value',
            'another_field' => 'another_value', 
            Constants::MOBILE => '9876543210'
        ];
        $result = $this->events->sanitizeDataForTracking($data);
        
        $this->assertEquals('some_value', $result['unknown_field']);
        $this->assertEquals('another_value', $result['another_field']);
        $this->assertEquals('xxxxx43210', $result[Constants::MOBILE]);

        // Test with empty array
        $this->assertEquals([], $this->events->sanitizeDataForTracking([]));
    }

    /**
     * Test event tracking methods - simplified approach
     */
    public function testEventTrackingMethods()
    {
        // Mock dependencies
        $mockDiag = Mockery::mock();
        $mockTrace = Mockery::mock();
        $mockTrace->shouldIgnoreMissing();
        $this->app->instance('diag', $mockDiag);
        $this->app->instance('trace', $mockTrace);

        // Setup basic expectations - just verify methods are called
        $mockDiag->shouldReceive('trackPhoneNumberPayoutEvents')
            ->times(3) // We'll test 3 methods 
            ->withAnyArgs();
        
        $mockTrace->shouldReceive('info')
            ->times(3)
            ->withAnyArgs();

        $events = new Events();

        // Test VPA Updated Event
        $events->trackPayoutsToPhoneNumberVPAUpdatedEvent(
            'merchant_123',
            '9876543210', 
            'John Doe',
            'john.doe@paytm',
            'old.john@upi'
        );

        // Test VPA Not Found Event
        $events->trackPayoutsToPhoneNumberVpaNotFoundEvent(
            'merchant_123',
            '9876543210',
            'John Doe'
        );

        // Test Name Matching Below Threshold Event
        $events->trackPayoutsToPhoneNumberNameMatchingBelowThresholdEvent(
            'merchant_123',
            '9876543210',
            'John Doe',
            'Johnny Doe', 
            'john.doe@paytm',
            65,
            75
        );

        // If we get here, all methods executed without exceptions
        $this->assertTrue(true);
    }

    /**
     * Test mobile number invalid event with merchant context
     */
    public function testTrackMobileInvalidEventWithMerchantContext()
    {
        // Mock all dependencies
        $mockDiag = Mockery::mock();
        $mockTrace = Mockery::mock();
        $mockBasicAuth = Mockery::mock();
        $mockMerchant = Mockery::mock();
        
        $mockTrace->shouldIgnoreMissing();
        $this->app->instance('diag', $mockDiag);
        $this->app->instance('trace', $mockTrace);
        $this->app->instance('basicauth', $mockBasicAuth);

        // Setup merchant context
        $mockMerchant->shouldReceive('getId')->andReturn('merchant_123');
        $mockBasicAuth->shouldReceive('getMerchant')->andReturn($mockMerchant);

        // Expect tracking to happen
        $mockDiag->shouldReceive('trackPhoneNumberPayoutEvents')->once();
        $mockTrace->shouldReceive('info')->once();

        $events = new Events();
        $events->trackPayoutsToPhoneNumberMobileNumberInvalidEvent('123456789');

        $this->assertTrue(true);
    }

    /**
     * Test error handling in event tracking methods
     */
    public function testEventTrackingErrorHandling()
    {
        // Mock dependencies that will cause errors
        $mockDiag = Mockery::mock();
        $mockTrace = Mockery::mock();
        $this->app->instance('diag', $mockDiag);
        $this->app->instance('trace', $mockTrace);

        // Make diag service throw an exception
        $mockDiag->shouldReceive('trackPhoneNumberPayoutEvents')
            ->andThrow(new \Exception('Service down'));

        // Expect error to be logged
        $mockTrace->shouldReceive('error')->once();

        $events = new Events();
        
        // Should not throw exception - should handle gracefully
        $events->trackPayoutsToPhoneNumberVpaNotFoundEvent(
            'merchant_123',
            '9876543210', 
            'John Doe'
        );

        $this->assertTrue(true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
} 