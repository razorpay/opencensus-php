<?php

namespace RZP\Models\P2p\Preferences;

class Constants
{
    private static array $popularBanksList = [
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

    public static function getPopularBanksList(): array
    {
        return self::$popularBanksList;
    }
}
