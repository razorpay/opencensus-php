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

    const PINOT_RISK_SCORING_FACT            = "risk_scoring_fact";

    const PINOT_TABLE_VIEW_PLUGIN_MERCHANT_FACT  = 'plugin_merchant_fact';

    const PINOT_TABLE_MERCHANT_RISK_FACT         = 'merchant_risk_fact';

    const PINOT_TABLE_VIEW_PLUGIN_MERCHANT_FACT_SCHEMA = [
        'plugin_transactions' => self::PINOT_DATA_TYPE_LONG,
        'total_transactions'  => self::PINOT_DATA_TYPE_LONG,
    ];

    const PINOT_TABLE_SEGMENT_FACT_SCHEMA   = [
        'merchant_details_created_at'                   => self::PINOT_DATA_TYPE_LONG,
        'merchant_details_merchant_id'                  => self::PINOT_DATA_TYPE_STRING,
        'join_table_merchant_id'                        => self::PINOT_DATA_TYPE_STRING,
        'join_table_last_txn_date'                      => self::PINOT_DATA_TYPE_STRING,
        'join_table_lt_gmv_payment_gateway'             => self::PINOT_DATA_TYPE_DOUBLE,
        'join_table_lt_gmv_payment_pages'               => self::PINOT_DATA_TYPE_DOUBLE,
        'join_table_lt_gmv_payment_links'               => self::PINOT_DATA_TYPE_DOUBLE,
        'join_table_lt_gmv_subscriptions'               => self::PINOT_DATA_TYPE_DOUBLE,
        'join_table_lt_gmv_epos'                        => self::PINOT_DATA_TYPE_DOUBLE,
        'join_table_lt_gmv_caw'                         => self::PINOT_DATA_TYPE_DOUBLE,
        'join_table_lt_overall_gmv'                     => self::PINOT_DATA_TYPE_DOUBLE,
        'join_table_average_monthly_gmv'                => self::PINOT_DATA_TYPE_DOUBLE,
        'join_table_average_monthly_transactions'       => self::PINOT_DATA_TYPE_DOUBLE,
        'merchant_lifetime_gmv'                         => self::PINOT_DATA_TYPE_DOUBLE,
        'user_days_till_last_transaction'               => self::PINOT_DATA_TYPE_LONG,
        'primary_product_used'                          => self::PINOT_DATA_TYPE_STRING,
        'ppc'                                           => self::PINOT_DATA_TYPE_LONG,
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

    const PINOT_TABLE_RISK_SCORING_FACT_SCHEMA  = [
        'merchants_created_at'                                                              => self::PINOT_DATA_TYPE_LONG,
        'merchants_id'                                                                      => self::PINOT_DATA_TYPE_STRING,
        'Transacting_Dedupe_Merchant_Risk_Scoring_merchant_id'                              => self::PINOT_DATA_TYPE_STRING,
        'Transacting_Dedupe_Merchant_Risk_Scoring_Transacting_Dedupe_Merchant_Risk_Score'   => self::PINOT_DATA_TYPE_DOUBLE,
        'Global_Merchant_Risk_Scoring_merchant_id'                                          => self::PINOT_DATA_TYPE_STRING,
        'Global_Merchant_Risk_Scoring_Global_Merchant_Risk_Score'                           => self::PINOT_DATA_TYPE_STRING,
        'merchant_vintage_merchant_id'                                                      => self::PINOT_DATA_TYPE_STRING,
        'merchant_vintage_merchant_vintage'                                                 => self::PINOT_DATA_TYPE_STRING,
        'Payment_Details_merchant_id'                                                       => self::PINOT_DATA_TYPE_STRING,
        'Payment_Details_first_transaction_date'                                            => self::PINOT_DATA_TYPE_STRING,
        'Payment_Details_last_transaction_date'                                             => self::PINOT_DATA_TYPE_STRING,
        'Payment_Details_lifetime_captured_payments'                                        => self::PINOT_DATA_TYPE_LONG,
        'Payment_Details_past_one_month_captured_payments'                                  => self::PINOT_DATA_TYPE_LONG,
        'Payment_Details_lifetime_captured_gmv'                                             => self::PINOT_DATA_TYPE_DOUBLE,
        'Payment_Details_past_one_month_captured_gmv'                                       => self::PINOT_DATA_TYPE_DOUBLE,
        'Payment_Details_lifetime_success_rate'                                             => self::PINOT_DATA_TYPE_DOUBLE,
        'Payment_Details_past_one_month_success_rate'                                       => self::PINOT_DATA_TYPE_DOUBLE,
        'Domestic_cts_overall_merchant_id'                                                  => self::PINOT_DATA_TYPE_STRING,
        'Domestic_cts_overall_lifetime_chargeback_amount'                                   => self::PINOT_DATA_TYPE_DOUBLE,
        'Domestic_cts_overall_lifetime_cts'                                                 => self::PINOT_DATA_TYPE_DOUBLE,
        'Domestic_cts_3months_merchant_id'                                                  => self::PINOT_DATA_TYPE_STRING,
        'Domestic_cts_3months_last_3_months_chargeback_amount'                              => self::PINOT_DATA_TYPE_DOUBLE,
        'Domestic_cts_3months_last_3_months_cts'                                            => self::PINOT_DATA_TYPE_DOUBLE,
        'Domestic_FTS_merchant_id'                                                          => self::PINOT_DATA_TYPE_STRING,
        'Domestic_FTS_lifetime_domestic_Fraud_amount'                                       => self::PINOT_DATA_TYPE_DOUBLE,
        'Domestic_FTS_lifetime_domestic_FTS'                                                => self::PINOT_DATA_TYPE_DOUBLE,
        'Domestic_FTS_past_3_month_domestic_FTS'                                            => self::PINOT_DATA_TYPE_DOUBLE,
        'Domestic_FTS_past_3_month_domestic_adjusted_FTS'                                   => self::PINOT_DATA_TYPE_DOUBLE,
        'Dispute_ltd_merchant_id'                                                           => self::PINOT_DATA_TYPE_STRING,
        'Dispute_ltd_lifetime_disputes'                                                     => self::PINOT_DATA_TYPE_LONG,
        'Dispute_1month_merchant_id'                                                        => self::PINOT_DATA_TYPE_STRING,
        'Dispute_1month_past_1_month_disputes'                                              => self::PINOT_DATA_TYPE_LONG,
        'International_Payment_Details_merchant_id'                                         => self::PINOT_DATA_TYPE_STRING,
        'International_Payment_Details_lifetime_captured_payments'                          => self::PINOT_DATA_TYPE_LONG,
        'International_Payment_Details_past_one_month_captured_payments'                    => self::PINOT_DATA_TYPE_LONG,
        'International_Payment_Details_lifetime_captured_gmv'                               => self::PINOT_DATA_TYPE_DOUBLE,
        'International_Payment_Details_past_one_month_captured_gmv'                         => self::PINOT_DATA_TYPE_DOUBLE,
        'International_Payment_Details_lifetime_success_rate'                               => self::PINOT_DATA_TYPE_DOUBLE,
        'International_Payment_Details_past_one_month_success_rate'                         => self::PINOT_DATA_TYPE_DOUBLE,
        'International_OAR_merchant_id'                                                     => self::PINOT_DATA_TYPE_STRING,
        'International_OAR_Order_Approval_rate'                                             => self::PINOT_DATA_TYPE_DOUBLE,
        'International_cts_overall_merchant_id'                                             => self::PINOT_DATA_TYPE_STRING,
        'International_cts_overall_lifetime_chargeback_amount'                              => self::PINOT_DATA_TYPE_DOUBLE,
        'International_cts_overall_lifetime_cts'                                            => self::PINOT_DATA_TYPE_DOUBLE,
        'International_cts_3months_merchant_id'                                             => self::PINOT_DATA_TYPE_STRING,
        'International_cts_3months_last_3_months_chargeback_amount'                         => self::PINOT_DATA_TYPE_DOUBLE,
        'International_cts_3months_last_3_months_cts'                                       => self::PINOT_DATA_TYPE_DOUBLE,
        'International_FTS_merchant_id'                                                     => self::PINOT_DATA_TYPE_STRING,
        'International_FTS_lifetime_international_Fraud_amount'                             => self::PINOT_DATA_TYPE_DOUBLE,
        'International_FTS_lifetime_international_FTS'                                      => self::PINOT_DATA_TYPE_DOUBLE,
        'International_FTS_past_3_month_international_FTS'                                  => self::PINOT_DATA_TYPE_DOUBLE,
        'International_FTS_past_3_month_international_adjusted_FTS'                         => self::PINOT_DATA_TYPE_DOUBLE,
        'PL_PP_Dedupe_merchant_id'                                                          => self::PINOT_DATA_TYPE_STRING,
        'PL_PP_Dedupe_pl_pp_deduped'                                                        => self::PINOT_DATA_TYPE_LONG,
        'Customer_Flagging_merchant_id'                                                     => self::PINOT_DATA_TYPE_STRING,
        'Customer_Flagging_customer_flagged'                                                => self::PINOT_DATA_TYPE_LONG,
        'Blacklist_IP_merchant_id'                                                          => self::PINOT_DATA_TYPE_STRING,
        'Blacklist_IP_blacklist_ip_entities'                                                => self::PINOT_DATA_TYPE_LONG,
        'workflows_data_merchant_id'                                                        => self::PINOT_DATA_TYPE_STRING,
        'workflows_data_FOH_workflows'                                                      => self::PINOT_DATA_TYPE_LONG,
        'workflows_data_Disable_live_workflows'                                             => self::PINOT_DATA_TYPE_LONG,
        'workflows_data_Suspend_workflows'                                                  => self::PINOT_DATA_TYPE_LONG,
    ];

    const PINOT_TABLE_MERCHANT_RISK_FACT_SCHEMA = [
        'merchants_billing_label'                                                   => self::PINOT_DATA_TYPE_STRING,
        'merchants_org_id'                                                          => self::PINOT_DATA_TYPE_STRING,
        'merchants_parent_id'                                                       => self::PINOT_DATA_TYPE_STRING,
        'merchants_category'                                                        => self::PINOT_DATA_TYPE_STRING,
        'merchants_category2'                                                       => self::PINOT_DATA_TYPE_STRING,
        'merchants_international'                                                   => self::PINOT_DATA_TYPE_LONG,
        'merchants_name'                                                            => self::PINOT_DATA_TYPE_STRING,
        'merchants_live'                                                            => self::PINOT_DATA_TYPE_LONG,
        'merchants_website'                                                         => self::PINOT_DATA_TYPE_STRING,
        'merchants_email'                                                           => self::PINOT_DATA_TYPE_STRING,
        'merchants_activated_at'                                                    => self::PINOT_DATA_TYPE_LONG,
        'merchants_suspended_at'                                                    => self::PINOT_DATA_TYPE_LONG,
        'merchants_pricing_plan_id'                                                 => self::PINOT_DATA_TYPE_STRING,
        'merchants_id'                                                              => self::PINOT_DATA_TYPE_STRING,
        'merchants_hold_funds'                                                      => self::PINOT_DATA_TYPE_LONG,
        'merchant_fact_merchant_id'                                                 => self::PINOT_DATA_TYPE_STRING,
        'merchant_fact_overall_gmv_ltd'                                             => self::PINOT_DATA_TYPE_DOUBLE,
        'merchant_fact_overall_gmv_lt_yesterday'                                    => self::PINOT_DATA_TYPE_DOUBLE,
        'merchant_fact_txn_count_ltd'                                               => self::PINOT_DATA_TYPE_LONG,
        'merchant_fact_txn_count_lt_yesterday'                                      => self::PINOT_DATA_TYPE_LONG,
        'merchant_fact_txn_attempt_count_ltd'                                       => self::PINOT_DATA_TYPE_LONG,
        'merchant_fact_txn_attempt_count_lt_yesterday'                              => self::PINOT_DATA_TYPE_LONG,
        'merchant_fact_authorized_gmv_ltd'                                          => self::PINOT_DATA_TYPE_DOUBLE,
        'merchant_fact_authorized_payment_count_ltd'                                => self::PINOT_DATA_TYPE_LONG,
        'aov_table_aov_merchant_id'                                                 => self::PINOT_DATA_TYPE_STRING,
        'aov_table_cov'                                                             => self::PINOT_DATA_TYPE_DOUBLE,
        'aov_table_cov_first20'                                                     => self::PINOT_DATA_TYPE_DOUBLE,
        'aov_table_aov_reported'                                                    => self::PINOT_DATA_TYPE_STRING,
        'aov_table_mean_aov'                                                        => self::PINOT_DATA_TYPE_DOUBLE,
        'aov_table_actual_aov'                                                      => self::PINOT_DATA_TYPE_DOUBLE,
        'aov_table_pay_count'                                                       => self::PINOT_DATA_TYPE_LONG,
        'merchant_details_merchant_id'                                              => self::PINOT_DATA_TYPE_STRING,
        'merchant_details_business_type'                                            => self::PINOT_DATA_TYPE_STRING,
        'merchant_details_business_type_desc'                                       => self::PINOT_DATA_TYPE_STRING,
        'merchant_details_gst_present'                                              => self::PINOT_DATA_TYPE_LONG,
        'merchant_details_apps_exempt_risk_check'                                   => self::PINOT_DATA_TYPE_LONG,
        'merchant_details_fund_account_validation_id'                               => self::PINOT_DATA_TYPE_STRING,
        'merchant_details_bank_details_verification_status'                         => self::PINOT_DATA_TYPE_STRING,
        'merchant_details_contact_email'                                            => self::PINOT_DATA_TYPE_STRING,
        'merchant_details_contact_mobile'                                           => self::PINOT_DATA_TYPE_STRING,
        'merchant_details_business_name'                                            => self::PINOT_DATA_TYPE_STRING,
        'merchant_details_business_category'                                        => self::PINOT_DATA_TYPE_STRING,
        'merchant_details_business_subcategory'                                     => self::PINOT_DATA_TYPE_STRING,
        'merchant_details_business_dba'                                             => self::PINOT_DATA_TYPE_STRING,
        'merchant_dormancy_merchant_id'                                             => self::PINOT_DATA_TYPE_STRING,
        'merchant_dormancy_max_dormancy_days'                                       => self::PINOT_DATA_TYPE_LONG,
        'merchant_dormancy_post_dormancy_first_txn_date_for_max_dormancy'           => self::PINOT_DATA_TYPE_STRING,
        'merchant_dormancy_max_intl_dormancy_days'                                  => self::PINOT_DATA_TYPE_LONG,
        'merchant_dormancy_post_dormancy_first_intl_txn_date_for_max_intl_dormancy' => self::PINOT_DATA_TYPE_STRING,
        'ac_owner_merchant_id'                                                      => self::PINOT_DATA_TYPE_STRING,
        'ac_owner_managing_team'                                                    => self::PINOT_DATA_TYPE_STRING,
        'ac_owner_owner_name'                                                       => self::PINOT_DATA_TYPE_STRING,
        'ac_owner_owner_email'                                                      => self::PINOT_DATA_TYPE_STRING,
        'ac_owner_od_isactive'                                                      => self::PINOT_DATA_TYPE_STRING,
        'balance_merchant_id'                                                       => self::PINOT_DATA_TYPE_STRING,
        'balance_pg_balance'                                                        => self::PINOT_DATA_TYPE_DOUBLE,
        'balance_x_va_balance'                                                      => self::PINOT_DATA_TYPE_DOUBLE,
        'balance_x_ca_balance'                                                      => self::PINOT_DATA_TYPE_DOUBLE
    ];

    const PINOT_TABLE_SCHEMA_MAP = [
        self::PINOT_TABLE_SEGMENT_FACT                  => self::PINOT_TABLE_SEGMENT_FACT_SCHEMA,
        self::PINOT_TABLE_PAYMNETS_AUTH_FACT            => self::PINOT_TABLE_PAYMENTS_AUTH_FACT_SCHEMA,
        self::PINOT_RISK_SCORING_FACT                   => self::PINOT_TABLE_RISK_SCORING_FACT_SCHEMA,
        self::PINOT_TABLE_VIEW_PLUGIN_MERCHANT_FACT     => self::PINOT_TABLE_VIEW_PLUGIN_MERCHANT_FACT_SCHEMA,
        self::PINOT_TABLE_MERCHANT_RISK_FACT            => self::PINOT_TABLE_MERCHANT_RISK_FACT_SCHEMA
    ];
}
