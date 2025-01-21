<?php

namespace RZP\Services\Mock;

use RZP\Services\Route\Api as BaseRoute;

class Route extends BaseRoute
{
    protected function fetchTransferByIdInternalRequest(string $transferId, string $merchantId = null): array
    {
        return [
            'id' => $transferId,
            'amount' => 1000,
            'status' => 'processed',
            'source_type' => 'merchant',
            'source_id' => '10000000000000',
            'merchant_id' => '10000000000000',
            'to_id' => '10000000000001',
            'to_type' => 'merchant',
            'recipient_settlement_id' => 'P1aV1cjfsJuNf9',
            'settlement_status' => 'settled'
        ];
    }

    protected function fetchPaymentByIdInternalRequest(string $paymentId): array
    {
        return [
            'id' => $paymentId,
            'amount' => 1000,
            'merchant_id' => '10000000000001',
            'status' => 'captured',
            'transfer_id' => 'P1aV1cjfsJuNf9',
        ];
    }

    protected function fetchPaymentByTransferIdAndAccountIdInternalRequest(string $transferId, string $accountId): array
    {
        return [
            'id' => 'dummypayment001',
            'amount' => 1000,
            'merchant_id' => $accountId,
            'status' => 'captured',
            'transfer_id' => $transferId,
        ];
    }

    public function createDirectTransfer($input): array
    {
        return [
            "id"             => "trf_P1aV1cjfsJuNf9",
            "entity"         => "transfer",
            "status"         => "processed",
            "source"         => "acc_10000000000001",
            "recipient"      => $input['account'],
            "amount"         => $input['amount'],
            "currency"       => $input['currency'],
            "created_at"     => "1727308444",
            "processed_at"   => "1727308444",
        ];
    }

    public function saveApiPayment(string $paymentId, array $input): array
    {
        return [

        ];
    }
};
