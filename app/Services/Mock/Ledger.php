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

    /**
     * @param      $input
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     */
    public function requestGovernor($input, bool $throwExceptionOnFailure = false): array
    {
        $response = [
            "code"                      => "200",
            "response"                  => [
                "additional_attribute" => [
                    [
                        "name"  => "transaction_config_id",
                        "value" => "t0"
                    ]
                ],
                "created_at"            => "2021-05-17T09:27:12.047901+05:30",
                "created_by"            => "sampleUser",
                "default_expression"    => null,
                "description"           => "",
                "expression"            => [
                    "operands" => [
                        [
                            "operands" => null,
                            "type" => "variable",
                            "value" => '$sampleVariable'
                        ],
                        [
                            "operands" => null,
                            "type" => "string",
                            "value" => "sampleValue"
                        ]
                    ],
                    "type" => "comparator",
                    "value" => "=="
                ],
                "id"                    => "sample14charId",
                "indexable"             => true,
                "mode"                  => "test",
                "name"                  => "sample_rule",
                "score"                 => 1,
                "skip_on_failure"       => false,
                "updated_at"            => "2021-05-17T09:27:12.047901+05:30"
            ],
        ];

        return [
            'code' => 200,
            'body' => $response
        ];
    }
}
