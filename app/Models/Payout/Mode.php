<?php

namespace RZP\Models\Payout;

use RZP\Constants;
use RZP\Error\ErrorCode;
use RZP\Models\Settlement;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Merchant\Balance\AccountType;

class Mode
{
    const RTGS      = 'RTGS';
    const IMPS      = 'IMPS';
    const NEFT      = 'NEFT';
    const IFT       = 'IFT';
    const UPI       = 'UPI';
    const AMAZONPAY = 'amazonpay';
    const DUITNOW   = 'duitnow';
    const IBG       = 'IBG';

    // We will be storing mode 'card' for payouts through
    // M2P, but we will be supporting 'Card', 'cArd', 'CaRD' etc in request body
    const CARD  = 'card';

    protected static $allSupportedModes = [
        Constants\Country::IN => [
            self::RTGS,
            self::IMPS,
            self::NEFT,
            self::IFT,
            self::UPI,
            self::AMAZONPAY,
            self::CARD,
        ],
        Constants\Country::MY => [
            self::DUITNOW,
            self::IBG,
        ]
    ];

    protected static $supportedModesForSmartRouting = [
        self::RTGS,
        self::IMPS,
        self::NEFT,
        self::IFT,
        self::UPI,
    ];

    public static function validateMode(string $mode, $merchantCountry = Constants\Country::IN)
    {
        if (self::isValid($mode, $merchantCountry) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_INVALID_MODE,
                null,
                [
                    'mode' => $mode,
                ]);
        }
    }

    public static function isValidModeForSmartRouting(string $mode)
    {
        return (in_array($mode, self::$supportedModesForSmartRouting, true) === true);
    }

    protected static function getAllSupportedPayoutChannelsWithModes()
    {
        return [
            Settlement\Channel::YESBANK   => [
                Constants\Entity::VPA           =>  [
                    self::UPI,
                ],
                Constants\Entity::BANK_ACCOUNT  =>  [
                    self::RTGS,
                    self::IMPS,
                    self::NEFT,
                    //self::IFT,
                ],
                Constants\Entity::CARD          =>  [
                    self::IMPS,
                    self::UPI,
                    self::NEFT,
                ],
                Constants\Entity::WALLET_ACCOUNT  =>  [
                    self::AMAZONPAY,
                ]
            ],
            Settlement\Channel::CITI      => [
                Constants\Entity::BANK_ACCOUNT  =>  [
                    self::RTGS,
                    self::IMPS,
                    self::NEFT,
                    self::IFT,
                ],
                Constants\Entity::CARD          =>  [
                    self::IMPS,
                    self::NEFT,
                ],
                Constants\Entity::WALLET_ACCOUNT  =>  [
                    self::AMAZONPAY,
                ]
            ],
            Settlement\Channel::ICICI     => [
                Constants\Entity::VPA           =>  [
                    self::UPI,
                ],
                Constants\Entity::BANK_ACCOUNT  =>  [
                    self::IMPS,
                    self::NEFT,
                    self::RTGS,
                ],
                Constants\Entity::CARD          =>  [
                    self::IMPS,
                    self::UPI,
                    self::NEFT,
                ],
                Constants\Entity::WALLET_ACCOUNT  =>  [
                    self::AMAZONPAY,
                ]
            ],
            Settlement\Channel::RBL       => [
                Constants\Entity::BANK_ACCOUNT  =>  [
                    self::RTGS,
                    self::IMPS,
                    self::NEFT,
                    self::IFT,
                ],
                Constants\Entity::CARD          =>  [
                    self::IMPS,
                    self::NEFT,
                ],
                Constants\Entity::WALLET_ACCOUNT  =>  [
                    self::AMAZONPAY,
                ]
            ],
            Settlement\Channel::M2P       => [
                Constants\Entity::CARD          => [
                    self::CARD,
                ]
            ]
        ];
    }

    protected static function getAllSupportedPayoutChannelsWithModesWithAccountType()
    {
        return [
            AccountType::SHARED => [
                Settlement\Channel::YESBANK => [
                    Constants\Entity::VPA            => [
                        self::UPI,
                    ],
                    Constants\Entity::BANK_ACCOUNT   => [
                        self::RTGS,
                        self::IMPS,
                        self::NEFT,
                        //self::IFT,
                    ],
                    Constants\Entity::CARD           => [
                        self::IMPS,
                        self::UPI,
                        self::NEFT,
                    ],
                    Constants\Entity::WALLET_ACCOUNT => [
                        self::AMAZONPAY,
                    ]
                ],
                Settlement\Channel::CITI    => [
                    Constants\Entity::BANK_ACCOUNT   => [
                        self::RTGS,
                        self::IMPS,
                        self::NEFT,
                        self::IFT,
                    ],
                    Constants\Entity::CARD           => [
                        self::IMPS,
                        self::NEFT,
                    ],
                    Constants\Entity::WALLET_ACCOUNT => [
                        self::AMAZONPAY,
                    ]
                ],
                Settlement\Channel::ICICI   => [
                    Constants\Entity::VPA            => [
                        self::UPI,
                    ],
                    Constants\Entity::BANK_ACCOUNT   => [
                        self::IMPS,
                        self::NEFT,
                        self::RTGS,
                    ],
                    Constants\Entity::CARD           => [
                        self::IMPS,
                        self::UPI,
                        self::NEFT,
                    ],
                    Constants\Entity::WALLET_ACCOUNT => [
                        self::AMAZONPAY,
                    ]
                ],
                Settlement\Channel::M2P     => [
                    Constants\Entity::CARD           => [
                        self::CARD,
                    ],
                ],
                Settlement\Channel::RZPX    => [
                    Constants\Entity::BANK_ACCOUNT   => [
                        self::IFT,
                    ],
                ],
                Settlement\Channel::OCBC    => [
                    Constants\Entity::BANK_ACCOUNT  => [
                        self::DUITNOW,
                        self::IBG,
                    ]
                ],
            ],
            AccountType::DIRECT => [
                Settlement\Channel::RBL   => [
                    Constants\Entity::VPA          => [
                       self::UPI,
                    ],
                    Constants\Entity::BANK_ACCOUNT => [
                        self::RTGS,
                        self::IMPS,
                        self::NEFT,
                        self::IFT,
                    ],
                    Constants\Entity::CARD         => [
                        self::IMPS,
                        self::NEFT,
                        self::UPI,
                    ]
                ],
                Settlement\Channel::ICICI => [
                    Constants\Entity::VPA          => [
                        self::UPI,
                    ],
                    Constants\Entity::BANK_ACCOUNT => [
                        self::RTGS,
                        self::IMPS,
                        self::NEFT,
                        self::IFT,
                    ],
                    Constants\Entity::CARD         => [
                        self::IMPS,
                        self::NEFT,
                        self::UPI,
                    ],
                ],
                Settlement\Channel::AXIS  => [
                    Constants\Entity::VPA          => [
                        self::UPI,
                    ],
                    Constants\Entity::BANK_ACCOUNT => [
                        self::RTGS,
                        self::NEFT,
                        self::IMPS,
                        self::IFT,
                    ],
                    Constants\Entity::CARD => [
                        self::IMPS,
                        self::NEFT,
                        self::UPI
                    ],
                ],
                Settlement\Channel::YESBANK  => [
                    Constants\Entity::VPA          => [
                        self::UPI,
                    ],
                    Constants\Entity::BANK_ACCOUNT => [
                        self::RTGS,
                        self::NEFT,
                        self::IMPS,
                        self::IFT,
                    ],
                    Constants\Entity::CARD => [
                        self::IMPS,
                        self::NEFT,
                        self::UPI
                    ],
                ],
            ],
        ];
    }

    protected static function isValid(string $mode, string $merchantCountry): bool
    {
        return (in_array($mode, self::$allSupportedModes[$merchantCountry]) === true);
    }

    public static function validateChannelAndModeForPayouts(string $channel = null,
                                                            string $destinationType = null,
                                                            string $mode = null,
                                                            string $accountType = null,
                                                            Merchant $merchant = null) : bool
    {
        Settlement\Channel::validate($channel, $merchant);

        $allChannelsWithModes = [];
        $modesSupportedForChannel = [];


        // Keeping this here as payouts which are made on primary balance don't have
        // account type so not touching the logic on which they operate. One example
        // of this is the test case - testPaymentPayoutPartial
        if ($accountType === null)
        {
            $allChannelsWithModes = self::getAllSupportedPayoutChannelsWithModes();

            $modesSupportedForChannel = $allChannelsWithModes[$channel][$destinationType] ?? [];
        }
        else
        {
            $allChannelsWithModes = self::getAllSupportedPayoutChannelsWithModesWithAccountType();

            $modesSupportedForChannel = $allChannelsWithModes[$accountType][$channel][$destinationType] ?? [];
        }

        return (in_array($mode, $modesSupportedForChannel, true));
    }
}
