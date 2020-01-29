<?php

namespace RZP\Tests\Functional\Helpers\Payout;

use RZP\Models\Admin;
use RZP\Models\Feature\Constants;

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

    protected function createPayoutWithWorkflow($workflow, $payoutAttributes = [])
    {
        $this->app['config']->set('heimdall.workflows.mock', false);
        $this->app['config']->set('heimdall.permissions.payouts.create_payout.assignable', true);

        $workflowDefaultPermissions = (new Admin\Permission\Repository())
                                       ->retrieveIdsByNames([Admin\Permission\Name::CREATE_PAYOUT]);

        // Attach permissions to the default workflow
        $workflow->permissions()->sync($workflowDefaultPermissions);

        return $this->createQueuedOrPendingPayout($payoutAttributes);
    }

    protected function createQueuedOrPendingPayout(array $attributes = [])
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'        => $attributes["account_number"] ?? '2224440041626905',
                'amount'                => $attributes["amount"] ?? 10000,
                'currency'              => 'INR',
                'purpose'               => 'refund',
                'fund_account_id'       => 'fa_100000000000fa',
                'mode'                  => 'NEFT',
                'queue_if_low_balance'  => $attributes["queue_if_low_balance"] ?? 0,
            ],
        ];

        $this->ba->privateAuth();

        $response = $this->sendRequest($request);

        return json_decode($response->getContent(), true);
    }
}
