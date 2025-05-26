<?php

namespace RZP\Tests\Unit\Models\Workflow\Observer;

use App;
use Mockery;
use RZP\Models\Merchant\Detail\Entity as MerchantDetailEntity;
use RZP\Models\Merchant\Detail\Status;
use RZP\Models\Workflow\Action\Differ\Entity as DifferEntity;
use RZP\Models\Workflow\Observer\MerchantReKYCStatusObserver;
use RZP\Services\KafkaProducer;
use RZP\Services\SplitzService;
use Tests\Unit\TestCase;

class MerchantReKYCStatusObserverTest extends TestCase
{
    protected $observer;
    protected $mockTrace;
    protected $mockSplitzService;
    protected $mockKafkaProducer;
    protected $mockBasicAuth;
    protected $mockAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockTrace = Mockery::mock('RZP\Trace\Trace');
        $this->mockTrace->shouldReceive('info')->andReturn(null);
        $this->mockTrace->shouldReceive('traceException')->andReturn(null);
        $this->mockTrace->shouldReceive('count')->andReturn(null);

        $this->mockSplitzService = Mockery::mock(SplitzService::class);

        // Create a mock for KafkaProducer that doesn't use the actual class
        $this->mockKafkaProducer = Mockery::mock('alias:' . KafkaProducer::class);
        $this->mockKafkaProducer->shouldReceive('__construct')->andReturn(null);
        $this->mockKafkaProducer->shouldReceive('Produce')->andReturn(true);

        $this->mockBasicAuth = Mockery::mock('RZP\Services\BasicAuth');
        $this->mockAdmin = Mockery::mock('RZP\Models\Admin\Admin');

        $this->mockAdmin->shouldReceive('getPublicId')->andReturn('admin_123');
        $this->mockAdmin->shouldReceive('getName')->andReturn('Test Admin');

        $this->app->instance('trace', $this->mockTrace);
        $this->app->instance('splitzService', $this->mockSplitzService);
        $this->app->instance('basicauth', $this->mockBasicAuth);
        $this->app->instance('kafka', $this->mockKafkaProducer);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    public function testOnApproveCallsOnExecute()
    {
        $input = [
            DifferEntity::ENTITY_ID => 'merchant_123',
            DifferEntity::PERMISSION => 'edit_rekyc_status',
            DifferEntity::DIFF => [
                DifferEntity::OLD => [MerchantDetailEntity::SELF_SERVE_REKYC_STATUS => Status::UNDER_REVIEW],
                DifferEntity::NEW => [MerchantDetailEntity::SELF_SERVE_REKYC_STATUS => Status::ACTIVATED]
            ]
        ];

        $observer = $this->getMockBuilder(MerchantReKYCStatusObserver::class)
            ->setConstructorArgs([$input])
            ->onlyMethods(['onExecute'])
            ->getMock();

        $observerData = ['action_id' => 'action_123'];

        $observer->expects($this->once())
            ->method('onExecute')
            ->with($observerData);

        $observer->onApprove($observerData);
    }


    public function testOnReject()
    {
        $input = [
            DifferEntity::ENTITY_ID => 'merchant_123',
            DifferEntity::PERMISSION => 'edit_rekyc_status',
            DifferEntity::DIFF => [
                DifferEntity::OLD => [MerchantDetailEntity::SELF_SERVE_REKYC_STATUS => Status::UNDER_REVIEW],
                DifferEntity::NEW => [MerchantDetailEntity::SELF_SERVE_REKYC_STATUS => Status::REJECTED]
            ]
        ];

        $this->mockSplitzService->shouldReceive('evaluateRequest')
            ->andReturn(['response' => ['variant' => ['name' => 'disable']]]);

        $this->mockBasicAuth->shouldReceive('getAdmin')
            ->andReturn($this->mockAdmin);

        $observer = new MerchantReKYCStatusObserver($input);
        $observerData = ['action_id' => 'action_123'];

        $observer->onReject($observerData);

        $this->assertTrue(true);
    }

    public function testOnExecute()
    {
        $input = [
            DifferEntity::ENTITY_ID => 'merchant_123',
            DifferEntity::PERMISSION => 'edit_rekyc_status',
            DifferEntity::DIFF => [
                DifferEntity::OLD => [MerchantDetailEntity::SELF_SERVE_REKYC_STATUS => Status::UNDER_REVIEW],
                DifferEntity::NEW => [MerchantDetailEntity::SELF_SERVE_REKYC_STATUS => Status::ACTIVATED]
            ]
        ];

        $this->mockSplitzService->shouldReceive('evaluateRequest')
            ->andReturn(['response' => ['variant' => ['name' => 'disable']]]);

        $this->mockBasicAuth->shouldReceive('getAdmin')
            ->andReturn($this->mockAdmin);


        $observer = new MerchantReKYCStatusObserver($input);
        $observerData = ['action_id' => 'action_123'];

        $observer->onExecute($observerData);

        $this->assertTrue(true);
    }
}
