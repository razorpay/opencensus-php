<?php

namespace RZP\Models\Partner\Commission;

/**
 * Class Analytics contains all harvester queries based on query type
 *
 * @package RZP\Models\Partner\Commission
 */
class Analytics
{
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
                        'type' => 'explicit',
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
}
