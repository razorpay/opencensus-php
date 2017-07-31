<?php

namespace RZP\Tests\Functional\Helpers\Risk;

use Requests;

/**
 * IMPORT requestResponseTrait explicitly
 * All the helper functions for risk handling of payments
 */
trait PaymentRiskTrait
{
    protected function markPaymentAsConfirmedFraud(
        string $riskId, string $comments)
    {
        $request = [
            'url' => '/risk/' . $riskId,
            'method' => 'PUT',
            'input' => [
                'comments' => 'Entry Confirmed as fraud',
                'source'   => 'Test Cases',
                'type'     => 'confirmed',
            ],
        ];

        $this->ba->appAuth();

        return $this->makeRequestAndContent($request);
    }
}
