<?php

namespace RZP\Services\Mock;

use App;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Services\Ledger as BaseLedger;
use RZP\Models\Transaction\Processor\Ledger\Payout;
use RZP\Models\Transaction\Processor\Ledger\Adjustment;
use RZP\Models\Transaction\Processor\Ledger\FundLoading;
use RZP\Models\Transaction\Processor\Ledger\FundAccountValidation;

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
    public function createAccountsOnEvent($input, bool $throwExceptionOnFailure = false): array
    {
        $response = [
            "accounts" => [
                "shared_account_onboarding" => [
                    [
                        "AccountDetail" => [
                            "account_category"  => "asset",
                            "account_id"        => "GZu5smDfbM0xhV",
                            "account_name"      => "test name 4",
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
                        "status"            => "ACTIVATED",
                        "updated_at"        => 1612967233
                    ],
                ],
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
    public function deactivateAccount($input, bool $throwExceptionOnFailure = false): array
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
    public function archiveAccount($input, bool $throwExceptionOnFailure = false): array
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
    public function updateAccount($input, bool $throwExceptionOnFailure = false): array
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
    public function updateAccountByEntitiesAndMerchantID($input, bool $throwExceptionOnFailure = false): array
    {
        $response =  [
            "merchant_id"       => $input['merchant_id'],
            "balance"           => $input['balance'],
            "status"            => "IN_REVIEW",
            "name"              => "test name",
            "parent_account_id" => "Parent00000002",
            "currency"          => "INR",
            "description"       => "sample description",
            "account_category"  => "asset",
            "business_category" => "nominal",
            "entities"          => $input['entities'],
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
    public function createJournal($input, bool $throwExceptionOnFailure = false): array
    {
        $presentTime      = (string) Carbon::now(Timezone::IST)->timestamp;
        $app              = App::getFacadeRoot();
        $merchant         = $app['repo']->merchant->find($input['merchant_id']);
        $bankingAccountId = $merchant->bankingAccounts->first()->getId();

        if (strpos($input['transactor_event'], 'fav') !== false)
        {
            if (strpos($input['transactor_event'], 'failed') !== false and
                $input['commission'] !== '0')
            {
                $balance = $app['repo']->reversal->findByPublicId($input['transactor_id'])->entity->balance->getBalance();
            }
            else
            {
                $balance = $app['repo']->fund_account_validation->findByPublicId($input['transactor_id'])->balance->getBalance();
            }
        }
        else if (strpos($input['transactor_event'], 'payout') !== false)
        {
            if ((strpos($input['transactor_event'], 'failed') !== false) or
                (strpos($input['transactor_event'], 'reversed') !== false))
            {
                $balance = $app['repo']->reversal->findByPublicId($input['transactor_id'])->entity->balance->getBalance();
            }
            else
            {
                $balance = $app['repo']->payout->findByPublicId($input['transactor_id'])->balance->getBalance();
            }
        }
        else if (strpos($input['transactor_event'], 'adjustment') !== false)
        {
            $balance = $app['repo']->adjustment->findByPublicId($input['transactor_id'])->balance->getBalance();
        }
        else if (strpos($input['transactor_event'], 'fund_loading') !== false)
        {
            // credits fund loading
            if (isset($input['additional_params']) === true)
            {
                $additional_params = $input['additional_params'];
                if ((isset($additional_params['fee_accounting']) === true) && $additional_params['fee_accounting'] === 'reward')
                {
                    $balance = 0;
                }
                else
                {
                    $balance = $app['repo']->bank_transfer->findByPublicId($input['transactor_id'])->balance->getBalance();
                }
            }
            else
            {
                $balance = $app['repo']->bank_transfer->findByPublicId($input['transactor_id'])->balance->getBalance();
            }
        }
        else
        {
            $balance = $merchant->sharedBankingBalance->getBalance();
        }

        // Cases when merchant balance will increase
        $merchantBalanceCreditEvents = [
            Adjustment::POSITIVE_ADJUSTMENT_PROCESSED,
            FundLoading::FUND_LOADING_PROCESSED,
            FundAccountValidation::FAV_FAILED,
            Payout::PAYOUT_REVERSED,
            Payout::INTER_ACCOUNT_PAYOUT_REVERSED,
            Payout::PAYOUT_FAILED,
            Payout::INTER_ACCOUNT_PAYOUT_FAILED,
        ];

        // Cases where there will be no change in merchant balance
        $merchantBalanceNoChangeEvents = [
            FundAccountValidation::FAV_REVERSED,
            FundAccountValidation::FAV_PROCESSED,
            Payout::PAYOUT_PROCESSED,
            Payout::INTER_ACCOUNT_PAYOUT_PROCESSED,
        ];

        $isCredit = false;

        if (in_array($input['transactor_event'], $merchantBalanceCreditEvents, true) === true)
        {
            $isCredit = true;
        }

        $balanceDelta = $input['amount'];

        if (strpos($input['transactor_event'], 'fav') !== false)
        {
            $balanceDelta = $input['commission'];
        }

        if (in_array($input['transactor_event'], $merchantBalanceNoChangeEvents, true) === true)
        {
            $balanceDelta = 0;
        }

        $journalId = \RZP\Models\Base\UniqueIdEntity::generateUniqueId();

        $response = [
            'id'               => $journalId,
            'created_at'       => $presentTime,
            'updated_at'       => $presentTime,
            'amount'           => $input['amount'],
            'base_amount'      => $input['base_amount'],
            'currency'         => $input['currency'],
            'tenant'           => $input['tenant'] ?? 'X',
            'transactor_id'    => $input['transactor_id'],
            'transactor_event' => $input['transactor_event'],
            'transaction_date' => $input['transaction_date'],
            'ledger_entry'     => [
                [
                    'id'               => \RZP\Models\Base\UniqueIdEntity::generateUniqueId(),
                    'created_at'       => $presentTime,
                    'updated_at'       => $presentTime,
                    'merchant_id'      => $input['merchant_id'],
                    'journal_id'       => $journalId,
                    'account_id'       => \RZP\Models\Base\UniqueIdEntity::generateUniqueId(),
                    'amount'           => $input['amount'],
                    'base_amount'      => $input['base_amount'],
                    'type'             => $isCredit ? 'debit' : 'credit',
                    'currency'         => $input['currency'],
                    'balance'          => '',
                    'account_entities' => [
                        'account_type'      => ['cash'],
                        'fund_account_type' => ['adjustment'], //TODO: Determine how to correct this
                        'transactor'        => [$input['tenant'] ?? 'X'],
                    ],
                ],
                [
                    'id'               => \RZP\Models\Base\UniqueIdEntity::generateUniqueId(),
                    'created_at'       => $presentTime,
                    'updated_at'       => $presentTime,
                    'merchant_id'      => $input['merchant_id'],
                    'journal_id'       => $journalId,
                    'account_id'       => \RZP\Models\Base\UniqueIdEntity::generateUniqueId(),
                    'amount'           => $input['amount'],
                    'base_amount'      => $input['base_amount'],
                    'type'             => $isCredit ? 'credit' : 'debit',
                    'currency'         => $input['currency'],
                    'balance'          => $isCredit ? ($balance + $balanceDelta) : ($balance - $balanceDelta),
                    'account_entities' => [
                        'account_type'       => ['payable'],
                        'banking_account_id' => [$bankingAccountId],
                        'fund_account_type'  => ['merchant_va'],
                        'transactor'         => [$input['tenant'] ?? 'X'],
                    ],
                ]
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
    public function fetchByTransactor($input, bool $throwExceptionOnFailure = false): array
    {
        $response =  [
            "id"                => "HNjsypA96SgJKJ",
            "created_at"        => "1623848289",
            "updated_at"        => "1632368730",
            "amount"            => "130.000000",
            "base_amount"       => "130.000000",
            "currency"          => "INR",
            "tenant"            => "X",
            "transactor_id"     => $input['transactor_id'],
            "transactor_event"  => $input['transactor_event'],
            "transaction_date"  => "1611132045",
            "ledger_entry" => [
                [
                    "id"          => "HNjsypHNXdSiei",
                    "created_at"  => "1623848289",
                    "updated_at"  => "1623848289",
                    "merchant_id" => "HN59oOIDACOXt3",
                    "journal_id"  => "HNjsypA96SgJKJ",
                    "account_id"  => "GoRNyEuu9Hl0OZ",
                    "amount"      => "130.000000",
                    "base_amount" => "130.000000",
                    "type"        => "debit",
                    "currency"    => "INR",
                    "balance"     => ""
                ],
                [
                    "id"          => "HNjsypHPOUlxDR",
                    "created_at"  => "1623848289",
                    "updated_at"  => "1623848289",
                    "merchant_id" => "HN59oOIDACOXt3",
                    "journal_id"  => "HNjsypA96SgJKJ",
                    "account_id"  => "HN5AGgmKu0ki13",
                    "amount"      => "130.000000",
                    "base_amount" => "130.000000",
                    "type"        => "credit",
                    "currency"    => "INR",
                    "balance"     => ""
                ]
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
    public function createLedgerConfig($input, bool $throwExceptionOnFailure = false): array
    {
        $response =  [
            "id"                    => "I0u53VkCM4wMMs",
            "tenant"                => "X",
            "transactor_event_name" => "XPositiveAdjustmentProcessed",
            "rule" => [
                "transactor_event"       => "positive_adjustment_processed"
            ],
            "config" => [
                "ledger_entries" => [
                    [
                        "account_discovery_config" => [
                            "account_category"  => "expense",
                            "account_type"      => "cash",
                            "fund_account_type" => "adjustment",
                            "identifiers"       => [],
                        ],
                        "direction" => "debit",
                        "formula"   => "amount",
                    ],
                    [
                        "account_discovery_config" => [
                            "account_category"  => "liability",
                            "account_type"      => "payable",
                            "fund_account_type" => "merchant_va",
                            "identifiers" => [
                                "banking_account_id" => "$"."banking_account_id"
                            ]
                        ],
                        "direction" => "credit",
                        "formula"   => "amount",
                    ]
                ]
            ],
            "created_at" => "1632399455",
            "updated_at" => "1632399455",
            "status"     => ""
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
    public function updateLedgerConfig($input, bool $throwExceptionOnFailure = false): array
    {
        $response =  [
            "id"                    => "I0u53VkCM4wMMs",
            "tenant"                => "X",
            "transactor_event_name" => "XPositiveAdjustmentProcessed2",
            "rule" => [
                "transactor_event"       => "positive_adjustment_processed2"
            ],
            "config" => [
                "ledger_entries" => [
                    [
                        "account_discovery_config" => [
                            "account_category"  => "expense",
                            "account_type"      => "cash",
                            "fund_account_type" => "adjustment",
                            "identifiers"       => [],
                        ],
                        "direction" => "debit",
                        "formula"   => "amount",
                    ],
                    [
                        "account_discovery_config" => [
                            "account_category"  => "liability",
                            "account_type"      => "payable",
                            "fund_account_type" => "merchant_va",
                            "identifiers" => [
                                "banking_account_id" => "$"."banking_account_id"
                            ]
                        ],
                        "direction" => "credit",
                        "formula"   => "amount",
                    ]
                ]
            ],
            "created_at" => "1632399455",
            "updated_at" => "1632399455",
            "status"     => ""
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
    public function deleteLedgerConfig($input, bool $throwExceptionOnFailure = false): array
    {
        $response =  [
            "id"                    => "I0u53VkCM4wMMs",
            "tenant"                => "X",
            "transactor_event_name" => "XPositiveAdjustmentProcessed3",
            "rule" => [
                "transactor_event"       => "positive_adjustment_processed3"
            ],
            "config" => [
                "ledger_entries" => [
                    [
                        "account_discovery_config" => [
                            "account_category"  => "expense",
                            "account_type"      => "cash",
                            "fund_account_type" => "adjustment",
                            "identifiers"       => [],
                        ],
                        "direction" => "debit",
                        "formula"   => "amount",
                    ],
                    [
                        "account_discovery_config" => [
                            "account_category"  => "liability",
                            "account_type"      => "payable",
                            "fund_account_type" => "merchant_va",
                            "identifiers" => [
                                "banking_account_id" => "$"."banking_account_id"
                            ]
                        ],
                        "direction" => "credit",
                        "formula"   => "amount",
                    ]
                ]
            ],
            "created_at" => "1632399455",
            "updated_at" => "1632399455",
            "status"     => ""
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
                "label" => "Status",
                "type" => "array",
                "values" => [
                    [
                        "name" => "ACTIVATED"
                    ],
                    [
                        "name" => "IN_REVIEW"
                    ],
                    [
                        "name" => "ARCHIVED"
                    ],
                    [
                        "name" => "SUSPENDED"
                    ],
                ]
            ],
            "account_category" => [
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
            "business_category" => [
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
            "transactor_events" => [
                "label" => "Transactor Event",
                "type" => "array",
                "values" => [
                    [
                        "name" => "fund_loading_processed"
                    ],
                    [
                        "name" => "fund_loading_expired"
                    ],
                    [
                        "name" => "payout_initiated"
                    ],
                    [
                        "name" => "payout_processed"
                    ],
                    [
                        "name" => "payout_failed"
                    ],
                    [
                        "name" => "payout_reversed"
                    ],
                    [
                        "name" => "fav_initiated"
                    ],
                    [
                        "name" => "fav_processed"
                    ],
                    [
                        "name" => "fav_reversed"
                    ],
                    [
                        "name" => "fav_failed"
                    ],
                    [
                        "name" => "positive_adjustment_processed"
                    ],
                    [
                        "name" => "negative_adjustment_processed"
                    ],
                    [
                        "name" => "penny_expense_settlement"
                    ],
                ]
            ],
            "currency" => [
                "label" => "Currency",
                "type" => "array",
                "values" => [
                    [
                        "name" => "INR"
                    ],
                ]
            ],
        ];

        return [
            'code' => 200,
            'body' => $response
        ];
    }

    public function fetchAccountFormFieldOptions($input, bool $throwExceptionOnFailure = false): array
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

    /**
     * @param      $input
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     */
    public function fetchAccountTypes($input, bool $throwExceptionOnFailure = false): array
    {
        $response = [
            "account_types" => [
                "values" => [
                    [
                        "name" => "payable"
                    ]
                ]
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
    public function fetchFundAccountTypes($input, bool $throwExceptionOnFailure = false): array
    {
        $response = [
            "fund_account_types" => [
                "values" => [
                    [
                        "name" => "merchant_va_vendor"
                    ],
                    [
                        "name" => "merchant_va"
                    ],
                    [
                        "name" => "va_gst"
                    ],
                    [
                        "name" => "reward"
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
                        "name" => "m2p"
                    ],
                    [
                        "name" => "amazonpay"
                    ]
                ]
            ]
        ];

        return [
            'code' => 200,
            'body' => $response
        ];
    }

    public function fetchJournalFormFieldOptions($input, bool $throwExceptionOnFailure = false): array
    {
        $response = [
            "tenant" => [
                "info" => "A BU is considered as a Tenant in the context of Razorpay. For ex - X, PG, Capital etc.",
                "label" => "Tenant",
                "type" => "array",
                "values" => [
                    [
                        "name" => "X"
                    ]
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
            "transactor_events" => [
                "label" => "Transactor Type",
                "type" => "array",
                "values" => [
                    [
                        "name" => "fund_loading_processed"
                    ],
                    [
                        "name" => "fund_loading_expired"
                    ],
                    [
                        "name" => "payout_initiated"
                    ],
                    [
                        "name" => "payout_processed"
                    ],
                    [
                        "name" => "payout_failed"
                    ],
                    [
                        "name" => "payout_reversed"
                    ],
                    [
                        "name" => "fav_initiated"
                    ],
                    [
                        "name" => "fav_processed"
                    ],
                    [
                        "name" => "fav_reversed"
                    ],
                    [
                        "name" => "fav_failed"
                    ],
                    [
                        "name" => "positive_adjustment_processed"
                    ],
                    [
                        "name" => "negative_adjustment_processed"
                    ],
                ]
            ]
        ];

        return [
            'code' => 200,
            'body' => $response
        ];
    }

    public function fetchLedgerConfigFormFieldOptions($input, bool $throwExceptionOnFailure = false): array
    {
        $response = [
            "tenant" => [
                "info" => "A BU is considered as a Tenant in the context of Razorpay. For ex - X, PG, Capital etc.",
                "label" => "Tenant",
                "type" => "array",
                "values" => [
                    [
                        "name" => "X"
                    ]
                ]
            ],
            "rule" => [
                "label" => "Rule",
                "type" => "array",
                "values" => [
                    [
                        "name" => "transactor_event"
                    ],
                    [
                        "name" => "fee_accounting"
                    ],
                ]
            ],
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
            "direction" => [
                "label" => "Direction",
                "type" => "array",
                "values" => [
                    [
                        "name" => "credit"
                    ],
                    [
                        "name" => "debit"
                    ]
                ]
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
    public function fetchMerchantLedgerEntryByID($input, bool $throwExceptionOnFailure = false): array
    {
        $response = [
            "ledger_entry" => [
                "amount"           => "1590",
                "balance"          => "98410",
                "base_amount"      => "1590",
                "created_at"       => 1634027277,
                "currency"         => "INR",
                "journal_id"       => "IWx1NL90G02vxr",
                "ledger_entry_id"  => "I8MJlgVttAs4KQ",
                "merchant_id"      => "10000000000000",
                "transactor_id"    => "pout_payout00000001",
                "transactor_event" => "payout_initiated",
                "type"             => "debit",
                "updated_at"       => 1634027277,
            ],
        ];

        return [
            'code' => 200,
            'body' => $response
        ];
    }

    public function deleteMerchants($input, bool $throwExceptionOnFailure = false): array
    {
        $response = [
             "merchant_ids_not_deleted" => [
                  "sampleMerchant1"
             ],
             "merchant_ids_not_exist" => [
                  "sampleMerchant2"
             ]
        ];

        return [
            'code' => 200,
            'body' => $response
        ];
    }

    public function fetchMerchantAccounts($input, bool $throwExceptionOnFailure = false): array {
        $response = [
            "merchant_id"      => "10000000000000",
            "merchant_balance" => [
                "balance"      => "160.000000",
                "min_balance"  => "10000.000000"
            ],
            "reward_balance"  => [
                "balance"     => "20.000000",
                "min_balance" => "-20.000000"
            ],
        ];

        return [
            'code' => 200,
            'body' => $response
        ];
    }
}
