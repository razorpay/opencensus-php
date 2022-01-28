<?php

namespace RZP\Models\Admin\Report;

class Field
{
    public static $fields = [
        Constant::REPORT_TYPE_SUMMARY_MERCHANT  => [
        ],
        Constant::REPORT_TYPE_SUMMARY_PAYMENT   => [
        ],

        Constant::REPORT_TYPE_DETAILED_MERCHANT  => [
            Constant::FIELD_RANK,
            Constant::FIELD_PARTNER_ID,
            Constant::FIELD_MERCHANT_NAME,
            Constant::FIELD_PAYMENT_METHOD,
            Constant::FIELD_PAYMENT_COUNT,
            Constant::FIELD_PAYMENT_AMOUNT,

        ],
        Constant::REPORT_TYPE_DETAILED_TRANSACTION  => [
//            Constant::FIELD_PARTNER_ID,
            Constant::FIELD_PAYMENT_METHOD,
            Constant::FIELD_SUCCESS_COUNT,
            Constant::FIELD_SUCCESS_AMOUNT,
            Constant::FIELD_FAILURE_COUNT,
            Constant::FIELD_FAILURE_AMOUNT,
            Constant::FIELD_TOTAL_COUNT,
            Constant::FIELD_TOTAL_AMOUNT,
            Constant::FIELD_SUCCESS_RATE,
        ],
        Constant::REPORT_TYPE_DETAILED_FAILURE  => [
            Constant::FIELD_PARTNER_ID,
            Constant::FIELD_PAYMENT_METHOD,
            Constant::FIELD_FAILURE_COUNT,
            Constant::FIELD_FAILURE_AMOUNT,
        ],
        Constant::REPORT_TYPE_DETAILED_FAILURE_DETAIL   => [
            Constant::FIELD_ERROR_CODE,
            Constant::FIELD_PAYMENT_COUNT,
        ],
    ];

    public static function getFieldsForReportType(string $type): array
    {
        return self::$fields[$type];
    }
}
