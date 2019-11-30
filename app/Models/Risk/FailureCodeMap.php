<?php

namespace RZP\Models\Risk;

use RZP\Error\ErrorCode;

class FailureCodeMap
{
    public static $codes = [
        ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK_DUE_TO_RISK => [
            Entity::SOURCE     => Source::BANK,
            Entity::REASON     => RiskCode::PAYMENT_DECLINED_DUE_TO_RISK,
            Entity::FRAUD_TYPE => Type::SUSPECTED,
        ],

        ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK_DUE_TO_BLOCKED_CARD => [
            Entity::SOURCE     => Source::BANK,
            Entity::REASON     => RiskCode::PAYMENT_FAILED_DUE_TO_BLOCKED_CARD,
            Entity::FRAUD_TYPE => Type::SUSPECTED,
        ],

        ErrorCode::BAD_REQUEST_CARD_STOLEN_OR_LOST => [
            Entity::SOURCE     => Source::BANK,
            Entity::REASON     => RiskCode::PAYMENT_FAILED_DUE_TO_STOLEN_CARD,
            Entity::FRAUD_TYPE => Type::CONFIRMED,
        ],

        ErrorCode::BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD_GATEWAY => [
            Entity::SOURCE     => Source::RISK_GATEWAY,
            Entity::REASON     => RiskCode::PAYMENT_FAILED_RISK_CHECK_IN_GATEWAY,
            Entity::FRAUD_TYPE => Type::SUSPECTED,
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
