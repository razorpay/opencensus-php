<?php

namespace RZP\Models\FundTransfer;

use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\FundAccount\Type;

class Mode
{
    const RTGS = 'RTGS';
    const IMPS = 'IMPS';
    const NEFT = 'NEFT';
    const IFT  = 'IFT';

    const UPI = 'UPI';

    public static $modeMap = [
        self::RTGS  => self::RTGS,
        self::IMPS  => self::IMPS,
        self::NEFT  => self::NEFT,
        self::IFT   => self::IFT,
        self::UPI   => self::UPI,
    ];

    public static $modeAccountTypeMap = [
        Type::BANK_ACCOUNT => [
            self::RTGS,
            self::IMPS,
            self::NEFT,
            self::IFT,
        ],
        Type::VPA => [
            self::UPI,
        ],
    ];

    public static function validateModeOfAccountType($mode, $accountType) {
        $expectedAccountType = get_key_from_subarray_match($mode, self::$modeAccountTypeMap);

        if ($accountType !== $expectedAccountType)
        {
            throw new BadRequestValidationFailureException("$mode is not a valid mode for account type $accountType");
        }
    }

    /**
     * gives the list of modes which are allowed for 24x7 transfers
     *
     * @return array
     */
    public static function get24x7TransferModes(): array {
        return [
            self::IMPS,
            self::IFT,
        ];
    }

    public static function isValid(string $mode): bool
    {
        $key = __CLASS__ . '::' . strtoupper($mode);

        return ((defined($key) === true) and (constant($key) === $mode));
    }

    public static function validateMode(string $mode)
    {
        if (self::isValid($mode) === false)
        {
            throw new BadRequestValidationFailureException('Not a valid mode: ' . $mode);
        }
    }

    public static function getInternalModeFromExternalMode(string $externalMode = null)
    {
        $internalMode = array_search($externalMode, static::$modeMap, true);

        if ($internalMode === false)
        {
            return $externalMode;
        }

        return $internalMode;
    }

    public static function getExternalModeFromInternalMode(string $internalMode = null)
    {
        return static::$modeMap[$internalMode] ?? $internalMode;
    }

    public static function getAll(): array
    {
        return [
            self::RTGS,
            self::IMPS,
            self::NEFT,
            self::IFT,
            self::UPI,
        ];
    }
}
