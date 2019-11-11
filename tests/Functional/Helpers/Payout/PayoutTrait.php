<?php

namespace RZP\Tests\Functional\Helpers\Payout;

trait PayoutTrait
{
    protected function makePayoutSummaryRequest()
    {
        $request = [
            'method'  => 'GET',
            'url'     => '/payouts/_meta/summary',
        ];

        $this->ba->proxyAuth();

        $response = $this->sendRequest($request);

        return json_decode($response->getContent(), true);
    }

    protected function createBankingAccount(array $attributes = [])
    {
        $bankingAccount = $this->fixtures->create('banking_account', [
            'id'                    => $attributes["id"] ?? 'ABCde1234ABCde',
            'account_number'        => $attributes["account_number"] ?? '2224440041626905',
            'account_type'          => $attributes["account_type"] ?? 'current',
            'merchant_id'           => $attributes["merchant_id"] ?? '10000000000000',
            'channel'               => $attributes["channel"] ?? 'rbl',
            'pincode'               => $attributes["pincode"] ?? '1',
            'bank_reference_number' => $attributes["bank_reference_number"] ?? '',
            'balance_id'            => $attributes["balance_id"] ?? '',
            'status'                => 'activated',
        ]);

        return $bankingAccount;
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
