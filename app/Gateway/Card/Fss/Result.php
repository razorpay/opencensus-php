<?php

namespace RZP\Gateway\Card\Fss;

final class Result
{
    const INCORRECT_PIN                     = 'incorrect pin';
    const LOST_CARD                         = 'lost card';
    const SUSPECT_FRAUD                     = 'suspect fraud';
    const OVER_DAILY_LIMIT                  = 'over daily limit';
    const PIN_TRIES_EXCEEDED                = 'pin tries exceeded';
    const NOT_SUFFICIENT_FUND               = 'not sufficient fund';
    const INVALID_EXPIRATION_DATE           = 'invalid expiration date';
    const ISSUER_DOWN                       = 'issuer down';
    const RESERVED_FOR_PRIVATE_USE          = 'reserved for private use';
    const TRAN_NOT_PERMITTED                = 'tran not permitted';
    const NO_CARD_RECORD                    = 'no card record';
    const NOT_CAPTURED                      = 'not captured';
    const WITHDRAWAL_EXCEEDED               = 'exceeds withdrawal frequency';
    const NULL                              = 'null';
    const CAF_STATUS_0_OR_9                 = 'caf status= 0 or 9';
    const FAILURE                           = 'failure';
    const INTERNAL_ERROR                    = 'internal error: java.lang.nullpointerexception';
    const ERROR_WHILE_CONNECTING_GATEWAY    = 'error while connecting payment gateway';

    public static $resultToErrorCodeMap = [
        Result::INCORRECT_PIN                           => ErrorCodes::RP00001,
        Result::LOST_CARD                               => ErrorCodes::RP00002,
        Result::SUSPECT_FRAUD                           => ErrorCodes::RP00003,
        Result::OVER_DAILY_LIMIT                        => ErrorCodes::RP00004,
        Result::PIN_TRIES_EXCEEDED                      => ErrorCodes::RP00005,
        Result::NOT_SUFFICIENT_FUND                     => ErrorCodes::RP00006,
        Result::INVALID_EXPIRATION_DATE                 => ErrorCodes::RP00007,
        Result::ISSUER_DOWN                             => ErrorCodes::RP00008,
        Result::RESERVED_FOR_PRIVATE_USE                => ErrorCodes::RP00009,
        Result::TRAN_NOT_PERMITTED                      => ErrorCodes::RP00010,
        Result::NO_CARD_RECORD                          => ErrorCodes::RP00011,
        Result::NOT_CAPTURED                            => ErrorCodes::RP00012,
        Result::WITHDRAWAL_EXCEEDED                     => ErrorCodes::RP00013,
        Result::NULL                                    => ErrorCodes::RP00014,
        Result::CAF_STATUS_0_OR_9                       => ErrorCodes::RP00015,
        Result::FAILURE                                 => ErrorCodes::RP00016,
        Result::INTERNAL_ERROR                          => ErrorCodes::RP00017,
        Result::ERROR_WHILE_CONNECTING_GATEWAY          => ErrorCodes::RP00018
    ];

    public static function getErrorCode($code)
    {
        return self::$resultToErrorCodeMap[strtolower($code)];
    }
}
