<?php

namespace RZP\Models\Merchant\Fraud\Checker;

class Constants
{
    // ras event types
    const RAS_EVENT_TYPE_MILESTONE_CHECKER = 'milestone_checker';

    // source
    const RAS_SOURCE_API_SERVICE = 'api_service';

    // ras categories
    const RAS_CATEGORY_AOV                        = 'aov';
    const RAS_CATEGORY_HIGH_GMV                   = 'high_gmv';
    const RAS_CATEGORY_MYSTERY_SHOPPING           = 'mystery_shopping';
    const RAS_CATEGORY_GSTIN_HARD_BLOCK           = 'gstin_hard_block';
    const RAS_CATEGORY_GSTIN_SOFT_BLOCK           = 'gstin_soft_block';
    const RAS_CATEGORY_GSTIN_OFFLINE_VERIFICATION = 'gstin_offline_verification';

    // ras entities
    const RAS_ENTITY_RISK_FACT_AOV              = 'risk_fact_aov';
    const RAS_ENTITY_RISK_FACT_MYSTERY_SHOPPING = 'risk_fact_mystery_shopping';
    const RAS_ENTITY_RISK_FACT_HIGH_GMV         = 'risk_fact_high_gmv';
    const RAS_ENTITY_RISK_FACT_GSTIN_HARD       = 'risk_fact_gst_hard';
    const RAS_ENTITY_RISK_FACT_GSTIN_SOFT       = 'risk_fact_gst_soft';
    const RAS_ENTITY_RISK_FACT_GSTIN_OFFLINE    = 'risk_fact_gst_offline';

    const ALLOWED_MILESTONE_CRON_CATEGORIES_BY_EVENT_TYPE = [
        self::RAS_EVENT_TYPE_MILESTONE_CHECKER => [
            self::RAS_CATEGORY_AOV,
            self::RAS_CATEGORY_HIGH_GMV,
            self::RAS_CATEGORY_MYSTERY_SHOPPING,
            self::RAS_CATEGORY_GSTIN_HARD_BLOCK,
            self::RAS_CATEGORY_GSTIN_SOFT_BLOCK,
            self::RAS_CATEGORY_GSTIN_OFFLINE_VERIFICATION,
        ],
    ];

    // lookup queries

    const DRUID_QUERY_AOV =
        'SELECT merchants_id FROM druid.merchant_risk_fact ' .
        'WHERE merchant_fact_txn_count_ltd >= 20 ' .
        'AND merchant_fact_txn_count_lt_yesterday < 20 ' .
        'AND aov_table_cov > 1.0 ' .
        'AND aov_table_aov_reported = \'1\'';

    const DRUID_QUERY_HIGH_GMV =
        'SELECT merchants_id FROM druid.merchant_risk_fact ' .
        'WHERE merchant_fact_overall_gmv_ltd >= 10000000 ' .
        'AND merchant_fact_overall_gmv_lt_yesterday < 10000000';

    const DRUID_QUERY_MYSTERY_SHOPPING =
        'SELECT merchants_id FROM druid.merchant_risk_fact ' .
        'WHERE ' .
        '( ' .
        'merchant_fact_overall_gmv_ltd >= 200000 ' .
        'AND merchant_fact_overall_gmv_lt_yesterday < 200000 ' .
        'AND merchant_details_apps_exempt_risk_check = FALSE ' .
        ') ' .
        'OR ' .
        '( ' .
        'merchant_fact_overall_gmv_ltd >= 1000000 ' .
        'AND merchant_fact_overall_gmv_lt_yesterday < 1000000 ' .
        'AND merchant_details_apps_exempt_risk_check = TRUE ' .
        ')';

    const DRUID_QUERY_GSTIN_SOFT_BLOCK =
        'SELECT merchants_id FROM druid.merchant_risk_fact ' .
        'WHERE merchant_fact_overall_gmv_ltd >= 3000000 ' .
        'AND merchant_fact_overall_gmv_lt_yesterday < 3000000 ' .
        'AND merchant_details_gst_present = FALSE';

    const DRUID_QUERY_GSTIN_HARD_BLOCK =
        'SELECT merchants_id FROM druid.merchant_risk_fact ' .
        'WHERE merchant_fact_overall_gmv_ltd >= 4000000 ' .
        'AND merchant_fact_overall_gmv_lt_yesterday < 4000000 ' .
        'AND merchant_details_gst_present = FALSE';

    const DRUID_QUERY_GSTIN_OFFLINE_VERIFICATION =
        'SELECT merchants_id FROM druid.merchant_risk_fact ' .
        'WHERE merchant_fact_overall_gmv_ltd >= 4000000 ' .
        'AND merchant_fact_overall_gmv_lt_yesterday < 4000000 ' .
        'AND merchant_details_gst_present = TRUE';

    // Pinot queries

    const PINOT_QUERY_AOV =
        'SELECT merchants_id FROM pinot.merchant_risk_fact ' .
        'WHERE merchant_fact_txn_count_ltd >= 20 ' .
        'AND merchant_fact_txn_count_lt_yesterday < 20 ' .
        'AND aov_table_cov > 1.0 ' .
        'AND aov_table_aov_reported = \'1\'';

    const PINOT_QUERY_HIGH_GMV =
        'SELECT merchants_id FROM pinot.merchant_risk_fact ' .
        'WHERE merchant_fact_overall_gmv_ltd >= 10000000 ' .
        'AND merchant_fact_overall_gmv_lt_yesterday < 10000000';

    const PINOT_QUERY_MYSTERY_SHOPPING =
        'SELECT merchants_id FROM pinot.merchant_risk_fact ' .
        'WHERE ' .
        '( ' .
        'merchant_fact_overall_gmv_ltd >= 200000 ' .
        'AND merchant_fact_overall_gmv_lt_yesterday < 200000 ' .
        'AND merchant_details_apps_exempt_risk_check = 0 ' .
        ') ' .
        'OR ' .
        '( ' .
        'merchant_fact_overall_gmv_ltd >= 1000000 ' .
        'AND merchant_fact_overall_gmv_lt_yesterday < 1000000 ' .
        'AND merchant_details_apps_exempt_risk_check = 1 ' .
        ')';

    const PINOT_QUERY_GSTIN_SOFT_BLOCK =
        'SELECT merchants_id FROM pinot.merchant_risk_fact  ' .
        'WHERE merchant_fact_overall_gmv_ltd >= 3000000 ' .
        'AND merchant_fact_overall_gmv_lt_yesterday < 3000000 ' .
        'AND merchant_details_gst_present = 0';

    const PINOT_QUERY_GSTIN_HARD_BLOCK =
        'SELECT merchants_id FROM pinot.merchant_risk_fact ' .
        'WHERE merchant_fact_overall_gmv_ltd >= 4000000 ' .
        'AND merchant_fact_overall_gmv_lt_yesterday < 4000000 ' .
        'AND merchant_details_gst_present = 0';

    const PINOT_QUERY_GSTIN_OFFLINE_VERIFICATION =
        'SELECT merchants_id FROM pinot.merchant_risk_fact ' .
        'WHERE merchant_fact_overall_gmv_ltd >= 4000000 ' .
        'AND merchant_fact_overall_gmv_lt_yesterday < 4000000 ' .
        'AND merchant_details_gst_present = 1';

    const DRUID_QUERY_GROUPED_BY_EVENT_CATEGORY = [
        self::RAS_EVENT_TYPE_MILESTONE_CHECKER => [
            self::RAS_CATEGORY_AOV                        => self::DRUID_QUERY_AOV,
            self::RAS_CATEGORY_HIGH_GMV                   => self::DRUID_QUERY_HIGH_GMV,
            self::RAS_CATEGORY_MYSTERY_SHOPPING           => self::DRUID_QUERY_MYSTERY_SHOPPING,
            self::RAS_CATEGORY_GSTIN_SOFT_BLOCK           => self::DRUID_QUERY_GSTIN_SOFT_BLOCK,
            self::RAS_CATEGORY_GSTIN_HARD_BLOCK           => self::DRUID_QUERY_GSTIN_HARD_BLOCK,
            self::RAS_CATEGORY_GSTIN_OFFLINE_VERIFICATION => self::DRUID_QUERY_GSTIN_OFFLINE_VERIFICATION,
        ],
    ];

    const PINOT_QUERY_GROUPED_BY_EVENT_CATEGORY = [
        self::RAS_EVENT_TYPE_MILESTONE_CHECKER => [
            self::RAS_CATEGORY_AOV                        => self::PINOT_QUERY_AOV,
            self::RAS_CATEGORY_HIGH_GMV                   => self::PINOT_QUERY_HIGH_GMV,
            self::RAS_CATEGORY_MYSTERY_SHOPPING           => self::PINOT_QUERY_MYSTERY_SHOPPING,
            self::RAS_CATEGORY_GSTIN_SOFT_BLOCK           => self::PINOT_QUERY_GSTIN_SOFT_BLOCK,
            self::RAS_CATEGORY_GSTIN_HARD_BLOCK           => self::PINOT_QUERY_GSTIN_HARD_BLOCK,
            self::RAS_CATEGORY_GSTIN_OFFLINE_VERIFICATION => self::PINOT_QUERY_GSTIN_OFFLINE_VERIFICATION,
        ],
    ];

    const RAS_CATEGORY_ENTITY_MAPPING = [
        self::RAS_CATEGORY_AOV                        => self::RAS_ENTITY_RISK_FACT_AOV,
        self::RAS_CATEGORY_MYSTERY_SHOPPING           => self::RAS_ENTITY_RISK_FACT_MYSTERY_SHOPPING,
        self::RAS_CATEGORY_HIGH_GMV                   => self::RAS_ENTITY_RISK_FACT_HIGH_GMV,
        self::RAS_CATEGORY_GSTIN_SOFT_BLOCK           => self::RAS_ENTITY_RISK_FACT_GSTIN_SOFT,
        self::RAS_CATEGORY_GSTIN_HARD_BLOCK           => self::RAS_ENTITY_RISK_FACT_GSTIN_HARD,
        self::RAS_CATEGORY_GSTIN_OFFLINE_VERIFICATION => self::RAS_ENTITY_RISK_FACT_GSTIN_OFFLINE,
    ];

    public static function getDruidQuery(string $category, string $eventType)
    {
        return self::DRUID_QUERY_GROUPED_BY_EVENT_CATEGORY[$eventType][$category];
    }

    public static function getPinotQuery(string $category, string $eventType)
    {
        return self::PINOT_QUERY_GROUPED_BY_EVENT_CATEGORY[$eventType][$category];
    }

    public static function isValidCategory(string $category, string $eventType)
    {
        $allowedCategories = self::ALLOWED_MILESTONE_CRON_CATEGORIES_BY_EVENT_TYPE[$eventType];

        if (is_null($allowedCategories) === true)
        {
            return false;
        }

        return (in_array($category, $allowedCategories) === true);
    }

    public static function getRasEntityType(string $category)
    {
        return self::RAS_CATEGORY_ENTITY_MAPPING[$category];
    }
}
