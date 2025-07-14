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

        // Expect the diag service to be called
        $mockDiag->shouldReceive('trackPhoneNumberPayoutEvents')
            ->atLeast()->times(1);

        // Expect info logging to happen
        $mockTrace->shouldReceive('info')
            ->atLeast()->times(1);

        $events = new Events();

        // Test VPA Updated Event
        $events->trackPayoutsToPhoneNumberVPAUpdatedEvent(
            'merchant_123',
            '9876543210',
            'John Doe',
            'john.doe@paytm',
            'old.john@upi',
            'fund_account_123'
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

        $this->addToAssertionCount(1);
    }

    /**
     * Test mobile number invalid event with merchant context
     */
    public function testTrackMobileInvalidEventWithMerchantContext()
    {
        $mockDiag = Mockery::mock();
        $mockTrace = Mockery::mock();
        $mockBasicAuth = Mockery::mock();
        $mockMerchant = Mockery::mock();

        $mockTrace->shouldIgnoreMissing();
        $this->app->instance('diag', $mockDiag);
        $this->app->instance('trace', $mockTrace);
        $this->app->instance('basicauth', $mockBasicAuth);

        $mockMerchant->shouldReceive('getId')->andReturn('merchant_123');
        $mockBasicAuth->shouldReceive('getMerchant')->andReturn($mockMerchant);

        $eventCalled = false;
        $mockDiag->shouldReceive('trackPhoneNumberPayoutEvents')
            ->andReturnUsing(function() use (&$eventCalled) {
                $eventCalled = true;
                return true;
            });

        $mockTrace->shouldReceive('info')->atLeast()->once();

        $events = new Events();
        $events->trackPayoutsToPhoneNumberMobileNumberInvalidEvent('123456789');

        $this->assertTrue($eventCalled, 'Event tracking method should have been called');
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

        $errorLogged = false;
        $mockTrace->shouldReceive('error')
            ->andReturnUsing(function() use (&$errorLogged) {
                $errorLogged = true;
                return true;
            });

        $events = new Events();

        $events->trackPayoutsToPhoneNumberVpaNotFoundEvent(
            'merchant_123',
            '9876543210',
            'John Doe'
        );

        $this->assertTrue($errorLogged, 'Error should have been logged when exception occurs');
    }

    /**
     * Test VPA Fund Account Created Event
     */
    public function testTrackVPAFundAccountCreatedEvent()
    {
        // Mock dependencies
        $mockDiag = Mockery::mock();
        $mockTrace = Mockery::mock();
        $mockTrace->shouldIgnoreMissing();
        $this->app->instance('diag', $mockDiag);
        $this->app->instance('trace', $mockTrace);

        // Track that the event method is called
        $eventCalled = false;
        $mockDiag->shouldReceive('trackPhoneNumberPayoutEvents')
            ->andReturnUsing(function($eventCode, $properties) use (&$eventCalled) {
                $eventCalled = true;
                // Verify the correct event properties are passed
                $this->assertEquals('merchant_123', $properties[Constants::MERCHANT_ID]);
                $this->assertEquals('fund_account_456', $properties['fund_account_id']);
                $this->assertEquals(Events::VPA_TYPE_FUND_ACCOUNT_CREATED, $properties['event_name']);
                return true;
            });

        $mockTrace->shouldReceive('info')->atLeast()->once();

        $events = new Events();
        $events->trackVPAFundAccountCreatedEvent('merchant_123', 'fund_account_456');

        // Assert that the event was actually called
        $this->assertTrue($eventCalled, 'VPA Fund Account Created event should have been tracked');
    }

    /**
     * Test VPA Fund Account Created Event with error handling
     */
    public function testTrackVPAFundAccountCreatedEventErrorHandling()
    {
        // Mock dependencies that will cause errors
        $mockDiag = Mockery::mock();
        $mockTrace = Mockery::mock();
        $this->app->instance('diag', $mockDiag);
        $this->app->instance('trace', $mockTrace);

        // Make diag service throw an exception
        $mockDiag->shouldReceive('trackPhoneNumberPayoutEvents')
            ->andThrow(new \Exception('Service unavailable'));

        // Track that error logging happens
        $errorLogged = false;
        $mockTrace->shouldReceive('error')
            ->andReturnUsing(function($traceCode, $data) use (&$errorLogged) {
                $errorLogged = true;
                // Verify error logging contains expected data
                $this->assertEquals('Service unavailable', $data['error_message']);
                $this->assertEquals('merchant_123', $data['merchant_id']);
                $this->assertEquals(Events::VPA_TYPE_FUND_ACCOUNT_CREATED, $data['context']);
                return true;
            });

        $events = new Events();

        // Should not throw exception - should handle gracefully
        $events->trackVPAFundAccountCreatedEvent('merchant_123', 'fund_account_456');

        // Assert that error was properly logged
        $this->assertTrue($errorLogged, 'Error should have been logged when VPA Fund Account Created event fails');
    }

    /**
     * Test the central trackPhoneNumberPayoutEvents method with all event types
     */
    public function testTrackPhoneNumberPayoutEventsAllEventTypes()
    {
        // Mock dependencies
        $mockDiag = Mockery::mock();
        $mockTrace = Mockery::mock();
        $mockTrace->shouldIgnoreMissing();
        $this->app->instance('diag', $mockDiag);
        $this->app->instance('trace', $mockTrace);

        $events = new Events();
        $testProperties = ['test_property' => 'test_value'];

        // Test all event types
        $eventTypes = [
            Events::PAYOUTS_TO_PHONE_NUMBER_VPA_NOT_FOUND,
            Events::PAYOUTS_TO_PHONE_NUMBER_NAME_MATCHING_BELOW_THRESHOLD,
            Events::PAYOUTS_TO_PHONE_NUMBER_MOBILE_NUMBER_FORMAT_INVALID,
            Events::PAYOUTS_TO_PHONE_NUMBER_VPA_UPDATED,
            Events::VPA_TYPE_FUND_ACCOUNT_CREATED,
            Events::VPA_FETCH_TIME_TAKEN, // Added new event type
        ];

        foreach ($eventTypes as $eventType) {
            // Track calls for each event type
            $mockDiag->shouldReceive('trackPhoneNumberPayoutEvents')
                ->once()
                ->withAnyArgs();

            $mockTrace->shouldReceive('info')
                ->once()
                ->withAnyArgs();

            // Call the method
            $events->trackPhoneNumberPayoutEvents($eventType, $testProperties);
        }

        // Test passes if all event types are handled without errors
        $this->addToAssertionCount(count($eventTypes));
    }

    /**
     * Test VPA Fetch Time Taken Event - Happy Path
     */
    public function testTrackVPAFetchTimeTakenEvent()
    {
        // Mock dependencies
        $mockDiag = Mockery::mock();
        $mockTrace = Mockery::mock();
        $mockTrace->shouldIgnoreMissing();
        $this->app->instance('diag', $mockDiag);
        $this->app->instance('trace', $mockTrace);

        // Track that the event method is called with correct properties
        $eventCalled = false;
        $mockDiag->shouldReceive('trackPhoneNumberPayoutEvents')
            ->andReturnUsing(function($eventCode, $properties) use (&$eventCalled) {
                $eventCalled = true;
                // Verify the correct event properties are passed
                $this->assertEquals('merchant_123', $properties[Constants::MERCHANT_ID]);
                $this->assertEquals('xxxxxxxx@paytm', $properties[Constants::VPA]);
                $this->assertEquals('xxxxx43210', $properties[Constants::MOBILE]);
                $this->assertArrayHasKey('time_taken', $properties);
                $this->assertIsNumeric($properties['time_taken']);
                $this->assertGreaterThanOrEqual(0, $properties['time_taken']);
                return true;
            });

        $mockTrace->shouldReceive('info')->atLeast()->once();

        $events = new Events();
        $startTime = round(microtime(true) * 1000);
        
        // Small delay to ensure time_taken > 0
        usleep(1000); // 1ms delay
        
        $events->trackVPAFetchTimeTakenEvent(
            $startTime,
            'john.doe@paytm',
            '9876543210',
            'merchant_123'
        );

        $this->assertTrue($eventCalled, 'VPA Fetch Time Taken event should have been tracked');
    }

    /**
     * Test VPA Fetch Time Taken Event - Error Handling
     */
    public function testTrackVPAFetchTimeTakenEventErrorHandling()
    {
        // Mock dependencies that will cause errors
        $mockDiag = Mockery::mock();
        $mockTrace = Mockery::mock();
        $this->app->instance('diag', $mockDiag);
        $this->app->instance('trace', $mockTrace);

        // Make diag service throw an exception
        $mockDiag->shouldReceive('trackPhoneNumberPayoutEvents')
            ->andThrow(new \Exception('Tracking service unavailable'));

        // Track that error logging happens
        $errorLogged = false;
        $mockTrace->shouldReceive('error')
            ->andReturnUsing(function($traceCode, $data) use (&$errorLogged) {
                $errorLogged = true;
                // Verify error logging contains expected data
                $this->assertEquals('Tracking service unavailable', $data['error_message']);
                $this->assertEquals('merchant_123', $data['merchant_id']);
                $this->assertEquals(Events::VPA_FETCH_TIME_TAKEN, $data['context']);
                return true;
            });

        $events = new Events();
        $startTime = round(microtime(true) * 1000);

        // Should not throw exception - should handle gracefully
        $events->trackVPAFetchTimeTakenEvent(
            $startTime,
            'john.doe@paytm',
            '9876543210',
            'merchant_123'
        );

        // Assert that error was properly logged
        $this->assertTrue($errorLogged, 'Error should have been logged when VPA Fetch Time Taken event fails');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}
