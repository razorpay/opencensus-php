<?php

namespace RZP\Models\FundTransfer;

use RZP\Exception\BadRequestValidationFailureException;

class Mode
{
    const RTGS = 'RTGS';
    const IMPS = 'IMPS';
    const NEFT = 'NEFT';
    const IFT  = 'IFT';

    public static $modeMap = [
        self::RTGS  => self::RTGS,
        self::IMPS  => self::IMPS,
        self::NEFT  => self::NEFT,
        self::IFT   => self::IFT,
    ];

    /**
     * gives the list of modes which are allowed for 24x7 transfers
     *
     * @return array
     */
    public static function get24x7TransferModes(): array {
        return [
            self::IMPS,
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
}
