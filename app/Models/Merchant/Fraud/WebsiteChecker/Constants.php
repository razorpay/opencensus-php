<?php


namespace RZP\Models\Merchant\Fraud\WebsiteChecker;

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

    const REDIS_RETRY_MAP_NAME    = 'risk:web_checker:retry_map';
    const REDIS_REMINDER_MAP_NAME = 'risk:web_checker:reminder_map';

    const SKIP_REASON_EXEMPT_RISK_CHECK = 'exempt_risk_check';
    const SKIP_REASON_RETRY_SCHEDULED   = 'retry_scheduled';

    // 2 days = 2*24*60*60
    const REMINDER_WAIT_SECONDS  = 172800;

    // 7 hours = 7*60*60
    const RETRY_WAIT_SECONDS  = 25200;

    const FD_TICKET_TAG_PREFIX = 'website_checker_fd_ticket_id_';

    // Workflow Tags
    const FD_TICKET_ID_TAG_FMT      = self::FD_TICKET_TAG_PREFIX . '%s';
    const MERCHANT_REPLIED_TAG      = 'merchant_replied';
    const MERCHANT_REMINDED_TAG     = 'merchant_reminded';
    const MERCHANT_WEBSITE_LIVE_TAG = 'merchant_website_live';

    const MAX_RISK_CHECK_RETRIES = 1;

    const MERCHANT_DETAIL_KEY = 'merchant_detail';

    const RAS_TRIGGER_WEBCHECKER_WF_TAG = 'Ras_trigger_reason:website_checker';

    const PERFORM_WEBSITE_CHECK_JOB     = 'perform_website_check';
    const SEND_REMINDER_TO_MERCHANT_JOB = 'send_reminder_to_merchant';

    const RETRY_COUNT_KEY = 'retry_count';
}
