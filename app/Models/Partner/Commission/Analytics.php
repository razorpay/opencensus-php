<?php

namespace RZP\Models\Partner\Commission;

/**
 * Class Analytics contains all harvester queries based on query type
 *
 * @package RZP\Models\Partner\Commission
 */
class Analytics
{
    const RESULT                    = 'result';
    const VALUE                     = 'value';
    const TOTAL_TAX                 = 'totalTax';
    const TOTAL_COMMISSION_WITH_TAX = 'totalCommissionWithTax';

    public function fetchAnalyticsForAggregateDetailQuery(array $input): array
    {
        return [
            'filters'      => [
                'default'  => [
                    [
                        'created_at' => [
                            'gte' => $input[Constants::FROM],
                            'lte' => $input[Constants::TO],
                        ],
                        'model' => 'commission',
                    ]
                ],
                'implicit' => [
                    [
                        'created_at' => [
                            'gte' => $input[Constants::FROM],
                            'lte' => $input[Constants::TO],
                        ],
                        'type' => 'implicit',
                        'model' => 'commission',
                    ]
                ],
                'explicit' => [
                    [
                        'created_at' => [
                            'gte' => $input[Constants::FROM],
                            'lte' => $input[Constants::TO],
                        ],
                        'type'        => 'explicit',
                        'model'       => 'commission',
                        'record_only' => 0,
                    ]
                ],
                'explicit_record' => [
                    [
                        'created_at' => [
                            'gte' => $input[Constants::FROM],
                            'lte' => $input[Constants::TO],
                        ],
                        'type'        => 'explicit',
                        'model'       => 'commission',
                        'record_only' => 1,
                    ]
                ],
            ],
            'aggregations' => [
                'activeMerchants'   => [
                    'agg_type' => 'cardinality',
                    'details'  => [
                        'index'    => 'commissions',
                        'column'   => 'payments_merchant_id',
                        'group_by' => ['histogram_daily'],
                    ],
                ],
                'transactionVolume' => [
                    'agg_type' => 'sum',
                    'details'  => [
                        'index'    => 'commissions',
                        'column'   => 'payments_base_amount',
                        'group_by' => ['histogram_daily'],
                    ],
                ],
                'transactions'      => [
                    'agg_type' => 'count',
                    'details'  => [
                        'index'    => 'commissions',
                        'column'   => 'id',
                        'group_by' => ['histogram_daily'],
                    ],
                ],
                'baseEarnings'      => [
                    'agg_type'   => 'sum',
                    'filter_key' => 'implicit',
                    'details'    => [
                        'index'    => 'commissions',
                        'column'   => 'commission',
                        'group_by' => ['histogram_daily'],
                    ],
                ],
                'baseTax'           => [
                    'agg_type'   => 'sum',
                    'filter_key' => 'implicit',
                    'details'    => [
                        'index'    => 'commissions',
                        'column'   => 'tax',
                        'group_by' => ['histogram_daily'],
                    ],
                ],
                'addonEarnings'     => [
                    'agg_type'   => 'sum',
                    'filter_key' => 'explicit',
                    'details'    => [
                        'index'    => 'commissions',
                        'column'   => 'commission',
                        'group_by' => ['histogram_daily'],
                    ],
                ],
                'addonTax'          => [
                    'agg_type'   => 'sum',
                    'filter_key' => 'explicit',
                    'details'    => [
                        'index'    => 'commissions',
                        'column'   => 'tax',
                        'group_by' => ['histogram_daily'],
                    ],
                ],
                'addonEarnings_record' => [
                    'agg_type'   => 'sum',
                    'filter_key' => 'explicit_record',
                    'details'    => [
                        'index'    => 'commissions',
                        'column'   => 'commission',
                        'group_by' => ['histogram_daily'],
                    ],
                ],
                'addonTax_record' => [
                    'agg_type'   => 'sum',
                    'filter_key' => 'explicit_record',
                    'details'    => [
                        'index'    => 'commissions',
                        'column'   => 'tax',
                        'group_by' => ['histogram_daily'],
                    ],
                ],
            ],
        ];
    }

    public function fetchAnalyticsForSubventionDetailQuery(array $input): array
    {
        return [
            'filters'      => [
                'default'  => [
                    [
                        'created_at' => [
                            'gte' => $input[Constants::FROM],
                            'lte' => $input[Constants::TO],
                        ],
                        'model' => 'subvention',
                    ]
                ],
                'implicit' => [
                    [
                        'created_at' => [
                            'gte' => $input[Constants::FROM],
                            'lte' => $input[Constants::TO],
                        ],
                        'type' => 'implicit',
                        'model' => 'subvention',
                    ]
                ],
                'explicit' => [
                    [
                        'created_at' => [
                            'gte' => $input[Constants::FROM],
                            'lte' => $input[Constants::TO],
                        ],
                        'type' => 'explicit',
                        'model' => 'subvention',
                    ]
                ],
            ],
            'aggregations' => [
                'activeMerchants'   => [
                    'agg_type' => 'cardinality',
                    'details'  => [
                        'index'    => 'commissions',
                        'column'   => 'payments_merchant_id',
                        'group_by' => ['histogram_daily'],
                    ],
                ],
                'transactionVolume' => [
                    'agg_type' => 'sum',
                    'details'  => [
                        'index'    => 'commissions',
                        'column'   => 'payments_base_amount',
                        'group_by' => ['histogram_daily'],
                    ],
                ],
                'transactions'      => [
                    'agg_type' => 'count',
                    'details'  => [
                        'index'    => 'commissions',
                        'column'   => 'id',
                        'group_by' => ['histogram_daily'],
                    ],
                ],
                'baseEarnings'      => [
                    'agg_type'   => 'sum',
                    'filter_key' => 'implicit',
                    'details'    => [
                        'index'    => 'commissions',
                        'column'   => 'commission',
                        'group_by' => ['histogram_daily'],
                    ],
                ],
                'baseTax'           => [
                    'agg_type'   => 'sum',
                    'filter_key' => 'implicit',
                    'details'    => [
                        'index'    => 'commissions',
                        'column'   => 'tax',
                        'group_by' => ['histogram_daily'],
                    ],
                ],
            ],
        ];
    }

    public function fetchAnalyticsForSubventionDailyQuery(array $input): array
    {
        return [
            'filters'      => [
                'default' => [
                    [
                        'created_at' => [
                            'gte' => $input[Constants::FROM],
                            'lte' => $input[Constants::TO],
                        ],
                        'model' => 'subvention',
                    ]
                ],
            ],
            'aggregations' => [
                'activeMerchants'   => [
                    'agg_type' => 'cardinality',
                    'details'  => [
                        'index'    => 'commissions',
                        'column'   => 'payments_merchant_id',
                        'group_by' => ['histogram_daily'],
                    ],
                ],
                'transactionVolume' => [
                    'agg_type' => 'sum',
                    'details'  => [
                        'index'    => 'commissions',
                        'column'   => 'payments_base_amount',
                        'group_by' => ['histogram_daily'],
                    ],
                ],
                'transactions'      => [
                    'agg_type' => 'count',
                    'details'  => [
                        'index'    => 'commissions',
                        'column'   => 'id',
                        'group_by' => ['histogram_daily'],
                    ],
                ],
                'earnings'          => [
                    'agg_type' => 'sum',
                    'details'  => [
                        'index'    => 'commissions',
                        'column'   => 'commission',
                        'group_by' => ['histogram_daily'],
                    ],
                ],
            ],
        ];
    }

    public function fetchAnalyticsForAggregateDailyQuery(array $input): array
    {
        return [
            'filters'      => [
                'default' => [
                    [
                        'created_at' => [
                            'gte' => $input[Constants::FROM],
                            'lte' => $input[Constants::TO],
                        ],
                        'model' => 'commission',
                    ]
                ],
            ],
            'aggregations' => [
                'activeMerchants'   => [
                    'agg_type' => 'cardinality',
                    'details'  => [
                        'index'    => 'commissions',
                        'column'   => 'payments_merchant_id',
                        'group_by' => ['histogram_daily'],
                    ],
                ],
                'transactionVolume' => [
                    'agg_type' => 'sum',
                    'details'  => [
                        'index'    => 'commissions',
                        'column'   => 'payments_base_amount',
                        'group_by' => ['histogram_daily'],
                    ],
                ],
                'transactions'      => [
                    'agg_type' => 'count',
                    'details'  => [
                        'index'    => 'commissions',
                        'column'   => 'id',
                        'group_by' => ['histogram_daily'],
                    ],
                ],
                'earnings'          => [
                    'agg_type' => 'sum',
                    'details'  => [
                        'index'    => 'commissions',
                        'column'   => 'commission',
                        'group_by' => ['histogram_daily'],
                    ],
                ],
            ],
        ];
    }

    public function fetchAggregateCommissionDetailsQuery(array $input)
    {
        $query = [
            'filters'      => [
                'default' => [
                    [
                        'created_at'           => [
                            'lte' => $input[Constants::TO],
                        ],
                        'model'                => 'commission',
                        'transactions_settled' => 0,
                        'record_only'          => 0,
                    ]
                ],
            ],
            'aggregations' => [
                self::TOTAL_COMMISSION_WITH_TAX => [
                    'agg_type' => 'sum',
                    'details'  => [
                        'index'  => 'commissions',
                        'column' => 'commission',
                    ],
                ],
                self::TOTAL_TAX                 => [
                    'agg_type' => 'sum',
                    'details'  => [
                        'index'  => 'commissions',
                        'column' => 'tax',
                    ],
                ],
            ],
        ];

        if (empty($input[Constants::FROM]) === false)
        {
            $query['filters']['default'][0]['created_at']['gte'] = $input[Constants::FROM];
        }

        return $query;
    }
}
