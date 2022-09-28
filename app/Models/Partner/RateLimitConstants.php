<?php

namespace RZP\Models\Partner;

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;

class RateLimitConstants
{
    const THRESHOLD         = "threshold";
    const TRACE_CODE        = "trace_code";
    const ERROR_TRACE_CODE  = "error_trace_code";
    const ERROR_CODE        = "error_code";
    const METRIC            = "metric";
    const RATE_LIMIT_PREFIX = "prts_";


    const SUPPORTED_RATELIMIT_SOURCES = [Constants::ADD_ACCOUNT, Constants::ADD_MULTIPLE_ACCOUNT];

    ////Add all the rate limiting values for each source, though they are same for now, we can update this matrix to have
    ////different values for different create sources in future
    const RATELIMIT_CONFIG = [
        Constants::ADD_ACCOUNT => [
            self::THRESHOLD        => 2000,
            self::TRACE_CODE       => TraceCode::RATE_LIMIT_PARTNER_SUBMERCHANT_ONBOARDING,
            self::ERROR_TRACE_CODE => TraceCode::RATE_LIMIT_PARTNER_SUBMERCHANT_ONBOARDING_EXCEEDED,
            self::ERROR_CODE       => ErrorCode::BAD_REQUEST_DAILY_LIMIT_SUBMERCHANT_ONBOARDING_EXCEEDED,
            self::METRIC           => Metric::SUBMERCHANT_ONBOARDING_DAILY_LIMIT_EXCEEDED,
        ],

        Constants::ADD_MULTIPLE_ACCOUNT => [
            self::THRESHOLD        => 2000,
            self::TRACE_CODE       => TraceCode::RATE_LIMIT_PARTNER_SUBMERCHANT_ONBOARDING,
            self::ERROR_TRACE_CODE => TraceCode::RATE_LIMIT_PARTNER_SUBMERCHANT_ONBOARDING_EXCEEDED,
            self::ERROR_CODE       => ErrorCode::BAD_REQUEST_DAILY_LIMIT_SUBMERCHANT_ONBOARDING_EXCEEDED,
            self::METRIC           => Metric::SUBMERCHANT_ONBOARDING_DAILY_LIMIT_EXCEEDED,
        ],
    ];
}
