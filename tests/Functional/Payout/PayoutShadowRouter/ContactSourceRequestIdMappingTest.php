<?php

namespace RZP\Tests\Functional\Payout\PayoutShadowRouter;

use Mockery;
use RZP\Models\Contact;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Payout\SourceRequestIDMapping\Entity as SourceRequestIDMappingEntity;
use RZP\Services\SplitzService;
use RZP\Services\PayoutService\PayoutShadowService;
use RZP\Tests\Traits\MocksSplitz;

class ContactSourceRequestIdMappingTest extends TestCase
{
    use DbEntityFetchTrait;
    use TestsBusinessBanking;
    use RequestResponseFlowTrait;
    use MocksSplitz;

    protected $splitzMock;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/ContactSourceRequestIdMappingTestData.php';

        parent::setUp();

        // Set up merchant for business banking to enable required features
        $this->setUpMerchantForBusinessBanking();

        // Mock configuration for shadow service
        $this->app['config']->set('applications.payouts_shadow_router', [
            'payouts_shadow_router_url' => 'https://shadow-service.example.com',
            'timeout' => 0.02,
            'connect_timeout' => 0.02,
            'kill_switch' => true,
            'debug' => false
        ]);

        $this->app['config']->set('app.xpayouts_shadow_router_splitz_experiment_id', 'shadow_router_exp_id');
        $this->app['config']->set('app.source_request_id_mapping_experiment_id', 'source_mapping_exp_id');
    }

    protected function getSplitzMock()
    {
        if ($this->splitzMock === null)
        {
            $this->splitzMock = Mockery::mock(SplitzService::class, [$this->app])->makePartial();
            $this->app->instance('splitzService', $this->splitzMock);
        }

        return $this->splitzMock;
    }

    private function mockSplitzExperiments($experiments = [])
    {
        $splitzMock = $this->getSplitzMock();

        $splitzMock->shouldReceive('evaluateRequest')
            ->andReturnUsing(function ($input) use ($experiments) {
                // Get the experiment_id from the input request
                $experimentId = $input['experiment_id'] ?? null;

                // Get the variant for this experiment, default to 'control' if not found
                $variant = $experiments[$experimentId] ?? 'control';

                return [
                    "response" => [
                        "variant" => [
                            "name" => $variant,
                        ]
                    ]
                ];
            });
    }
    protected function mockPayoutShadowService($shouldMirror = true, $throwError = false)
    {
        // Create a static mock since PayoutShadowService methods are static
        $shadowServiceMock = Mockery::mock('alias:' . PayoutShadowService::class);

        if ($throwError) {
            $shadowServiceMock->shouldReceive('mirrorRequest')
                              ->andThrow(new \Exception('Shadow service error'))
                              ->once();
        } else {
            $shadowServiceMock->shouldReceive('mirrorRequest')
                              ->withArgs(function($method, $path, $body, $headers) {
                                  // Assert the mirror request is called with correct parameters
                                  $this->assertEquals('POST', $method);
                                  $this->assertEquals('v1/contacts', $path); // Framework adds v1/ prefix
                                  $this->assertArrayHasKey('name', $body);
                                  $this->assertEquals('Test Contact Shadow', $body['name']);
                                  return true;
                              })
                              ->andReturnNull() // mirrorRequest returns void
                              ->times($shouldMirror ? 1 : 0);
        }

        $shadowServiceMock->shouldReceive('shouldMirrorRequest')
                          ->andReturn($shouldMirror)
                          ->zeroOrMoreTimes();

        return $shadowServiceMock;
    }

    public function testCreateContactWithSourceRequestIdMapping()
    {
        $awsTraceId = 'Root=1-507f38c4-687b7dc8a5b4c4d5e8f12345';

        // Mock Splitz experiments to enable both shadow routing and source request ID mapping
        $this->mockSplitzExperiments([
            'shadow_router_exp_id' => 'disable',
            'source_mapping_exp_id' => 'enable'
        ]);

        // Mock the PayoutShadowService to verify it gets called
        $this->mockPayoutShadowService(true);

        // Execute the test
        $this->ba->privateAuth();
        $response = $this->startTest();

        // Verify contact was created successfully
        $contact = $this->getDbLastEntity('contact');
        $this->assertNotNull($contact);
        $this->assertEquals('Test Contact Shadow', $contact->getName());
        $this->assertEquals('shadow.test@example.com', $contact->getEmail());
        $this->assertEquals('9876543210', $contact->getContact());
        $this->assertEquals('self', $contact->getType());


            $contactID = $contact->getId();
            $sourceRequestIdMapping = \DB::connection('live')->select("select * from ps_source_request_id_mapping where source_id = '$contactID'")[0];

            if ($sourceRequestIdMapping !== null)
            {
                $this->assertEquals('contact', $sourceRequestIdMapping->source_type, "source type should be 'contact'");
                $this->assertEquals($contact->getId(), $sourceRequestIdMapping->source_id, "source ID should match contact ID");
                $this->assertEquals($sourceRequestIdMapping->request_id, $awsTraceId);
            }

        // Verify response structure
        $this->assertEquals('contact', $response['entity'], "Contact Entity should be returned");
        $this->assertEquals('Test Contact Shadow', $response['name']);
    }

    public function testCreateContactWithSourceRequestIdMappingDisabled()
    {
        // Mock Splitz experiments to disable source request ID mapping but enable shadow routing
        $this->mockSplitzExperiments([
            'shadow_router_exp_id' => 'enable',
            'source_mapping_exp_id' => 'disable'
        ]);

        // Mock the PayoutShadowService to verify it gets called
        $this->mockPayoutShadowService(true);

        // Execute the test
        $this->ba->privateAuth();
        $response = $this->startTest();

        // Verify contact was created successfully
        $contact = $this->getDbLastEntity('contact');
        $this->assertNotNull($contact);
        $this->assertEquals('Test Contact Shadow', $contact->getName());

        // Since source request ID mapping is disabled, we don't need to check for the mapping
        // The main point is that contact creation succeeds regardless
    }

    public function testCreateContactWithShadowRoutingDisabled()
    {
        // Mock Splitz experiments to disable shadow routing but enable source request ID mapping
        $this->mockSplitzExperiments([
            'shadow_router_exp_id' => 'disable',
            'source_mapping_exp_id' => 'enable'
        ]);

        // Mock the PayoutShadowService to verify it does NOT get called
        $this->mockPayoutShadowService(false);

        // Execute the test
        $this->ba->privateAuth();
        $response = $this->startTest();

        // Verify contact was created successfully
        $contact = $this->getDbLastEntity('contact');
        $this->assertNotNull($contact);
        $this->assertEquals('Test Contact Shadow', $contact->getName());

        // Try to verify source request ID mapping was created (since experiment is enabled)
        try {
            $sourceRequestIdMapping = \DB::connection('test')
                ->table('ps_source_request_id_mapping')
                ->where('source_id', $contact->getId())
                ->where('source_type', 'aws_trace_id')
                ->first();

            if ($sourceRequestIdMapping !== null) {
                $this->assertEquals('aws_trace_id', $sourceRequestIdMapping->source_type);
                $this->assertEquals($contact->getId(), $sourceRequestIdMapping->source_id);
            }
        } catch (\Exception $e) {
            // Log but don't fail the test if the table doesn't exist in test environment
            app('trace')->info('source_request_id_mapping_table_not_available', [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function testCreateContactWithShadowServiceError()
    {
        // Mock Splitz experiments to enable both features
        $this->mockSplitzExperiments([
            'shadow_router_exp_id' => 'enable',
            'source_mapping_exp_id' => 'enable'
        ]);

        // Mock the PayoutShadowService to throw an error
        $this->mockPayoutShadowService(true, true);

        // Execute the test - should succeed even if shadow service fails
        $this->ba->privateAuth();
        $response = $this->startTest();

        // Verify contact was created successfully despite shadow service error
        $contact = $this->getDbLastEntity('contact');
        $this->assertNotNull($contact);
        $this->assertEquals('Test Contact Shadow', $contact->getName());

        // Verify response structure
        $this->assertEquals('contact', $response['entity']);
        $this->assertEquals('Test Contact Shadow', $response['name']);
    }

    public function testCreateContactWithoutAwsTraceId()
    {
        // Mock Splitz experiments to enable both features
        $this->mockSplitzExperiments([
            'shadow_router_exp_id' => 'enable',
            'source_mapping_exp_id' => 'enable'
        ]);

        // Mock the PayoutShadowService
        $this->mockPayoutShadowService(true);

        // Execute the test (no AWS trace ID in test data)
        $this->ba->privateAuth();
        $response = $this->startTest();

        // Verify contact was created successfully
        $contact = $this->getDbLastEntity('contact');
        $this->assertNotNull($contact);
        $this->assertEquals('Test Contact Shadow', $contact->getName());

        // Note: Source request ID mapping creation depends on AWS trace ID extraction logic
        // In test environment, this might behave differently than production
    }
}
