<?php

namespace RZP\Models\FundTransfer\M2P;

use RZP\Models\Card\Issuer;
use RZP\Models\Card\Network;
use RZP\Models\FundTransfer\Mode;
use RZP\Models\Card\Type as CardType;

/**
 * Class M2PConfigs
 *
 * @package RZP\Models\FundTransfer
 */
class M2PConfigs
{
    const DEFAULT_ISSUER  = "default_issuer";
    const DEFAULT_NETWORK = "default_network";
    const DEFAULT_TYPE    = "default_type";
    const DEFAULT_IIN     = "default_iin";

    // Following map contains the configs as supported by various issuers. It forms a tree like structure.
    // There are cases where a certain network is supported on all issuers. In such cases, we keep issuer as default_issuer.
    // Similarly, there are certain networks which support on any issuer and specific iin. For such, we define config at iin level.
    // Some issuers support all iins for a particular network, in such cases we define default_iin.
    public static $m2pSupportedModesMap = [
        Issuer::BKID => [
            Network::VISA => [
                CardType::DEBIT => [
                    self::DEFAULT_IIN => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                ]
            ],
        ],

        Issuer::MAHB => [
            Network::VISA => [
                CardType::DEBIT => [
                    self::DEFAULT_IIN => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                ]
            ],
        ],

        Issuer::CNRB => [
            Network::VISA => [
                CardType::DEBIT => [
                    self::DEFAULT_IIN => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                ]
            ],
        ],

        Issuer::DCBL => [
            Network::VISA => [
                CardType::DEBIT => [
                    self::DEFAULT_IIN => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                ]
            ],
        ],

        Issuer::CBIN => [
            Network::VISA => [
                CardType::DEBIT => [
                    self::DEFAULT_IIN => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                ]
            ],
        ],

        Issuer::ICIC => [
            Network::VISA => [
                CardType::DEBIT => [
                    self::DEFAULT_IIN => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                ]
            ],
        ],

        Issuer::IBKL => [
            Network::VISA => [
                CardType::DEBIT => [
                    self::DEFAULT_IIN => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                ]
            ],
        ],

        Issuer::IDFB => [
            Network::VISA => [
                CardType::DEBIT => [
                    self::DEFAULT_IIN => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                ]
            ],
        ],

        Issuer::INDB => [
            Network::VISA => [
                CardType::DEBIT => [
                    self::DEFAULT_IIN => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                ]
            ],
            Network::MC   => [
                CardType::CREDIT => [
                    self::DEFAULT_IIN => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                ]
            ],
        ],

        Issuer::ORBC => [
            Network::VISA => [
                CardType::DEBIT => [
                    self::DEFAULT_IIN => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                ]
            ],
        ],

        Issuer::SCBL => [
            Network::VISA => [
                CardType::DEBIT => [
                    self::DEFAULT_IIN => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                ]
            ],
        ],

        Issuer::SBIN => [
            Network::VISA => [
                CardType::DEBIT  => [
                    self::DEFAULT_IIN => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                ],
                CardType::CREDIT => [
                    self::DEFAULT_IIN => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                ]
            ],
            Network::MC   => [
                CardType::CREDIT => [
                    self::DEFAULT_IIN => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                ]
            ],
        ],

        Issuer::FDRL => [
            Network::VISA => [
                CardType::DEBIT => [
                    self::DEFAULT_IIN => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                ]
            ],
        ],

        Issuer::SIBL => [
            Network::VISA => [
                CardType::DEBIT => [
                    self::DEFAULT_IIN => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                ]
            ],
        ],

        Issuer::UTIB => [
            Network::VISA => [
                CardType::DEBIT => [
                    self::DEFAULT_IIN => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                ]
            ],
            Network::MC   => [
                CardType::CREDIT => [
                    self::DEFAULT_IIN => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                ]
            ],
        ],

        Issuer::KKBK => [
            Network::VISA => [
                CardType::DEBIT => [
                    self::DEFAULT_IIN => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                ]
            ],
        ],

        Issuer::CORP         => [
            Network::VISA => [
                CardType::DEBIT => [
                    self::DEFAULT_IIN => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                ]
            ],
        ],

        // default issuer
        self::DEFAULT_ISSUER => [
            // MC/MAES supports on bin level.
            Network::MC   => [
                CardType::DEBIT => [
                    "222686" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "222844" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "222849" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "222852" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "222853" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "222854" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "222855" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "222856" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "222857" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "230007" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "230398" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "230512" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "230555" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "230723" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "265434" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "268262" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "269927" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "270114" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "270127" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "270154" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "270237" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "510096" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "510128" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "510135" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "510223" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "510557" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "511870" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "512033" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "512293" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "512622" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "512932" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "512944" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "512971" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "513404" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "514831" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "514833" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "515228" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "515630" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "515875" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "515892" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "516068" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "516626" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "517252" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "517271" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "517286" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "517438" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "517456" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "517550" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "517899" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "518851" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "519608" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "519980" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "520722" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "522012" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "522306" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "522346" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "522352" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "522358" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "523650" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "523950" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "524167" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "524182" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "524247" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "524317" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "524373" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "524480" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "524563" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "524652" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "525245" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "525313" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "525611" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "510372" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "511878" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "517574" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "519253" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "519254" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],

                    "519255" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "519256" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "519619" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "519620" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "519913" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "525622" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "521106" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "521107" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "521108" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "521109" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "521110" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "521111" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "521112" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "521113" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "521114" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "521115" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "521154" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "521782" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "522669" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "523116" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "524272" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "528095" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "529812" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "531091" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "534131" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "534327" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "534330" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "534332" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "534334" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "534337" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "534340" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "535930" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "538426" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "542225" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "543252" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "543606" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "544405" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "544574" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "544575" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "544585" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "544670" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "544751" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "544921" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "545080" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "545332" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "545354" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "549172" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "549263" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "557582" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "557586" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "557629" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "557631" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "557632" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "557677" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "559601" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "559602" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "559603" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "559604" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "559605" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "559606" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "525866" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "526468" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "526550" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "526701" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "526702" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "526861" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "526905" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "527086" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "527114" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "527898" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "528028" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "528677" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "528678" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "528734" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "528756" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "529243" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "529617" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "529618" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "529819" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "530917" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "531230" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "531746" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "531849" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "531858" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "532728" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "533102" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "533114" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "533121" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "534289" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "534555" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "534697" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "535322" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "535938" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "535985" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "536016" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "536017" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "536038" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "536089" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "536132" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "536194" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "536038" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "536089" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "536132" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "536194" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "536038" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "536089" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "536132" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "536194" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "536038" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "536089" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "536132" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "536194" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "536298" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "536303" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "536589" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "536610" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "536621" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "536799" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "536907" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "537652" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "538103" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "538865" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "539149" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "539158" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "539192" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "539936" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "540461" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "541538" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "542505" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "542609" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "542790" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "542873" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "543751" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "544365" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "547277" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "547359" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "547827" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "549751" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "549759" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "549793" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "549921" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "551599" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "553387" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "555915" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "555942" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "557633" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "557654" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "557656" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "558853" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "558918" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "558963" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "559153" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "559426" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "559746" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "559752" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                ]
            ],
            Network::MAES => [
                CardType::DEBIT => [
                    "222686" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "222844" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "222849" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "222852" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "222853" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "222854" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "222855" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "222856" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "222857" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "230007" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "230398" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "230512" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "230555" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "230723" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "265434" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "268262" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "269927" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "270114" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "270127" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "270154" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "270237" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "510096" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "510128" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "510135" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "510223" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "510557" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "511870" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "512033" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "512293" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "512622" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "512932" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "512944" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "512971" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "513404" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "514831" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "514833" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "515228" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "515630" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "515875" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "515892" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "516068" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "516626" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "517252" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "517271" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "517286" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "517438" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "517456" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "517550" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "517899" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "518851" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "519608" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "519980" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "520722" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "522012" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "522306" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "522346" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "522352" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "522358" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "523650" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "523950" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "524167" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "524182" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "524247" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "524317" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "524373" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "524480" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "524563" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "524652" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "525245" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "525313" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "525611" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "525622" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "525866" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "526468" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "526550" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "526701" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "526702" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "526861" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "526905" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "527086" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "527114" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "527898" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "528028" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "528677" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "528678" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "528734" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "528756" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "529243" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "529617" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "529618" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "529819" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "530917" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "531230" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "531746" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "531849" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "531858" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "532728" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "533102" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "533114" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "533121" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "534289" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "534555" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "534697" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "535322" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "535938" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "535985" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "536016" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "536017" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "536038" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "536089" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "536132" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "536194" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "536298" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "536303" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "536589" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "536610" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "536621" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "536799" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "536907" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "537652" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "538103" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "538865" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "539149" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "539158" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "539192" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "539936" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "540461" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "541538" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "542505" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "542609" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "542790" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "542873" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "543751" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "544365" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "547277" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "547359" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "547827" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "549751" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "549759" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "549793" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "549921" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "551599" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "553387" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "555915" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "555942" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "557633" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "557654" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "557656" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "558853" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "558918" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "558963" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "559426" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "559746" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                    "559752" => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                ]
            ],
        ],

        Issuer::YESB => [
            Network::MC   => [
                CardType::DEBIT  => [
                    self::DEFAULT_IIN => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ]
                ],
                CardType::CREDIT => [
                    self::DEFAULT_IIN => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                ]
            ],
            Network::MAES => [
                CardType::DEBIT => [
                    self::DEFAULT_IIN => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ]
                ]
            ],
            Network::VISA => [
                CardType::CREDIT => [
                    self::DEFAULT_IIN => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                ]
            ],
        ],

        Issuer::RATN => [
            Network::MC => [
                CardType::CREDIT => [
                    self::DEFAULT_IIN => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                ]
            ],
            Network::VISA => [
                CardType::CREDIT => [
                    self::DEFAULT_IIN => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                ]
            ],
        ],

        Issuer::HSBC => [
            Network::MC => [
                CardType::CREDIT => [
                    self::DEFAULT_IIN => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                ]
            ],
        ],

        Issuer::JAKA => [
            Network::MC => [
                CardType::CREDIT => [
                    self::DEFAULT_IIN => [
                        Mode::CHANNEL => Mode::M2P,
                        Mode::MODES   => [Mode::CT]
                    ],
                ]
            ],
        ],
    ];

    public static function getNetworkRailsSupportedModesMap()
    {
        return self::$m2pSupportedModesMap;
    }
}
