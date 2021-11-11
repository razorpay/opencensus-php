<?php

namespace RZP\Tests\Unit\Metro;

use ReflectionClass;
use RZP\Tests\TestCase;
use Mockery\MockInterface;
use RZP\Metro\MetroHandler;
use Google\Cloud\PubSub\Topic;
use Google\Cloud\PubSub\PubSubClient;

class MetroHandlerTest extends TestCase
{
    public function testPublish()
    {
        $mockTopic = $this->mock(Topic::class, function(MockInterface $mock) {
            $mock->shouldReceive('publish')->once();
        });
        $pubSubClientMock = $this->mock(PubSubClient::class, function(MockInterface $mock) use ($mockTopic) {
            $mock->shouldReceive('topic')->andReturn($mockTopic)->once();
        });
        $metroHandler = new MetroHandler();
        $reflection = new ReflectionClass($metroHandler);
        $reflection_property = $reflection->getProperty('pubSubClient');
        $reflection_property->setAccessible(true);

        $reflection_property_mock = $reflection->getProperty('mock');
        $reflection_property_mock->setAccessible(true);

        $reflection_property->setValue($metroHandler, $pubSubClientMock);
        $reflection_property_mock->setValue($metroHandler, false);
        $metroHandler->publish('dummyTopic', ['data' => 'dummyMessage']);
    }
}