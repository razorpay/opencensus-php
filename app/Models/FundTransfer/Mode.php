<?php

namespace RZP\Models\FundTransfer;

use RZP\Models\Card\Issuer;
use RZP\Models\Card\Network;
use RZP\Models\FundAccount\Type;
use RZP\Exception\BadRequestValidationFailureException;

/**
 * Class Mode
 *
 * @package RZP\Models\FundTransfer
 */
class Mode
{
    const RTGS = 'RTGS';
    const IMPS = 'IMPS';
    const NEFT = 'NEFT';
    const IFT  = 'IFT';

    const UPI = 'UPI';

    protected static $modeMap = [
        self::RTGS => self::RTGS,
        self::IMPS => self::IMPS,
        self::NEFT => self::NEFT,
        self::IFT  => self::IFT,
        self::UPI  => self::UPI,
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
            self::NEFT,
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
        Issuer::ICIC => [
            Attempt\Constants::DEFAULT_NETWORK => [
                self::UPI,
                self::NEFT
            ]
        ],
        Issuer::UTIB => [
            Attempt\Constants::DEFAULT_NETWORK => [
                self::IMPS,
                self::NEFT
            ]
        ],
        Issuer::HDFC => [
            Attempt\Constants::DEFAULT_NETWORK => [
                self::IMPS,
                self::NEFT
            ]
        ],
        Issuer::KKBK => [
            Attempt\Constants::DEFAULT_NETWORK => [
                self::IMPS,
                self::NEFT
            ]
        ],
        Issuer::ANDB => [
            Attempt\Constants::DEFAULT_NETWORK => [
                self::IMPS,
                self::NEFT
            ]
        ],
        Issuer::INDB => [
            Attempt\Constants::DEFAULT_NETWORK => [
                self::IMPS,
                self::NEFT
            ]
        ],
        Issuer::SCBL => [
            //Adding Networkcode as a key since Amex card network uses SCBL issuer internally
            //and It can have other issuers as well. Also by this we distinguish with other cards issued by SCBL
            Network::AMEX                      => [
                self::UPI,
                self::IMPS,
                self::NEFT
            ],
            Attempt\Constants::DEFAULT_NETWORK => [
                self::NEFT
            ]
        ],
        Issuer::CITI => [
            Attempt\Constants::DEFAULT_NETWORK => [
                self::NEFT
            ]
        ],
        Issuer::HSBC => [
            Attempt\Constants::DEFAULT_NETWORK => [
                self::NEFT
            ]
        ],
        Issuer::PUNB => [
            Attempt\Constants::DEFAULT_NETWORK => [
                self::NEFT
            ]
        ],
        Issuer::CNRB => [
            Attempt\Constants::DEFAULT_NETWORK => [
                self::NEFT
            ]
        ],
        Issuer::UBIN => [
            Attempt\Constants::DEFAULT_NETWORK => [
                self::NEFT
            ]
        ],
        Issuer::BKID => [
            Attempt\Constants::DEFAULT_NETWORK => [
                self::NEFT
            ]
        ],
        Issuer::CORP => [
            Attempt\Constants::DEFAULT_NETWORK => [
                self::NEFT
            ]
        ],
        Issuer::SYNB => [
            Attempt\Constants::DEFAULT_NETWORK => [
                self::NEFT
            ]
        ],
        Issuer::IOBA => [
            Attempt\Constants::DEFAULT_NETWORK => [
                self::NEFT
            ]
        ],
        Issuer::BOFA => [
            Attempt\Constants::DEFAULT_NETWORK => [
                self::NEFT
            ]
        ],
        Issuer::IBKL => [
            Attempt\Constants::DEFAULT_NETWORK => [
                self::NEFT
            ]
        ],
        Issuer::BARB => [
            Attempt\Constants::DEFAULT_NETWORK => [
                self::NEFT
            ]
        ],
        Issuer::YESB => [
            Attempt\Constants::DEFAULT_NETWORK => [
                self::NEFT
            ]
        ],
        Issuer::SBIN => [
            Attempt\Constants::DEFAULT_NETWORK => [
                self::NEFT
            ]
        ],
        Issuer::RATN => [
            Attempt\Constants::DEFAULT_NETWORK => [
                self::NEFT
            ]
        ],

        /*
        This is declared as a default issuer for those Amex network cards that don't have any issuer in order to check
        which payout modes are supported for such cards. Their issuer remains null only, we just validate mode based on
        the mapping defined here.
        */
        Attempt\Constants::DEFAULT_ISSUER => [
            Network::AMEX => [
                self::IMPS,
                self::NEFT
            ],
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

    public static function getSupportedModesMap(): array
    {
        return self::$issuerModeMap;
    }

    /**
     *  if the network exists in issuer mode map then consider the mode given for the same
     *  else consider default
     *
     *  if not found return empty array
     *
     * @param $issuer
     * @param $networkCode
     *
     * @return mixed|array
     */
    public static function getSupportedModes($issuer, $networkCode): array
    {
        if (array_key_exists($issuer, self::$issuerModeMap) === true)
        {
            return array_key_exists($networkCode, self::$issuerModeMap[$issuer]) === true ?
                self::$issuerModeMap[$issuer][$networkCode] : self::$issuerModeMap[$issuer][Attempt\Constants::DEFAULT_NETWORK];
        }

        return [];
    }

    public static function validateModeOfAccountType($mode, $accountType)
    {
        if ((isset(self::$modeAccountTypeMap[$accountType]) === false) or
            (in_array($mode, self::$modeAccountTypeMap[$accountType], true) === false))
        {
            throw new BadRequestValidationFailureException("$mode is not a valid mode for account type $accountType");
        }
    }

    /**
     * @param string $mode
     * @param string $issuer
     * @param string $networkCode
     *
     * @throws BadRequestValidationFailureException
     */
    public static function validateModeOfIssuer(string $mode, string $issuer, string $networkCode)
    {
        $supportedModes = self::getSupportedModes($issuer, $networkCode);

        if ((isset($supportedModes) === false) or
            (in_array($mode, $supportedModes, true) === false))
        {
            if ($issuer === Attempt\Constants::DEFAULT_ISSUER)
            {
                throw new BadRequestValidationFailureException("$mode is not a valid mode for issuer AMEX");
            }

            throw new BadRequestValidationFailureException("$mode is not a valid mode for issuer $issuer");
        }
    }

    /**
     * gives the list of modes which are allowed for 24x7 transfers
     *
     * @return array
     */
    public static function get24x7TransferModes(): array
    {
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

    public static function get24x7FtsTransferModes(): array
    {
        return [
            self::IMPS,
            self::IFT,
            self::UPI,
        ];
    }
}
