<?php

namespace RZP\Models\FundTransfer;

use RZP\Constants;
use RZP\Models\Card;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Settings;
use RZP\Models\Base\Core;
use RZP\Models\Settlement;
use RZP\Models\Card\Issuer;
use RZP\Models\Card\Network;
use RZP\Models\FundAccount\Type;
use RZP\Models\FundTransfer\M2P\M2PConfigs;
use RZP\Exception\BadRequestValidationFailureException;

/**
 * Class Mode
 *
 * @package RZP\Models\FundTransfer
 */
class Mode extends Core
{
    const RTGS = 'RTGS';
    const IMPS = 'IMPS';
    const NEFT = 'NEFT';
    const IFT  = 'IFT';

    // We will be storing mode 'card' for payouts through
    // M2P, but we will be supporting 'Card', 'cArd', 'CaRD' etc in request body
    const CARD = 'card';

    const UPI = 'UPI';

    // Card Transfer mode for M2P Integration via FTS
    const CT  = 'CT';
    const M2P = Settlement\Channel::M2P;

    const CHANNEL  = 'channel';
    const MODES    = 'modes';
    const MODE     = 'mode';
    const PRIORITY = 'priority';

    const BLACKLISTED = 'blacklisted';
    const ERROR_CODE  = 'error_code';

    // Amazon Pay mode for Wallet account Integrations via FTS
    const AMAZONPAY = 'amazonpay';
    const FTS_WALLET_TRANSFERS_MODE = 'WALLET_TRANSFER';

    protected static $modeMap = [
        self::RTGS      => self::RTGS,
        self::IMPS      => self::IMPS,
        self::NEFT      => self::NEFT,
        self::IFT       => self::IFT,
        self::UPI       => self::UPI,
        self::CT        => self::CT,
        self::AMAZONPAY => self::AMAZONPAY,
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
            self::CT,
            self::CARD,
        ],
        Type::WALLET_ACCOUNT => [
            self::AMAZONPAY,
        ],
    ];

    protected static $accountTypePublicNameMap = [
        Type::BANK_ACCOUNT   => 'Bank Account',
        Type::VPA            => 'UPI',
        Type::CARD           => 'Card',
        Type::WALLET_ACCOUNT => 'Wallet',
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
                self::NEFT,
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

        Issuer::AUBL => [
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
                self::UPI,
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
            return ((array_key_exists($networkCode, self::$issuerModeMap[$issuer]) === true) ?
                self::$issuerModeMap[$issuer][$networkCode] : self::$issuerModeMap[$issuer][Attempt\Constants::DEFAULT_NETWORK]);
        }

        return [];
    }

    public static function validateModeOfAccountType($mode, $accountType)
    {
        if ((isset(self::$modeAccountTypeMap[$accountType]) === false) or
            (in_array($mode, self::$modeAccountTypeMap[$accountType], true) === false))
        {
            $accountTypePublic = self::$accountTypePublicNameMap[$accountType] ?? $accountType;
            throw new BadRequestValidationFailureException("Invalid combination of payout mode ($mode) and beneficiary account type ($accountTypePublic)");
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
            self::AMAZONPAY,
            self::CARD,
        ];
    }

    public static function getAllFTSModes(): array
    {
        return [
            self::RTGS,
            self::IMPS,
            self::NEFT,
            self::IFT,
            self::UPI,
            self::FTS_WALLET_TRANSFERS_MODE,
            self::CT,
        ];
    }

    public static function get24x7FtsTransferModes(): array
    {
        return [
            self::IMPS,
            self::IFT,
            self::UPI,
            self::CT,
            self::AMAZONPAY,
        ];
    }

    protected function getSettingsAccessor($merchant)
    {
        return Settings\Accessor::for($merchant, Settings\Module::M2P_TRANSFER);
    }

    // checking if merchant is blacklisted on the payout end for specific purpose/product.
    // also, checking if the merchant is blacklisted by m2p to make transfers to particular card network.
    public function m2pMerchantBlacklisted(Merchant\Entity $merchant, string $network = "", string $purposeType = "")
    {
        // we are storing the merchant level blacklist(purpose/network) in the `settings` table in key value format.
        $accessor    = self::getSettingsAccessor($merchant);
        $allSettings = $accessor->all();

        $errorCode   = '';
        $blacklisted = false;

        $this->trace->info(TraceCode::M2P_MERCHANT_BLACKLISTED_SETTINGS, [
            Constants\Entity::PRODUCT  => $purposeType,
            Constants\Entity::MERCHANT => $merchant->getId(),
            Card\Entity::NETWORK       => $network,
        ]);

        // converting to the strict cases as used in db.
        $purposeType = strtolower($purposeType);
        $networkCode = Network::getCode($network);
        $networkCode = strtoupper($networkCode);

        $productLevelBlacklist = ((isset($allSettings[$purposeType]) === true) and
                                 ($allSettings[$purposeType] === 'true'));

        if ($productLevelBlacklist === true)
        {
            $blacklisted = true;
            $errorCode   = ErrorCode::BAD_REQUEST_M2P_MERCHANT_BLACKLISTED_FOR_PRODUCT;

            $this->trace->info(
                TraceCode::M2P_MERCHANT_BLACKLISTED_FOR_PRODUCT,
                [
                    Constants\Entity::MERCHANT => $merchant->getId()
                ]);

            (new Metric)->pushM2PMerchantBlacklist(strtolower(TraceCode::M2P_MERCHANT_BLACKLISTED_FOR_PRODUCT), [
                'network' => $networkCode,
                'purpose' => $purposeType,
            ]);

            return [
                self::BLACKLISTED => $blacklisted,
                self::ERROR_CODE  => $errorCode,
            ];
        }

        $networkLevelBlacklist = ((isset($allSettings[$networkCode]) === true) and
                                 ($allSettings[$networkCode] === 'true'));

        if ($networkLevelBlacklist === true)
        {
            $blacklisted = true;
            $errorCode   = ErrorCode::BAD_REQUEST_M2P_MERCHANT_BLACKLISTED_BY_NETWORK;

            $this->trace->info(
                TraceCode::M2P_MERCHANT_BLACKLISTED_BY_NETWORK,
                [
                    Constants\Entity::MERCHANT => $merchant->getId()
                ]);

            (new Metric)->pushM2PMerchantBlacklist(strtolower(TraceCode::M2P_MERCHANT_BLACKLISTED_BY_NETWORK), [
                'network' => $networkCode,
                'purpose' => $purposeType,
            ]);

            return [
                self::BLACKLISTED => $blacklisted,
                self::ERROR_CODE  => $errorCode,
            ];
        }

        $this->trace->info(TraceCode::M2P_TRANSFER_ALLOWED, [
            self::BLACKLISTED => $blacklisted,
            self::ERROR_CODE  => $errorCode,
        ]);

        return [
            self::BLACKLISTED => $blacklisted,
            self::ERROR_CODE  => $errorCode,
        ];
    }

    // following function returns the supported modes for m2p channel
    // given particular issuer, network, cardtype and iin.
    public function getM2PSupportedModes($issuer = null, $networkCode = null, $cardType = null, $iin = null)
    {
        $supportedIinsConfig     = [];
        $supportedNetworksConfig = [];
        $supportedTypesConfig    = [];

        // we first match the given issuer in the m2p config.
        $supportedIssuersConfig = self::getSupportedM2PIssuerConfigs($issuer);

        // for all the matched issuers, we find the if there are supported networks in config.
        foreach ($supportedIssuersConfig as $issuerConfig)
        {
            $supportedNetworksConfig = array_merge($supportedNetworksConfig, self::getSupportedNetworkConfig($networkCode, $issuerConfig));
        }

        // for all the supported networks, we find if there are supported card types in config.
        foreach ($supportedNetworksConfig as $networkConfig)
        {
            $supportedTypesConfig = array_merge($supportedTypesConfig, self::getSupportedCardTypesConfig($cardType, $networkConfig));
        }

        // for all the supported card types, we find if there are supported iins in the config.
        foreach ($supportedTypesConfig as $cardTypeConfig)
        {
            $supportedIinsConfig = array_merge($supportedIinsConfig, self::getSupportedIinConfig($iin, $cardTypeConfig));
        }

        // in case we have multiple modes for the supported channel, we return them as per the priority.
        // 1 priority being the highest i.e first element of the following returning array having
        // highest priority.
        // If there are multiple modes of the same channel(m2p) available, we can return them alongside so
        // that the consumer can try to do payout/refund via them.
        $priorityCounter = 0;

        $supportedModesResponse = [];

        foreach ($supportedIinsConfig as $iinConfig)
        {
            $supportedChannel = $iinConfig[self::CHANNEL];
            $supportedModes   = $iinConfig[self::MODES];

            foreach ($supportedModes as $mode)
            {
                $priorityCounter = $priorityCounter + 1;

                array_push($supportedModesResponse, [
                    self::PRIORITY => $priorityCounter,
                    self::CHANNEL  => $supportedChannel,
                    self::MODE     => $mode
                ]);
            }
        }

        return $supportedModesResponse;
    }

    // getting supported issuer config as per given issuer for m2p.
    public function getSupportedM2PIssuerConfigs($issuer)
    {
        $supportedIssuersConfig = [];

        $issuers = [
            $issuer,
            M2PConfigs::DEFAULT_ISSUER,
        ];

        foreach ($issuers as $issuer)
        {
            $issuerConfig = ((isset(M2PConfigs::$m2pSupportedModesMap[$issuer]) === true) ?
                            M2PConfigs::$m2pSupportedModesMap[$issuer] : null);

            if ($issuerConfig !== null)
            {
                array_push($supportedIssuersConfig, $issuerConfig);
            }
        }

        return $supportedIssuersConfig;
    }

    // getting supported networks config as per given issuer and network code for m2p.
    public function getSupportedNetworkConfig(string $networkCode, array $supportedIssuer)
    {
        $supportedNetworksConfig = [];

        $networks = [
            $networkCode,
            M2PConfigs::DEFAULT_NETWORK,
        ];

        foreach ($networks as $network)
        {
            $networkConfig = (isset($supportedIssuer[$network]) === true) ? $supportedIssuer[$network] : null;

            if ($networkConfig !== null)
            {
                array_push($supportedNetworksConfig, $networkConfig);
            }
        }

        return $supportedNetworksConfig;
    }

    // getting supported card types for given supported network and card type for m2p.
    public function getSupportedCardTypesConfig(string $cardType, array $supportedNetwork)
    {
        $supportedCardTypesConfigs = [];

        $cardTypes = [
            $cardType,
            M2PConfigs::DEFAULT_TYPE,
        ];

        foreach ($cardTypes as $cardType)
        {
            $cardTypeConfig = (isset($supportedNetwork[$cardType]) === true) ? $supportedNetwork[$cardType] : null;

            if ($cardTypeConfig !== null)
            {
                array_push($supportedCardTypesConfigs, $cardTypeConfig);
            }
        }

        return $supportedCardTypesConfigs;
    }


    // getting supported iin/bin config based on supported card type and given iin for m2p.
    public function getSupportedIinConfig(string $iin, array $supportedCardType)
    {
        $supportedIinConfigs = [];

        $iins = [
            $iin,
            M2PConfigs::DEFAULT_IIN,
        ];

        foreach ($iins as $iin)
        {
            $iinConfig = (isset($supportedCardType[$iin]) === true) ? $supportedCardType[$iin] : null;

            if ($iinConfig !== null)
            {
                array_push($supportedIinConfigs, $iinConfig);
            }
        }

        return $supportedIinConfigs;
    }

    // gets m2p specific config for given issuer, network, card_type, iin.
    public function getM2PSupportedChannelModeConfig($issuer = null, $network = null, $cardType = null, $iin = null)
    {
        $this->trace->info(TraceCode::M2P_GET_SUPPORTED_MODES_CALL,
        [
            "issuer"   => $issuer,
            "network"  => $network,
            "cardType" => $cardType,
            "iin"      => $iin,
        ]);

        // convert network to networkCode
        $networkCode = Network::getCode($network);
        $networkCode = strtoupper($networkCode);

        $supportedModes = self::getM2PSupportedModes($issuer, $networkCode, $cardType, $iin);

        $this->trace->info(TraceCode::M2P_GET_SUPPORTED_MODES_CALL_VALUES,
        [
            "supported_modes" => $supportedModes
        ]);

        return $supportedModes;
    }
}
