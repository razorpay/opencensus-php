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
}
