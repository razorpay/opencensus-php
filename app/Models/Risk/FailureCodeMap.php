<?php

namespace RZP\Models\Risk;

use RZP\Error\ErrorCode;

class FailureCodeMap
{
    public static $codes = [
        ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK_DUE_TO_RISK => [
            Entity::SOURCE     => Source::BANK,
            Entity::REASON     => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK_DUE_TO_RISK,
            Entity::FRAUD_TYPE => Type::CONFIRMED,
        ],

        ErrorCode::GATEWAY_ERROR_DENIED_BY_RISK => [
            Entity::SOURCE     => Source::GATEWAY,
            Entity::REASON     => ErrorCode::GATEWAY_ERROR_DENIED_BY_RISK,
            Entity::FRAUD_TYPE => Type::CONFIRMED,
        ],

        ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED => [
            Entity::SOURCE     => Source::BANK,
            Entity::REASON     => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK_DUE_TO_RISK,
            Entity::FRAUD_TYPE => Type::SUSPECTED,
        ],

        ErrorCode::BAD_REQUEST_PAYMENT_BLOCKED_DUE_TO_FRAUD => [
            Entity::REASON     => RiskCode::PAYMENT_FAILED_DUE_TO_BLOCKED_CARD,
            Entity::SOURCE     => Source::INTERNAL,
            Entity::FRAUD_TYPE => Type::CONFIRMED,
        ],
    ];

    public static function getRiskDataForError(string $errorCode): array
    {
        $riskErrorCodes = array_keys(static::$codes);

        if (in_array($errorCode, $riskErrorCodes, true) === true)
        {
            return static::$codes[$errorCode];
        }

        return [];
    }
}
