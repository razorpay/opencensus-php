<?php

namespace RZP\Tests\P2p\Service\Base\Traits;

use RZP\Tests\P2p\Service\Base\P2pHelper;
use Illuminate\Testing\TestResponse;

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

    public function assertUpiPinSet(bool $set, $bankAccount)
    {
        $this->assertSame($set, $bankAccount['creds']['upipin']['set']);
    }

    public function withFailureResponse(P2pHelper $helper, callable $callback = null, $status = 400)
    {
        $helper->withFailureResponse(
            function(TestResponse $response) use ($callback, $status)
            {
                $this->assertArrayHasKey('error', $response->json());
                $this->assertCount(1, $response->json());
                $response->assertStatus($status);

                if (is_callable($callback))
                {
                    $callback($response->json()['error'], $response);
                }
            });
    }
}
