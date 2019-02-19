<?php

namespace RZP\Tests\P2p\Service\Base\Traits;

trait AssertionTrait
{
    public function assertCollection(array $collection, int $count, array $items = null)
    {
        $this->assertArrayHasKey('entity', $collection);
        $this->assertSame('collection', $collection['entity']);

        $this->assertArrayHasKey('count', $collection);
        $this->assertSame($count, $collection['count']);

        $this->assertArrayHasKey('items', $collection);
        $this->assertCount($count, $collection['items']);

        if (is_array($items) === true)
        {
            foreach ($collection['items'] as $index => $item)
            {
                $this->assertArraySubset($items[$index], $item);
            }
        }
    }
}
