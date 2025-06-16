<?php

namespace Unit\Models\LinkedNumber;

use Mockery;
use RZP\Models\LinkedNumber\Core;
use RZP\Models\FundAccount;
use RZP\Tests\TestCase;
use RZP\Exception\BadRequestException;
use RZP\Trace\TraceCode;
use RZP\Models\Payout\Metric;
use RZP\Services\PayoutService\VpaMapperFetch;

class CoreTest extends TestCase
{
    protected $mockMappedVpaFetchClient;
    protected $mockPayoutService;
    protected $mockFavCore;
    protected $mockTrace;
    protected $mockPayoutCore;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock all dependencies
        $this->mockMappedVpaFetchClient = Mockery::mock();
        $this->mockPayoutService = Mockery::mock();
        $this->mockFavCore = Mockery::mock();
        $this->mockTrace = Mockery::mock()->shouldIgnoreMissing();
        $this->mockPayoutCore = Mockery::mock();

        // Inject mocked dependencies into app container
        $this->app->instance(VpaMapperFetch::MAPPED_VPA_FETCH, $this->mockMappedVpaFetchClient);
        $this->app->instance('trace', $this->mockTrace);
    }

    protected function createCoreWithMocks(): Core
    {
        $core = new Core();

        // Use reflection to inject mocked dependencies
        $reflection = new \ReflectionClass($core);

        $payoutServiceProperty = $reflection->getProperty('payoutService');
        $payoutServiceProperty->setAccessible(true);
        $payoutServiceProperty->setValue($core, $this->mockPayoutService);

        $favCoreProperty = $reflection->getProperty('favCore');
        $favCoreProperty->setAccessible(true);
        $favCoreProperty->setValue($core, $this->mockFavCore);

        $payoutCoreProperty = $reflection->getProperty('payoutCore');
        $payoutCoreProperty->setAccessible(true);
        $payoutCoreProperty->setValue($core, $this->mockPayoutCore);

        return $core;
    }

    /**
     * Test 1.1: VPA Not Found - Verifies main business logic and successful event tracking
     */
    public function testFetchMappedVpaFromLinkedNumberVpaNotFoundThrowsException()
    {
        // Setup: VPA fetch returns empty data
        $this->mockMappedVpaFetchClient
            ->shouldReceive('fetchMappedVpaViaMicroservice')
            ->once()
            ->with('9876543210')
            ->andReturn([]);

        // Setup: Trace count should be called for metric
        $this->mockTrace
            ->shouldReceive('count')
            ->once()
            ->with(Metric::PAYOUTS_TO_PHONE_NUMBER_VPA_NOT_FOUND_COUNT);

        // Setup: Mock sanitizeDataForTracking method on payoutCore
        $this->mockPayoutCore
            ->shouldReceive('sanitizeDataForTracking')
            ->once()
            ->with([FundAccount\Entity::MOBILE => '9876543210'])
            ->andReturn([FundAccount\Entity::MOBILE => 'xxxxx43210']);

        // Setup: Event tracking should succeed - payoutCore service called
        $this->mockPayoutCore
            ->shouldReceive('trackPhoneNumberPayoutEvents')
            ->once()
            ->with(
                Core::PAYOUTS_TO_PHONE_NUMBER_VPA_NOT_FOUND,
                [
                    'merchant_id' => 'test_merchant_123',
                    'mobile' => 'xxxxx43210',
                    'customer_name' => 'Test Customer',
                    'failure_reason' => Core::PAYOUTS_TO_PHONE_NUMBER_VPA_NOT_FOUND
                ]
            );

        // Main business logic: Should throw BadRequestException
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('No linked account details found');

        $core = $this->createCoreWithMocks();
        $core->FetchMappedVpaFromLinkedNumber('9876543210', 'Test Customer', 'test_merchant_123');
    }

    /**
     * Test 1.2: VPA Not Found with Empty VPA Field
     */
    public function testFetchMappedVpaFromLinkedNumberEmptyVpaFieldThrowsException()
    {
        // Setup: VPA fetch returns data but VPA field is empty
        $this->mockMappedVpaFetchClient
            ->shouldReceive('fetchMappedVpaViaMicroservice')
            ->once()
            ->with('9876543210')
            ->andReturn([
                FundAccount\Entity::VPA => '',
                FundAccount\Entity::CUSTOMER_NAME => 'Some Customer'
            ]);

        $this->mockTrace
            ->shouldReceive('count')
            ->once()
            ->with(Metric::PAYOUTS_TO_PHONE_NUMBER_VPA_NOT_FOUND_COUNT);

        // Setup: Mock sanitizeDataForTracking method on payoutCore
        $this->mockPayoutCore
            ->shouldReceive('sanitizeDataForTracking')
            ->once()
            ->with([FundAccount\Entity::MOBILE => '9876543210'])
            ->andReturn([FundAccount\Entity::MOBILE => 'xxxxx43210']);

        // Event tracking should succeed
        $this->mockPayoutCore
            ->shouldReceive('trackPhoneNumberPayoutEvents')
            ->once()
            ->with(
                Core::PAYOUTS_TO_PHONE_NUMBER_VPA_NOT_FOUND,
                [
                    'merchant_id' => 'test_merchant_123',
                    'mobile' => 'xxxxx43210',
                    'customer_name' => 'Test Customer',
                    'failure_reason' => Core::PAYOUTS_TO_PHONE_NUMBER_VPA_NOT_FOUND
                ]
            );

        $this->expectException(BadRequestException::class);

        $core = $this->createCoreWithMocks();
        $core->FetchMappedVpaFromLinkedNumber('9876543210', 'Test Customer', 'test_merchant_123');
    }

    /**
     * Test 1.3: VPA Found - Success Path with Above Threshold Match
     */
    public function testFetchMappedVpaFromLinkedNumberSuccessfulFlow()
    {
        // Setup: VPA fetch returns valid data
        $validVpaData = [
            FundAccount\Entity::VPA => 'test@upi',
            FundAccount\Entity::CUSTOMER_NAME => 'Bank Customer Name'
        ];

        $this->mockMappedVpaFetchClient
            ->shouldReceive('fetchMappedVpaViaMicroservice')
            ->once()
            ->with('9876543210')
            ->andReturn($validVpaData);

        // Setup: Trace info should be called for successful VPA fetch
        $this->mockTrace
            ->shouldReceive('info')
            ->once()
            ->with(TraceCode::MAPPED_VPA_FROM_LINKED_NUMBER, Mockery::any());

        // Setup: doMatchScoring dependencies - above threshold
        $this->mockPayoutService
            ->shouldReceive('getMerchantSettingsForThresholdFromPayoutService')
            ->once()
            ->with('test_merchant_123')
            ->andReturn(0.8);

        $this->mockFavCore
            ->shouldReceive('getNameScoreForValidation')
            ->once()
            ->with('Bank Customer Name', 'Test Customer')
            ->andReturn(0.9); // Above threshold

        $this->mockTrace
            ->shouldReceive('info')
            ->once()
            ->with(TraceCode::NAME_MATCHING_SCORE_FOR_LINKED_NUMBER, ['matchScore' => 0.9]);

        // Should return VPA data without exception
        $core = $this->createCoreWithMocks();
        $result = $core->FetchMappedVpaFromLinkedNumber('9876543210', 'Test Customer', 'test_merchant_123');

        $this->assertEquals([
            FundAccount\Entity::VPA => 'test@upi',
            FundAccount\Entity::CUSTOMER_NAME => 'Bank Customer Name'
        ], $result);
    }

    /**
     * Test 2.1: Name Match Below Threshold - Throws Exception and Tracks Event Successfully
     */
    public function testDoMatchScoringBelowThresholdThrowsException()
    {
        // Setup: Return low threshold and low match score
        $this->mockPayoutService
            ->shouldReceive('getMerchantSettingsForThresholdFromPayoutService')
            ->once()
            ->with('test_merchant_123')
            ->andReturn(0.8);

        $this->mockFavCore
            ->shouldReceive('getNameScoreForValidation')
            ->once()
            ->with('Bank Customer Name', 'Test Customer')
            ->andReturn(0.6); // Below threshold

        // Setup: Trace info for match score
        $this->mockTrace
            ->shouldReceive('info')
            ->once()
            ->with(TraceCode::NAME_MATCHING_SCORE_FOR_LINKED_NUMBER, ['matchScore' => 0.6]);

        // Setup: Trace count should be called for metric
        $this->mockTrace
            ->shouldReceive('count')
            ->once()
            ->with(Metric::PAYOUTS_TO_PHONE_NUMBER_NAME_MATCHING_THRESHOLD_FAILURE_COUNT);

        // Setup: Mock sanitizeDataForTracking method on payoutCore
        $this->mockPayoutCore
            ->shouldReceive('sanitizeDataForTracking')
            ->once()
            ->with([
                FundAccount\Entity::MOBILE => '9876543210',
                FundAccount\Entity::VPA => 'test@upi'
            ])
            ->andReturn([
                FundAccount\Entity::MOBILE => 'xxxxx43210',
                FundAccount\Entity::VPA => 'xxxx@upi'
            ]);

        // Setup: Event tracking should succeed - payoutCore service called
        $this->mockPayoutCore
            ->shouldReceive('trackPhoneNumberPayoutEvents')
            ->once()
            ->with(
                Core::PAYOUTS_TO_PHONE_NUMBER_NAME_MATCHING_BELOW_THRESHOLD,
                [
                    'merchant_id' => 'test_merchant_123',
                    'mobile' => 'xxxxx43210',
                    'customer_name' => 'Test Customer',
                    'vpa' => 'xxxx@upi',
                    'bank_customer_name' => 'Bank Customer Name',
                    'match_score' => 0.6,
                    'threshold' => 0.8,
                    'failure_reason' => Core::PAYOUTS_TO_PHONE_NUMBER_NAME_MATCHING_BELOW_THRESHOLD
                ]
            );

        // Execute test - should throw BadRequestException
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Account holder name not matching with bank provided name');

        $core = $this->createCoreWithMocks();
        $core->doMatchScoring('Bank Customer Name', 'Test Customer', 'test_merchant_123', '9876543210', 'test@upi');
    }

    /**
     * Test 2.2: Name Match Above Threshold - No Exception
     */
    public function testDoMatchScoringAboveThresholdPasses()
    {
        // Setup: Return threshold and high match score
        $this->mockPayoutService
            ->shouldReceive('getMerchantSettingsForThresholdFromPayoutService')
            ->once()
            ->with('test_merchant_123')
            ->andReturn(0.8);

        $this->mockFavCore
            ->shouldReceive('getNameScoreForValidation')
            ->once()
            ->with('Bank Customer Name', 'Test Customer')
            ->andReturn(0.9); // Above threshold

        // Only info trace should be called, no count or error
        $this->mockTrace
            ->shouldReceive('info')
            ->once()
            ->with(TraceCode::NAME_MATCHING_SCORE_FOR_LINKED_NUMBER, ['matchScore' => 0.9]);

        // Should complete without exception
        $core = $this->createCoreWithMocks();

        try {
            $core->doMatchScoring('Bank Customer Name', 'Test Customer', 'test_merchant_123', '9876543210', 'test@upi');
            $this->addToAssertionCount(1);
        } catch (\Exception $e) {
            $this->fail('Expected no exception to be thrown, but got: ' . $e->getMessage());
        }
    }

    /**
     * Test 2.3: Exact Threshold Match - Should Pass (not below)
     */
    public function testDoMatchScoringExactThresholdPasses()
    {
        $this->mockPayoutService
            ->shouldReceive('getMerchantSettingsForThresholdFromPayoutService')
            ->once()
            ->andReturn(0.8);

        $this->mockFavCore
            ->shouldReceive('getNameScoreForValidation')
            ->once()
            ->andReturn(0.8); // Exactly at threshold

        $this->mockTrace
            ->shouldReceive('info')
            ->once()
            ->with(TraceCode::NAME_MATCHING_SCORE_FOR_LINKED_NUMBER, ['matchScore' => 0.8]);

        // Should complete without exception (not below threshold)
        $core = $this->createCoreWithMocks();

        try {
            $core->doMatchScoring('Bank Customer Name', 'Test Customer', 'test_merchant_123', '9876543210', 'test@upi');
            $this->addToAssertionCount(1);
        } catch (\Exception $e) {
            $this->fail('Expected no exception to be thrown, but got: ' . $e->getMessage());
        }
    }

    /**
     * Test 3.1: End-to-End Failure Flow - VPA Found but Name Match Fails
     */
    public function testEndToEndFlowVpaFoundButNameMatchFails()
    {
        // Setup: VPA fetch returns valid data
        $validVpaData = [
            FundAccount\Entity::VPA => 'test@upi',
            FundAccount\Entity::CUSTOMER_NAME => 'Bank Customer Name'
        ];

        $this->mockMappedVpaFetchClient
            ->shouldReceive('fetchMappedVpaViaMicroservice')
            ->once()
            ->andReturn($validVpaData);

        $this->mockTrace
            ->shouldReceive('info')
            ->once()
            ->with(TraceCode::MAPPED_VPA_FROM_LINKED_NUMBER, Mockery::any());

        // Setup: Name matching fails
        $this->mockPayoutService
            ->shouldReceive('getMerchantSettingsForThresholdFromPayoutService')
            ->once()
            ->andReturn(0.8);

        $this->mockFavCore
            ->shouldReceive('getNameScoreForValidation')
            ->once()
            ->andReturn(0.5); // Below threshold

        $this->mockTrace
            ->shouldReceive('info')
            ->once()
            ->with(TraceCode::NAME_MATCHING_SCORE_FOR_LINKED_NUMBER, ['matchScore' => 0.5]);

        $this->mockTrace
            ->shouldReceive('count')
            ->once()
            ->with(Metric::PAYOUTS_TO_PHONE_NUMBER_NAME_MATCHING_THRESHOLD_FAILURE_COUNT);

        // Setup: Mock sanitizeDataForTracking method on payoutCore
        $this->mockPayoutCore
            ->shouldReceive('sanitizeDataForTracking')
            ->once()
            ->with([
                FundAccount\Entity::MOBILE => '9876543210',
                FundAccount\Entity::VPA => 'test@upi'
            ])
            ->andReturn([
                FundAccount\Entity::MOBILE => 'xxxxx43210',
                FundAccount\Entity::VPA => 'xxxx@upi'
            ]);

        // Event tracking should succeed
        $this->mockPayoutCore
            ->shouldReceive('trackPhoneNumberPayoutEvents')
            ->once()
            ->with(
                Core::PAYOUTS_TO_PHONE_NUMBER_NAME_MATCHING_BELOW_THRESHOLD,
                [
                    'merchant_id' => 'test_merchant_123',
                    'mobile' => 'xxxxx43210',
                    'customer_name' => 'Different Name',
                    'vpa' => 'xxxx@upi',
                    'bank_customer_name' => 'Bank Customer Name',
                    'match_score' => 0.5,
                    'threshold' => 0.8,
                    'failure_reason' => Core::PAYOUTS_TO_PHONE_NUMBER_NAME_MATCHING_BELOW_THRESHOLD
                ]
            );

        // Should throw exception for name mismatch
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Account holder name not matching with bank provided name');

        $core = $this->createCoreWithMocks();
        $core->FetchMappedVpaFromLinkedNumber('9876543210', 'Different Name', 'test_merchant_123');
    }

    /**
     * Test 4.1: Event Tracking Failure Scenario - PayoutCore Service Throws Exception
     */
    public function testEventTrackingFailureIsHandledGracefully()
    {
        // Setup: VPA fetch returns empty data
        $this->mockMappedVpaFetchClient
            ->shouldReceive('fetchMappedVpaViaMicroservice')
            ->once()
            ->andReturn([]);

        $this->mockTrace
            ->shouldReceive('count')
            ->once()
            ->with(Metric::PAYOUTS_TO_PHONE_NUMBER_VPA_NOT_FOUND_COUNT);

        // Setup: Mock sanitizeDataForTracking method on payoutCore
        $this->mockPayoutCore
            ->shouldReceive('sanitizeDataForTracking')
            ->once()
            ->with([FundAccount\Entity::MOBILE => '9876543210'])
            ->andReturn([FundAccount\Entity::MOBILE => 'xxxxx43210']);

        // Setup: PayoutCore service throws exception
        $this->mockPayoutCore
            ->shouldReceive('trackPhoneNumberPayoutEvents')
            ->once()
            ->with(
                Core::PAYOUTS_TO_PHONE_NUMBER_VPA_NOT_FOUND,
                [
                    'merchant_id' => 'test_merchant_123',
                    'mobile' => 'xxxxx43210',
                    'customer_name' => 'Test Customer',
                    'failure_reason' => Core::PAYOUTS_TO_PHONE_NUMBER_VPA_NOT_FOUND
                ]
            )
            ->andThrow(new \Exception('PayoutCore service failed'));

        // Setup: Error should be logged when event tracking fails
        $this->mockTrace
            ->shouldReceive('error')
            ->once()
            ->with(
                TraceCode::PAYOUT_TO_PHONE_NUMBER_EVENT_TRACKING_FAILED,
                [
                    'error_message' => 'PayoutCore service failed',
                    'merchant_id' => 'test_merchant_123',
                    'context' => Core::PAYOUTS_TO_PHONE_NUMBER_VPA_NOT_FOUND
                ]
            );

        // Main business logic should still work - exception should be thrown
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('No linked account details found');

        $core = $this->createCoreWithMocks();
        $core->FetchMappedVpaFromLinkedNumber('9876543210', 'Test Customer', 'test_merchant_123');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
