<?php


namespace RZP\Models\Merchant\Fraud\HealthChecker;

class Constants
{
    const RESULT_LIVE = 'Live';
    const RESULT_NOT_LIVE = 'Not Live';
    const RESULT_MANUAL_REVIEW = 'Manual Review';

    const STATUS_CODE_RESULT_MAP = [
        200 => self::RESULT_LIVE,
        402 => self::RESULT_NOT_LIVE,
        404 => self::RESULT_NOT_LIVE,
    ];

    const NO_EXCEPTION_COMMENT_FORMAT = 'Status Code = %d';
    const EXCEPTION_COMMENT_FORMAT    = 'Error = %s';

    const REDIS_RETRY_MAP_NAME    = [
        self::WEBSITE_CHECKER   => 'risk:web_checker:retry_map',
        self::APP_CHECKER       => 'risk:app_checker:retry_map',
    ];
    const REDIS_REMINDER_MAP_NAME = [
        self::WEBSITE_CHECKER   => 'risk:web_checker:reminder_map',
        self::APP_CHECKER       => 'risk:app_checker:reminder_map',
    ];

    const QUEUE_MAP = [
        self::WEBSITE_CHECKER   => 'risk_website_checker',
        self::APP_CHECKER       => 'risk_app_checker',
    ];

    const SKIP_REASON_NO_PAYMENT_IN_WINDOW = 'no_payment_in_window';
    const SKIP_REASON_EXEMPT_RISK_CHECK    = 'exempt_risk_check';
    const SKIP_REASON_RETRY_SCHEDULED      = 'retry_scheduled';

    // Health checker types
    const WEBSITE_CHECKER = 'website';
    const APP_CHECKER  = 'app';

    // 2 days = 2*24*60*60
    const REMINDER_WAIT_SECONDS  = 172800;

    // 7 hours = 7*60*60
    const RETRY_WAIT_SECONDS  = 25200;

    const FD_WEBSITE_TICKET_TAG_PREFIX = 'website_checker_fd_ticket_id_';
    const FD_APP_TICKET_TAG_PREFIX = 'app_checker_fd_ticket_id_';

    const FD_TICKET_TAG_PREFIX = [
        self::WEBSITE_CHECKER => self::FD_WEBSITE_TICKET_TAG_PREFIX,
        self::APP_CHECKER => self::FD_APP_TICKET_TAG_PREFIX,
    ];

    // Workflow Tags
    const FD_TICKET_ID_TAG_FMT = [
        self::WEBSITE_CHECKER   => self::FD_WEBSITE_TICKET_TAG_PREFIX . '%s',
        self::APP_CHECKER   => self::FD_APP_TICKET_TAG_PREFIX . '%s',
    ];
    const MERCHANT_REPLIED_TAG      = 'merchant_replied';
    const MERCHANT_REMINDED_TAG     = 'merchant_reminded';
    const MERCHANT_LIVE_TAG = [
        self::WEBSITE_CHECKER   => 'merchant_website_live',
        self::APP_CHECKER       => 'merchant_app_live',
    ];

    const MAX_RISK_CHECK_RETRIES = 1;

    const MERCHANT_DETAIL_KEY = 'merchant_detail';

    const RAS_TRIGGER_REASON = 'Ras_trigger_reason';
    const RAS_TRIGGER_TAG = [
        self::WEBSITE_CHECKER   => 'Ras_trigger_reason:website_checker',
        self::APP_CHECKER       => 'Ras_trigger_reason:app_checker',
    ];

    const PERFORM_HEALTH_CHECK_JOB     = 'perform_health_check';
    const SEND_REMINDER_TO_MERCHANT_JOB = 'send_reminder_to_merchant';

    const RETRY_COUNT_KEY = 'retry_count';

    const EVENT_TYPE               = 'event_type';
    const CHECKER_TYPE             = 'checker_type';
    const PERIODIC_CHECKER_EVENT   = 'periodic_checker';
    const MILESTONE_CHECKER_EVENT  = 'milestone_checker';
    const RISK_SCORE_CHECKER_EVENT = 'risk_score_checker';
    const ONBOARDING_CHECKER_EVENT = 'onboarding_checker';

    // 2592000 = 30 days = 30 * 24 * 60 * 60
    const PERIODIC_CHECKER_MERCHANT_LIST_PAYMENT_CREATED_WINDOW_SECONDS = 2592000;

    const EVENT_TYPES = [
        self::PERIODIC_CHECKER_EVENT,
        self::MILESTONE_CHECKER_EVENT,
        self::RISK_SCORE_CHECKER_EVENT,
        self::ONBOARDING_CHECKER_EVENT
    ];

    const CHECKER_TYPES = [
        self::WEBSITE_CHECKER,
        self::APP_CHECKER
    ];

    const GMV_MILESTONE_AMOUNT                = 15000;
    const GMV_MILESTONE_AMOUNT2               = 100000;
    const TRANSACTION_MILESTONE_COUNT         = 50;
    const MILESTONE_MERCHANT_LIST_PINOT_QUERY =
        'SELECT merchants_id FROM pinot.merchant_risk_fact ' .
        'WHERE (merchant_fact_overall_gmv_lt_yesterday < '. self::GMV_MILESTONE_AMOUNT .' AND merchant_fact_overall_gmv_ltd >= '. self::GMV_MILESTONE_AMOUNT .') OR ' .
        '(merchant_fact_overall_gmv_lt_yesterday < '. self::GMV_MILESTONE_AMOUNT2 .' AND merchant_fact_overall_gmv_ltd >= '. self::GMV_MILESTONE_AMOUNT2 .') OR ' .
        '(merchant_fact_txn_count_lt_yesterday < ' . self::TRANSACTION_MILESTONE_COUNT . ' AND merchant_fact_txn_count_ltd >= ' . self::TRANSACTION_MILESTONE_COUNT . ')';

    const MILESTONE_MERCHANT_LIST_DRUID_QUERY =
        'SELECT merchants_id FROM druid.merchant_risk_fact ' .
        'WHERE (merchant_fact_overall_gmv_lt_yesterday < '. self::GMV_MILESTONE_AMOUNT .' AND merchant_fact_overall_gmv_ltd >= '. self::GMV_MILESTONE_AMOUNT .') OR ' .
        '(merchant_fact_overall_gmv_lt_yesterday < '. self::GMV_MILESTONE_AMOUNT2 .' AND merchant_fact_overall_gmv_ltd >= '. self::GMV_MILESTONE_AMOUNT2 .') OR ' .
        '(merchant_fact_txn_count_lt_yesterday < ' . self::TRANSACTION_MILESTONE_COUNT . ' AND merchant_fact_txn_count_ltd >= ' . self::TRANSACTION_MILESTONE_COUNT . ')';

    const TRANSACTION_DEDUPE_RISK_SCORE        = 90;
    const RISK_SCORE_MERCHANT_LIST_DRUID_QUERY =
        'SELECT merchants_id FROM druid.risk_scoring_fact ' .
        'where Transacting_Dedupe_Merchant_Risk_Scoring_Transacting_Dedupe_Merchant_Risk_Score >= ' . self::TRANSACTION_DEDUPE_RISK_SCORE;

    const RISK_SCORE_MERCHANT_LIST_PINOT_QUERY =
        'SELECT merchants_id FROM pinot.risk_scoring_fact ' .
        'where Transacting_Dedupe_Merchant_Risk_Scoring_Transacting_Dedupe_Merchant_Risk_Score >= ' . self::TRANSACTION_DEDUPE_RISK_SCORE;

    const EVENT_TYPE_DRUID_QUERY_MAP = [
        self::MILESTONE_CHECKER_EVENT  => self::MILESTONE_MERCHANT_LIST_DRUID_QUERY,
        self::RISK_SCORE_CHECKER_EVENT => self::RISK_SCORE_MERCHANT_LIST_DRUID_QUERY,
    ];

    const EVENT_TYPE_QUERY_MAP = [
        self::MILESTONE_CHECKER_EVENT  => self::MILESTONE_MERCHANT_LIST_PINOT_QUERY,
        self::RISK_SCORE_CHECKER_EVENT => self::RISK_SCORE_MERCHANT_LIST_PINOT_QUERY,
    ];


    const APP_CHECKER_QUEUE_DELAY_LIMIT = 600;

    const TRANSACTION_DEDUPE = 'transaction_dedupe';

    public static function eventAndCheckerTypeRedisMap($eventType, $checkerType): string
    {
        if ($eventType === Constants::PERIODIC_CHECKER_EVENT)
        {
            return Constants::REDIS_RETRY_MAP_NAME[$checkerType];
        }
        return Constants::REDIS_RETRY_MAP_NAME[$checkerType] . ':' . $eventType;
    }

    public static function getAllEventTypeRedisMap($checkerType): array
    {
        $redisMap = [];
        foreach (Constants::EVENT_TYPES as $eventType)
        {
            if ($eventType === Constants::PERIODIC_CHECKER_EVENT)
            {
                $redisMap[$eventType] = Constants::REDIS_RETRY_MAP_NAME[$checkerType];
            }
            else
            {
                $redisMap[$eventType] = Constants::REDIS_RETRY_MAP_NAME[$checkerType] . ':' . $eventType;
            }
        }
        return $redisMap;
    }
}
