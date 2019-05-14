<?php

namespace RZP\Models\FundTransfer;

use RZP\Models\Card\Issuer;
use RZP\Models\FundAccount\Type;
use RZP\Exception\BadRequestValidationFailureException;

class Mode
{
    const RTGS = 'RTGS';
    const IMPS = 'IMPS';
    const NEFT = 'NEFT';
    const IFT  = 'IFT';

    const UPI = 'UPI';

    protected static $modeMap = [
        self::RTGS  => self::RTGS,
        self::IMPS  => self::IMPS,
        self::NEFT  => self::NEFT,
        self::IFT   => self::IFT,
        self::UPI   => self::UPI,
    ];

    protected static $modeAccountTypeMap = [
        Type::BANK_ACCOUNT => [
            self::RTGS,
            self::IMPS,
            self::NEFT,
            self::IFT,
        ],
        Type::VPA => [
            self::UPI,
        ],
        Type::CARD => [
            self::IMPS,
            self::UPI,
        ]
    ];

    /**
     * Don't have nodal bank specific issuer map since SHK confirmed
     * that all nodal banks will support the same list of card issuers.
     * (issuer supporting bank transfer is not at nodal bank level.)
     *
     * @var array
     */
    protected static $issuerModeMap = [
        Issuer::UTIB    => [
            self::IMPS
        ],
        Issuer::HDFC    => [
            self::IMPS
        ],
        Issuer::INDB    => [
            self::IMPS
        ],
        Issuer::KKBK    => [
            self::IMPS
        ],
        Issuer::ANDB    => [
            self::IMPS
        ],
        Issuer::ICIC    => [
            self::UPI
        ],
    ];

    protected static $allTimeTransferModes = [
        self::IMPS,
        self::IFT
    ];

    public static function getSupportedIssuers()
    {
        return array_keys(self::$issuerModeMap);
    }

    public static function validateModeOfAccountType($mode, $accountType)
    {
        $expectedAccountType = get_key_from_subarray_match($mode, self::$modeAccountTypeMap);

        if ($accountType !== $expectedAccountType)
        {
            throw new BadRequestValidationFailureException("$mode is not a valid mode for account type $accountType");
        }
    }

    public static function validateModeOfIssuer(string $mode = null, string $issuer = null)
    {
        if ((isset(self::$issuerModeMap[$issuer]) === false) or
            (in_array($mode, self::$issuerModeMap[$issuer], true) === false))
        {
            throw new BadRequestValidationFailureException("$mode is not a valid mode for issuer $issuer");
        }
    }

    /**
     * gives the list of modes which are allowed for 24x7 transfers
     *
     * @return array
     */
    public static function get24x7TransferModes(): array {
        return self::$allTimeTransferModes;
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
