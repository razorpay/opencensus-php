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

    protected static $typeToDescriptionMap = [
        self::CARD_LTE_2K   => 'Comission on Card Payments <= INR 2,000',
        self::CARD_GT_2K    => 'Comission on Card Payments > INR 2,000',
        self::NON_CARD      => 'Comission on All Methods Except Cards',
        self::ADJUSTMENT    => 'Adjustment',
    ];

    public function isValid($type): bool
    {
        return (defined(__CLASS__ . '::' . strtoupper($type)));
    }

    public function getGstSacCodeForType(string $type): string
    {
        return self::$typeToSacMap[$type] ?? self::DEFAULT_GST_SAC_CODE;
    }

    public function getDescriptionFromType(string $type): string
    {
        return self::$typeToDescriptionMap[$type] ?? self::DEFAULT_DESCRIPTION;
    }
}