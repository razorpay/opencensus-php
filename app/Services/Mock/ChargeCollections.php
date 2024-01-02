<?php


namespace RZP\Services\Mock;

use RZP\Services\ChargeCollections as BaseChargeCollections;

class ChargeCollections extends BaseChargeCollections
{
    public function getReceiptForInvoice(array $input, $requestHeaders = [])
    {
        $response = [];
        $response['items'] = [];
        $lineItem1 =  [
            'name' => 'Line Item 1',
            'amount' => 0,
            'tax' => 0,
        ];

        array_push($response['items'], $lineItem1);
        return $response;
    }
}
