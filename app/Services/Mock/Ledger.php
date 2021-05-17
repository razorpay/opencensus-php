<?php

namespace RZP\Services\Mock;

use RZP\Services\Ledger as BaseLedger;

class Ledger extends BaseLedger
{
    /**
     * @param      $input
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     */
    public function createAccount($input, bool $throwExceptionOnFailure = false): array
    {
        $response =  [
            "merchant_id"       => "sampleMerchant",
            "status"            => "IN_REVIEW",
            "name"              => "test name",
            "parent_account_id" => "Parent00000002",
            "currency"          => "INR",
            "description"       => "sample description",
            "account_category"  => "asset",
            "business_category" => "nominal",
            "entities"          => [
                "product" => ["card"]
            ]
        ];

        return [
            'code' => 200,
            'body' => $response
        ];
    }

    /**
     * @param      $input
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     */
    public function createAccountsInBulk($input, bool $throwExceptionOnFailure = false): array
    {
        $response = [
                    "accounts" => [
                    [
                        "merchant_id"       => "sampleMerchant",
                        "status"            => "IN_REVIEW",
                        "name"              => "test name",
                        "parent_account_id" => "Parent00000002",
                        "currency"          => "INR",
                        "description"       => "sample description",
                        "account_category"  => "asset",
                        "business_category" => "nominal",
                        "entities"          => [
                            "product" => ["card"]
                        ]
                    ],
                    ]
                ];

        return [
            'code' => 200,
            'body' => $response
        ];
    }

    /**
     * @param      $input
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     */
    public function activateAccount($input, bool $throwExceptionOnFailure = false): array
    {
        $response =  [
            "merchant_id"       => "sampleMerchant",
            "status"            => "LIVE",
            "name"              => "test name",
            "parent_account_id" => "Parent00000002",
            "currency"          => "INR",
            "description"       => "sample description",
            "account_category"  => "asset",
            "business_category" => "nominal",
            "entities"          => [
                "product" => ["card"]
            ]
        ];

        return [
            'code' => 200,
            'body' => $response
        ];
    }

    /**
     * @param      $input
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     */
    public function updateAccountDetail($input, bool $throwExceptionOnFailure = false): array
    {
        $response =  [
            "id"                => "sampleAccountD",
            "account_id"        => "1Zqmt8zp2EnDsaar",
            "merchant_id"       => "sampleMerchant",
            "account_name"      => "test name",
            "parent_account_id" => "Parent00000002",
            "currency"          => "INR",
            "description"       => "sample description",
            "account_category"  => "asset",
            "business_category" => "nominal",
            "entities"          => [
                "product" => ["card"]
            ]
        ];

        return [
            'code' => 200,
            'body' => $response
        ];
    }
}
