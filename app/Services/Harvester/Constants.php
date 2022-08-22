<?php

namespace RZP\Services\Harvester;

class Constants
{

    const PINOT_DATA_TYPE_LONG             = 'LONG';
    const PINOT_DATA_TYPE_DOUBLE           = 'DOUBLE';
    const PINOT_DATA_TYPE_STRING           = 'STRING';

    const PINOT_DATA_TYPE_DEFAULT_MAPPING = [
        self::PINOT_DATA_TYPE_LONG         => [-9223372036854776000, -9223372036854775808],
        self::PINOT_DATA_TYPE_DOUBLE       => "-Infinity",
        self::PINOT_DATA_TYPE_STRING       => "null"
    ];

    const PINOT_TABLE_SEGMENT_FACT           = "segment_fact";

    const PINOT_TABLE_PAYMNETS_AUTH_FACT     = "payments_auth_fact";

    const PINOT_TABLE_VIEW_PLUGIN_MERCHANT_FACT  = 'plugin_merchant_fact';

    const PINOT_TABLE_VIEW_PLUGIN_MERCHANT_FACT_SCHEMA = [
        'plugin_transactions' => self::PINOT_DATA_TYPE_LONG,
        'total_transactions'  => self::PINOT_DATA_TYPE_LONG,
    ];

    const PINOT_TABLE_SEGMENT_FACT_SCHEMA   = [
        'merchant_details_created_at'                   => self::PINOT_DATA_TYPE_LONG,
        'merchant_details_merchant_id'                  => self::PINOT_DATA_TYPE_STRING,
        'join_table_merchant_id'                        => self::PINOT_DATA_TYPE_STRING,
        'join_table_last_txn_date'                      => self::PINOT_DATA_TYPE_STRING,
        'join_table_lt_gmv_payment_gateway'             => self::PINOT_DATA_TYPE_STRING,
        'join_table_lt_gmv_payment_pages'               => self::PINOT_DATA_TYPE_STRING,
        'join_table_lt_gmv_payment_links'               => self::PINOT_DATA_TYPE_STRING,
        'join_table_lt_gmv_subscriptions'               => self::PINOT_DATA_TYPE_STRING,
        'join_table_lt_gmv_epos'                        => self::PINOT_DATA_TYPE_STRING,
        'join_table_lt_gmv_caw'                         => self::PINOT_DATA_TYPE_STRING,
        'join_table_lt_overall_gmv'                     => self::PINOT_DATA_TYPE_STRING,
        'join_table_average_monthly_gmv'                => self::PINOT_DATA_TYPE_DOUBLE,
        'join_table_average_monthly_transactions'       => self::PINOT_DATA_TYPE_DOUBLE,
        'merchant_lifetime_gmv'                         => self::PINOT_DATA_TYPE_STRING,
        'user_days_till_last_transaction'               => self::PINOT_DATA_TYPE_LONG,
        'primary_product_used'                          => self::PINOT_DATA_TYPE_STRING,
        'ppc'                                           => self::PINOT_DATA_TYPE_STRING,
        'mtu'                                           => self::PINOT_DATA_TYPE_STRING,
        'average_monthly_transactions'                  => self::PINOT_DATA_TYPE_DOUBLE,
        'average_monthly_gmv'                           => self::PINOT_DATA_TYPE_DOUBLE,
        'pg_only'                                       => self::PINOT_DATA_TYPE_STRING,
        'pp_only'                                       => self::PINOT_DATA_TYPE_STRING,
        'pl_only'                                       => self::PINOT_DATA_TYPE_STRING,
        'merchant_details_created_date'                 => self::PINOT_DATA_TYPE_STRING,
    ];

    const PINOT_TABLE_PAYMENTS_AUTH_FACT_SCHEMA = [
        'payments_merchant_id'                        => self::PINOT_DATA_TYPE_STRING,
        'payments_reference1'                         => self::PINOT_DATA_TYPE_STRING,
        'payments_id'                                 => self::PINOT_DATA_TYPE_STRING,
        'authorization_payment_id'                    => self::PINOT_DATA_TYPE_STRING,
        'authorization_rrn'                           => self::PINOT_DATA_TYPE_STRING,
    ];

    const PINOT_TABLE_SCHEMA_MAP = [
        self::PINOT_TABLE_SEGMENT_FACT                  => self::PINOT_TABLE_SEGMENT_FACT_SCHEMA,
        self::PINOT_TABLE_VIEW_PLUGIN_MERCHANT_FACT     => self::PINOT_TABLE_VIEW_PLUGIN_MERCHANT_FACT_SCHEMA,
        self::PINOT_TABLE_PAYMNETS_AUTH_FACT            => self::PINOT_TABLE_PAYMENTS_AUTH_FACT_SCHEMA
    ];
}
