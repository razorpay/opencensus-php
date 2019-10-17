<?php

namespace RZP\Tests\Functional\Helpers\Payout;

trait PayoutTrait
{
    protected function makePayoutQueueSummaryRequest()
    {
        $request = [
            'method'  => 'GET',
            'url'     => '/payouts/queued/amount',
        ];

        $this->ba->proxyAuth();

        $response = $this->sendRequest($request);

        return json_decode($response->getContent(), true);
    }

    protected function dispatchQueuedPayouts()
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/payouts/queued/process',
        ];

        $this->ba->cronAuth();

        $response = $this->sendRequest($request);

        return json_decode($response->getContent(), true);
    }

    protected function retryPayout($id)
    {
        $request = [
            'url' => "/payouts/$id/retry",
            'method' => 'POST',
            'content' => []
        ];

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNotNull($response['id']);
        $this->assertNotEquals($id, $response['id']);
    }
}
