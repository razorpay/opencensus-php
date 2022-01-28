<?php

namespace RZP\Models\Admin\Report;

class Type
{
    protected static $reportTypes = [
        Constant::REPORT_TYPE_SUMMARY_MERCHANT,
        Constant::REPORT_TYPE_SUMMARY_PAYMENT,

        Constant::REPORT_TYPE_DETAILED_MERCHANT,
        Constant::REPORT_TYPE_DETAILED_TRANSACTION,
        Constant::REPORT_TYPE_DETAILED_FAILURE,

        Constant::REPORT_TYPE_DETAILED_FAILURE_DETAIL,
        Constant::REPORT_TYPE_DETAILED_FAILURE_DETAIL_DOWNLOAD,
        Constant::REPORT_TYPE_SINGLE_MERCHANT_DETAIL,

        Constant::DOWNLOAD,
    ];

    public static function getValidReportTypes(): array
    {
        return self::$reportTypes;
    }

    public static function isValidReportType(string $type): bool
    {
        if(in_array($type, Type::getValidReportTypes()))
        {
            return true;
        }
        return false;
    }
}
