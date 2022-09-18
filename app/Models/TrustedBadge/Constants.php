<?php

namespace RZP\Models\TrustedBadge;

use RZP\Models\Terminal\Category as Category;

class Constants
{
    public const EXCLUDED_CATEGORY_LIST = [Category::LENDING, Category::GOVERNMENT, Category::GOVT_EDUCATION];
    public const EXCLUDED_MCC_CODE_LIST = ['0', '6012', '9399', '5541', '8220', '4900', '8299', '5817', '8211', '5094', '7998'];

    public const STANDARD_DEVIATION = 'standard_deviation';
    public const MEAN = 'mean';

    public const CATEGORY_WISE_STATISTICAL_DATA = [
        Category::COMMODITIES => [
            self::STANDARD_DEVIATION => 0.03811,
            self::MEAN => 0.02865,
        ],
        Category::CORPORATE => [
            self::STANDARD_DEVIATION => 0.02856,
            self::MEAN => 0.01074,
        ],
        Category::ECOMMERCE => [
            self::STANDARD_DEVIATION => 0.05264,
            self::MEAN => 0.02703,
        ],
        Category::FINANCIAL_SERVICES => [
            self::STANDARD_DEVIATION => 0.04918,
            self::MEAN => 0.01623,
        ],
        Category::FOOD_AND_BEVERAGE => [
            self::STANDARD_DEVIATION => 0.01624,
            self::MEAN => 0.01290,
        ],
        Category::FOREX => [
            self::STANDARD_DEVIATION => 0.18375,
            self::MEAN => 0.06455,
        ],
        Category::GAMING => [
            self::STANDARD_DEVIATION => 0.00182,
            self::MEAN => 0.00094,
        ],
        Category::GOVERNMENT => [
            self::STANDARD_DEVIATION => 0.05154,
            self::MEAN => 0.01204,
        ],
        Category::GOVT_EDUCATION => [
            self::STANDARD_DEVIATION => 0.00334,
            self::MEAN => 0.00186,
        ],
        Category::GROCERY => [
            self::STANDARD_DEVIATION => 0.01273,
            self::MEAN => 0.00785,
        ],
        Category::HEALTHCARE => [
            self::STANDARD_DEVIATION => 0.05311,
            self::MEAN => 0.02594,
        ],
        Category::HOSPITALITY => [
            self::STANDARD_DEVIATION => 0.02419,
            self::MEAN => 0.01363,
        ],
        Category::HOUSING => [
            self::STANDARD_DEVIATION => 0.05711,
            self::MEAN => 0.01548,
        ],
        Category::INSURANCE => [
            self::STANDARD_DEVIATION => 0.02365,
            self::MEAN => 0.01400,
        ],
        Category::IT_AND_SOFTWARE => [
            self::STANDARD_DEVIATION => 0.03627,
            self::MEAN => 0.01655,
        ],
        Category::LOGISTICS => [
            self::STANDARD_DEVIATION => 0.02213,
            self::MEAN => 0.01344,
        ],
        Category::MEDIA_AND_ENTERTAINMENT => [
            self::STANDARD_DEVIATION => 0.02250,
            self::MEAN => 0.00982,
        ],
        Category::MUTUAL_FUNDS => [
            self::STANDARD_DEVIATION => 0.03261,
            self::MEAN => 0.01105,
        ],
        Category::NOT_FOR_PROFIT => [
            self::STANDARD_DEVIATION => 0.00668,
            self::MEAN => 0.00316,
        ],
        Category::OTHERS => [
            self::STANDARD_DEVIATION => 0.05686,
            self::MEAN => 0.02662,
        ],
        Category::PHARMA => [
            self::STANDARD_DEVIATION => 0.03333,
            self::MEAN => 0.02390,
        ],
        Category::PVT_EDUCATION => [
            self::STANDARD_DEVIATION => 0.01999,
            self::MEAN => 0.00577,
        ],
        Category::REAL_ESTATE => [
            self::STANDARD_DEVIATION => 0.01751,
            self::MEAN => 0.00698,
        ],
        Category::RECHARGES => [
            self::STANDARD_DEVIATION => 0.02982,
            self::MEAN => 0.00635,
        ],
        Category::SECURITIES => [
            self::STANDARD_DEVIATION => 0.00351,
            self::MEAN => 0.00124,
        ],
        Category::SERVICES => [
            self::STANDARD_DEVIATION => 0.03633,
            self::MEAN => 0.01455,
        ],
        Category::SOCIAL => [
            self::STANDARD_DEVIATION => 0.02154,
            self::MEAN => 0.01308,
        ],
        Category::TRANSPORT => [
            self::STANDARD_DEVIATION => 0.12507,
            self::MEAN => 0.04835,
        ],
        Category::TRAVEL_AGENCY => [
            self::STANDARD_DEVIATION => 0.05725,
            self::MEAN => 0.02843,
        ],
        Category::UTILITIES => [
            self::STANDARD_DEVIATION => 0.01503,
            self::MEAN => 0.00584,
        ],
    ];
}
