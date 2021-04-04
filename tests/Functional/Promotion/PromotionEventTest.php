<?php

namespace RZP\Tests\Functional\Promotion;

use RZP\Models\Promotion\Event;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class PromotionEventTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/PromotionEventTestData.php';

        parent::setUp();
    }

    public function testCreatePromotionEventTest()
    {
        $data = [
            Event\Entity::NAME           => 'sign up',
            Event\Entity::DESCRIPTION    => 'sign up related credits'
        ];

        $response = $this->makeEvent($data);

        $this->assertEquals('sign up', $response['name']);
    }

    public function testDuplicateEventCreateTest()
    {
        $data = [
            Event\Entity::NAME           => 'sign up',
            Event\Entity::DESCRIPTION    => 'sign up related credits'
        ];

        $response = $this->makeEvent($data);

        $this->assertEquals('sign up', $response['name']);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data);
    }

    public function testFetchEventsTest()
    {
        $data = [
            Event\Entity::NAME           => 'sign up',
            Event\Entity::DESCRIPTION    => 'sign up related credits'
        ];

        $this->makeEvent($data);

        $data = [
            Event\Entity::NAME           => 'L2 activation',
            Event\Entity::DESCRIPTION    => 'L2 activation related credits'
        ];

        $this->makeEvent($data);

        $this->ba->adminAuth('live');

        $request = [
            'content' => [],
            'url'     => '/admin/promotion_event',
            'method'  => 'GET',
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('sign up', $response['items'][1]['name']);
        $this->assertEquals('L2 activation', $response['items'][0]['name']);
    }

    protected function makeEvent(array $data)
    {
        $this->ba->adminAuth('live');

        $request = [
            'content' => $data,
            'url'     => '/promotions/events',
            'method'  => 'post'
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }
}
