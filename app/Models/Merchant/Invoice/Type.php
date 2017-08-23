<?php

namespace RZP\Models\Merchant\Invoice;

class Type
{
    // Comission on Card Payments <= INR 2,000'
    const CARD_LTE_2K   = 'card_lte_2k';

    // Comission on Card Payments > INR 2,000
    const CARD_GT_2K    = 'card_gt_2k';

    // Comission on All Methods Except Cards
    const NON_CARD      = 'non_card';

    // Any adjustments made for commision
    const ADJUSTMENT    = 'adjustment';

    const DEFAULT_GST_SAC_CODE = '';

    protected static $typeToSacMap = [
        self::CARD_LTE_2K   => 997158,
        self::CARD_GT_2K    => 997158,
        self::NON_CARD      => 997158,
        self::ADJUSTMENT    => 997158,
    ];

    const DEFAULT_DESCRIPTION = 'Commision';

    const CARD_LTE_2K_DESCRIPTION   = 'Comission on Card Payments <= INR 2,000';
    const CARD_GT_2K_DESCRIPTION    = 'Comission on Card Payments > INR 2,000';
    const NON_CARD_DESCRIPTION      = 'Comission on All Methods Except Cards';

    protected static $typeToDescriptionMap = [
        self::CARD_LTE_2K   => self::CARD_LTE_2K_DESCRIPTION,
        self::CARD_GT_2K    => self::CARD_GT_2K_DESCRIPTION,
        self::NON_CARD      => self::NON_CARD_DESCRIPTION,
        self::ADJUSTMENT    => 'Adjustment',
    ];

    public static function getAllTypes(): array
    {
        return [
            self::CARD_LTE_2K,
            self::CARD_GT_2K,
            self::NON_CARD,
            self::ADJUSTMENT,
        ];
    }

    public static function isValid($type): bool
    {
        return (defined(__CLASS__ . '::' . strtoupper($type)));
    }

    public static function getGstSacCodeForType(string $type): string
    {
        return self::$typeToSacMap[$type] ?? self::DEFAULT_GST_SAC_CODE;
    }

    public static function getDescriptionFromType(string $type): string
    {
        return self::$typeToDescriptionMap[$type] ?? self::DEFAULT_DESCRIPTION;
    }
}