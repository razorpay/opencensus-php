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

    /**
     * @param      $input
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     */
    public function fetch($input, bool $throwExceptionOnFailure = false): array
    {
        $response =  [
            "entity" => [
                "AccountDetail" => [
                    "account_category"  => "asset",
                    "account_id"        => "GZu5smDfbM0xhV",
                    "account_name"      => "test name 3",
                    "business_category" => "nominal",
                    "created_at"        => 1612967233,
                    "currency"          => "INR",
                    "deleted_at"        => [
                        "Int64" => 0,
                        "Valid" => false
                    ],
                    "description"       => "sample description",
                    "entities"          => [
                        "product" => ["card"]
                    ],
                    "id"                => "GZu5smNQhUwQoA",
                    "merchant_id"       => "sampleMerchant",
                    "parent_account_id" => "Parent00000001",
                    "updated_at"        => 1612967233
                ]
            ],
            "balance"           => 0,
            "created_at"        => 1612967233,
            "deleted_at"        => [
                "Int64" => 0,
                "Valid" => false
            ],
            "id"                => "GZu5smDfbM0xhV",
            "merchant_id"       => "sampleMerchant",
            "min_balance"       => null,
            "negative_balance"  => null,
            "state"             => "",
            "state_change_logs" => null,
            "status"            => "IN_REVIEW",
            "updated_at"        => 1612967233
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
    public function fetchMultiple($input, bool $throwExceptionOnFailure = false): array
    {
        $response =  [
            "entities" => [
                "accounts" => [
                    [
                        "AccountDetail" => [
                            "account_category"  => "asset",
                            "account_id"        => "GZu5smDfbM0xhV",
                            "account_name"      => "test name 3",
                            "business_category" => "nominal",
                            "created_at"        => 1612967233,
                            "currency"          => "INR",
                            "deleted_at"        => [
                                "Int64" => 0,
                                "Valid" => false
                            ],
                            "description"       => "sample description",
                            "entities"          => [
                                "product" => ["card"]
                            ],
                            "id"                => "GZu5smNQhUwQoA",
                            "merchant_id"       => "sampleMerchant",
                            "parent_account_id" => "Parent00000001",
                            "updated_at"        => 1612967233
                        ],
                        "balance"           => 0,
                        "created_at"        => 1612967233,
                        "deleted_at"        => [
                            "Int64" => 0,
                            "Valid" => false
                        ],
                        "id"                => "GZu5smDfbM0xhV",
                        "merchant_id"       => "sampleMerchant",
                        "min_balance"       => null,
                        "negative_balance"  => null,
                        "state"             => "",
                        "state_change_logs" => null,
                        "status"            => "IN_REVIEW",
                        "updated_at"        => 1612967233
                    ],
                ]
            ]
        ];

        return [
            'code' => 200,
            'body' => $response
        ];
    }

    public function fetchFilter($input, bool $throwExceptionOnFailure = false): array
    {
        $response = [
            "status" => [
                "LIVE",
                "IN_REVIEW",
                "ARCHIVED",
                "SUSPENDED"
            ],
            "account_category" => [
                "asset",
                "liability",
                "revenue",
                "equity",
                "gain",
                "loss",
                "expense"
            ],
            "business_category" => [
                "real",
                "nominal",
                "personal"
            ],
            "transactor_types" => [
                "fund_loading_processed",
                "fund_loading_expired",
                "payout_initiated",
                "payout_processed",
                "payout_failed",
                "payout_reversed",
                "fav_initiated",
                "fav_processed",
                "fav_reversed",
                "fav_failed",
                "positive_adjustment_processed",
                "negative_adjustment_processed"
            ],
            "currency" => [
                "INR"
            ],
        ];

        return [
            'code' => 200,
            'body' => $response
        ];
    }

    public function fetchAdminFormFieldOptions($input, bool $throwExceptionOnFailure = false): array
    {
        $response = [
            "parent_account" => [
                "account_category" => [
                    "info" => "The categories into which transactions are classified are called account categories.",
                    "label" => "Account Category",
                    "type" => "array",
                    "values" => [
                        [
                            "name" => "asset"
                        ],
                        [
                            "name" => "liability"
                        ],
                        [
                            "name" => "revenue"
                        ],
                        [
                            "name" => "equity"
                        ],
                        [
                            "name" => "gain"
                        ],
                        [
                            "name" => "loss"
                        ],
                        [
                            "name" => "expense"
                        ],
                    ]
                ],
                "account_type" => [
                    "info" => "Sub-type of Account for further categorisation of account type.",
                    "label" => "Account Type",
                    "type" => "array",
                    "values" => [
                        [
                            "name" => "payable"
                        ],
                        [
                            "name"=> "receivable"
                        ],
                        [
                            "name" => "adjustment"
                        ],
                        [
                            "name" => "cash"
                        ]
                    ]
                ],
                "business_category" => [
                    "info" => "Accounts are classified into 3 business categories as per accounting principles.",
                    "label" => "Business Category",
                    "type" => "array",
                    "values" => [
                        [
                            "name" => "real"
                        ],
                        [
                            "name" => "nominal"
                        ],
                        [
                            "name" => "personal"
                        ],
                    ]
                ],
                "currency" => [
                    "info" => "Currency in which the transaction on this account is recorded. Currently only 'INR' is defined.",
                    "label" => "Currency",
                    "type" => "array",
                    "values" => [
                        [
                            "name" => "INR"
                        ],
                    ]
                ],
                "description" => [
                    "info" => "Description can be used to add further details on what this account is used for, it's functions etc.",
                    "label" => "Description",
                    "type" => "string",
                ],
                "fund_account_type" => [
                    "info"  => "Fund Account Type is used to further classify the accounts based on the origin of the funds.",
                    "label" => "Fund Account Type",
                    "type" => "array",
                    "values" => [
                        [
                            "name" => "merchant_va"
                        ],
                        [
                            "name" => "merchant_va_vendor"
                        ],
                        [
                            "name" => "nodal"
                        ],
                        [
                            "name" => "current"
                        ],
                        [
                            "name" => "adjustment"
                        ],
                        [
                            "name" => "amazonpay"
                        ],
                        [
                            "name" => "m2p"
                        ],
                        [
                            "name" => "reward"
                        ],
                        [
                            "name" => "va_gst"
                        ],
                    ]
                ],
                "name" => [
                    "info" => "Account Name is the name of the account.",
                    "label" => "Account Name",
                    "type" => "string",
                ],
                "tenant" => [
                    "info" => "A BU is considered as a Tenant in the context of Razorpay. For ex - X, PG, Capital etc.",
                    "label" => "Tenant",
                    "type" => "array",
                    "values" => [
                        [
                            "name" => "X"
                        ]
                    ]
                ]
            ],
            "sub_account" => [
                "identifiers" => [
                    "info" => "Identifiers are the set of entities which can be used to identify the sub accounts.",
                    "label" => "Identifiers",
                    "type" => "array",
                    "values" => [
                        [
                            "name" => "fts_fund_account_id"
                        ],
                        [
                            "name" => "fts_account_type"
                        ],
                        [
                            "name" => "terminal_id"
                        ],
                        [
                            "name" => "terminal_account_type"
                        ],
                        [
                            "name" => "banking_account_id"
                        ]
                    ]
                ],
                "minimum_balance" => [
                    "info" => "Minimum Balance is the minimum balance that has to be maintained by the account. By default, minimum balance is 0.",
                    "label" => "Minimum Balance",
                    "type" => "string"
                ],
                "negative_balance" => [
                    "info" => "Negative Balance is the negative balance maintained by the account.",
                    "label" => "Negative Balance",
                    "type" => "string"
                ],
                "parent_account" => [
                    "info" => "Parent Account is the account under which this sub-account gets created. This sub-account would inherit the properties of the parent account it is associated with.",
                    "label" => "Parent Account",
                    "type" => "array",
                    "values" => [
                        [
                            "id" => "GZtinZSckaoVqD",
                            "name" => "Parent Account 1"
                        ],
                        [
                            "id" => "GZu5smNQhUwQoA",
                            "name" => "Parent Account 2"
                        ]
                    ]
                ],
                "tenant" => [
                    "info" => "A BU is considered as a Tenant in the context of Razorpay. For ex - X, PG, Capital etc.",
                    "label" => "Tenant",
                    "type" => "array",
                    "values" => [
                        [
                            "name" => "X"
                        ]
                    ]
                ]
            ],
        ];

        return [
            'code' => 200,
            'body' => $response
        ];
    }
}
