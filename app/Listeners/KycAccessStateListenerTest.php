<?php
namespace Tests\Unit\Listeners;

use Mockery;
use ReflectionClass;
use Tests\Unit\TestCase;
use RZP\Listeners\KycAccessStateListener;
use RZP\Models\Partner\KycAccessState;
use RZP\Tests\Traits\MocksSplitz;
use Razorpay\Trace\Logger as Trace;
use RZP\Tests\Unit\Request\Traits\HasRequestCases;

class KycAccessStateListenerTest extends TestCase
{
    use HasRequestCases;
    use MocksSplitz;


    protected $listener;
    protected $trace;
    protected $pkycCore;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trace = Mockery::mock(Trace::class);

        $this->listener = new KycAccessStateListener();

        $reflection = new ReflectionClass($this->listener);

        $traceProperty = $reflection->getProperty('trace');
        $traceProperty->setAccessible(true);
        $traceProperty->setValue($this->listener, $this->trace);
    }

    public function testOnSavedSuccess()
    {
        $entityMock = Mockery::mock(KycAccessState\Entity::class, \ArrayAccess::class);
        $eventMock = Mockery::mock(KycAccessState\EventSaved::class);
        $eventMock->entity = $entityMock;

        $entityMock->shouldReceive('offsetGet')
            ->with(KycAccessState\Entity::ENTITY_ID)
            ->andReturn('entity_123');

        $entityMock->shouldReceive('offsetGet')
            ->with(KycAccessState\Entity::PARTNER_ID)
            ->andReturn('partner_456');

        $entityMock->shouldReceive('toArray')->andReturn([]);

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];
        $splitzMock = $this->getSplitzMock();

        $splitzMock->shouldReceive('evaluateRequest')->zeroOrMoreTimes()->with(Mockery::hasKey('experiment_id'))->with(Mockery::hasValue('Q5tllvcwc6BZlW'))->andReturn($output);



        $this->trace->shouldReceive('count')->atLeast()->once();


        $this->trace->shouldReceive('traceException')->zeroOrMoreTimes();

        $partnershipServiceMock = Mockery::mock('overload:RZP\Services\Partnerships\PartnershipsService');
        
        $partnershipServiceMock->shouldReceive('upsertPartnerKycAccessState')->once();

        $this->listener->onSaved($eventMock);

        $this->assertTrue(true);

    }



    public function testOnDeletedSuccess()
    {

        $entityMock = Mockery::mock(KycAccessState\Entity::class, \ArrayAccess::class);
        $eventMock = Mockery::mock(KycAccessState\EventDeleted::class);
        $eventMock->entity = $entityMock;

        $entityMock->shouldReceive('offsetGet')
            ->with(KycAccessState\Entity::PARTNER_ID)
            ->andReturn('partner_456');

        $entityMock->shouldReceive('toArray')->andReturn([]);
        $output = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];
        $splitzMock = $this->getSplitzMock();

        $splitzMock->shouldReceive('evaluateRequest')->zeroOrMoreTimes()->with(Mockery::hasKey('experiment_id'))->with(Mockery::hasValue('Q5tllvcwc6BZlW'))->andReturn($output);

        $this->trace->shouldReceive('count')->atLeast()->once();
        $this->trace->shouldReceive('traceException')->zeroOrMoreTimes();

        $partnershipServiceMock = Mockery::mock('overload:RZP\Services\Partnerships\PartnershipsService');
        $partnershipServiceMock->shouldReceive('deletePartnerKycAccessState')->once();
        $this->listener->onDeleted($eventMock);
        $this->assertTrue(true);
    }


    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
