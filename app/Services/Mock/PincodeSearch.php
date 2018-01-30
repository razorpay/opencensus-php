<?php

namespace RZP\Services\Mock;

use RZP\Services\PincodeSearch as BasePincodeSearcherClient;

class PincodeSearch extends BasePincodeSearcherClient
{
    const TEST_RESPONSE = [
        'status'  => 'ok',
        'records' => [
            [
                "officename"     => "Vasant Kunj S.O",
                "pincode"        => "110070",
                "deliverystatus" => "Delivery",
                "divisionname"   => "New Delhi South West",
                "regionname"     => "Delhi",
                "circlename"     => "Delhi",
                "taluk"          => "NA",
                "districtname"   => "South West Delhi",
                "statename"      => "DELHI"
            ]
        ]
    ];

    public function sendRequest(string $url, string $method, string $data = null): array
    {
        return self::TEST_RESPONSE;
    }
}
