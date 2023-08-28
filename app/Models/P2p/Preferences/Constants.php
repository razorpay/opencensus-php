<?php

namespace RZP\Models\P2p\Preferences;

class Constants
{
    const MERCHANT     = 'merchant';
    const FEATURES     = 'features';
    const MERCHANT_ID  = 'merchant_id';
    const DISPLAY_NAME = 'display_name';
    private static array $popularBanksListInProd = [
        [
            "priority"     => "0",
            "iin"          => "607153",
            "display_name" => "AXIS"
        ],
        [
            "priority"     => "1",
            "iin"          => "607152",
            "display_name" => "HDFC"
        ],
        [
            "priority"     => "2",
            "iin"          => "508534",
            "display_name" => "ICICI"
        ],
        [
            "priority"     => "3",
            "iin"          => "508548",
            "display_name" => "SBI"
        ],
        [
            "priority"     => "4",
            "iin"          => "607420",
            "display_name" => "Kotak"
        ],
        [
            "priority"     => "5",
            "iin"          => "508568",
            "display_name" => "PNB"
        ],
        [
            "priority"     => "6",
            "iin"          => "606985",
            "display_name" => "BOB"
        ],
        [
            "priority"     => "7",
            "iin"          => "607189",
            "display_name" => "INDUSIND"
        ]
    ];

    private static array $popularBanksListInUAT = [
        [
            "priority"     => "0",
            "iin"          => "607153",
            "display_name" => "AXIS"
        ],
        [
            "priority"     => "1",
            "iin"          => "901345",
            "display_name" => "HDFC"
        ],
        [
            "priority"     => "2",
            "iin"          => "508534",
            "display_name" => "ICICI"
        ],
        [
            "priority"     => "3",
            "iin"          => "508548",
            "display_name" => "SBI"
        ],
        [
            "priority"     => "4",
            "iin"          => "190070",
            "display_name" => "Kotak"
        ],
        [
            "priority"     => "5",
            "iin"          => "189025",
            "display_name" => "PNB"
        ],
        [
            "priority"     => "6",
            "iin"          => "612353",
            "display_name" => "BOB"
        ],
        [
            "priority"     => "7",
            "iin"          => "612355",
            "display_name" => "INDUSIND"
        ]
    ];

    public static function getStaticPopularBanksList(): array
    {
        if((app()->isEnvironmentProduction() === true))
        {
            return self::$popularBanksListInProd;
        }
        else
        {
            return self::$popularBanksListInUAT;
        }
    }
}
