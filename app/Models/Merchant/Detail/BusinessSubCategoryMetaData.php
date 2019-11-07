<?php

namespace RZP\Models\Merchant\Detail;

use RZP\Error\ErrorCode;
use RZP\Models\Terminal\Category;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Merchant\Detail\BusinessSubcategory as Sub;

class BusinessSubCategoryMetaData
{
    const DESCRIPTION                       = 'description';
    const NORMAL_AUTH_FIELDS                = [self::DESCRIPTION, Entity::ACTIVATION_FLOW];
    const EMI_ACTIVATION                    = 'emi_activation';
    const INTERNATIONAL_ACTIVATION          = 'international_activation';
    const NON_REGISTERED_MAX_PAYABLE_AMOUNT = 'non_registered_max_payable_amount';

    const SUB_CATEGORY_METADATA = [
        Sub::ACCOMMODATION                 => [
            Merchant::CATEGORY                      => 7011,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::ACCOMMODATION],
            Merchant::CATEGORY2                     => Category::HOSPITALITY,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1062300,
        ],
        Sub::ACCOUNTING                    => [
            Merchant::CATEGORY                      => 8931,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::ACCOUNTING],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::BLACKLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 3500000,
        ],
        Sub::AD_AND_MARKETING              => [
            Merchant::CATEGORY                      => 7311,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::AD_AND_MARKETING],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 5000000,
        ],
        Sub::AGRICULTURE                   => [
            Merchant::CATEGORY                      => 5193,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::AGRICULTURE],
            Merchant::CATEGORY2                     => Category::ECOMMERCE,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1480500,
        ],
        Sub::ALCOHOL                       => [
            Merchant::CATEGORY                      => 5813,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::ALCOHOL],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::BLACKLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::BLACKLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::BLACKLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1000000,
        ],
        Sub::ARTS_AND_COLLECTIBLES         => [
            Merchant::CATEGORY                      => 5971,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::ARTS_AND_COLLECTIBLES],
            Merchant::CATEGORY2                     => Category::ECOMMERCE,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::BLACKLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1480500,
        ],
        Sub::AVIATION                      => [
            Merchant::CATEGORY                      => 4511,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::AVIATION],
            Merchant::CATEGORY2                     => Category::TRAVEL_AGENCY,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::GREYLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1062300,
        ],
        Sub::BABY_PRODUCTS                 => [
            Merchant::CATEGORY                      => 5945,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::BABY_PRODUCTS],
            Merchant::CATEGORY2                     => Category::ECOMMERCE,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1480500,
        ],
        Sub::BETTING                       => [
            Merchant::CATEGORY                      => 7801,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::BETTING],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::BLACKLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::BLACKLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::BLACKLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 0,
        ],
        Sub::BILL_AND_RECHARGE_AGGREGATORS => [
            Merchant::CATEGORY                      => 4814,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::BILL_AND_RECHARGE_AGGREGATORS],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::GREYLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1000000,
        ],
        Sub::BOOKS                         => [
            Merchant::CATEGORY                      => 5942,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::BOOKS],
            Merchant::CATEGORY2                     => Category::ECOMMERCE,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1480500,
        ],
        Sub::BROADBAND                     => [
            Merchant::CATEGORY                      => 4899,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::BROADBAND],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1000000,
        ],
        Sub::BUS                           => [
            Merchant::CATEGORY                      => 4131,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::BUS],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1000000,
        ],
        Sub::CABLE                         => [
            Merchant::CATEGORY                      => 4899,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::CABLE],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1000000,
        ],
        Sub::CAB_HAILING                   => [
            Merchant::CATEGORY                      => 4121,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::CAB_HAILING],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1000000,
        ],
        Sub::CATERING                      => [
            Merchant::CATEGORY                      => 5811,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::CATERING],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1000000,
        ],
        Sub::CENTRAL                       => [
            Merchant::CATEGORY                      => 9399,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::CENTRAL],
            Merchant::CATEGORY2                     => Category::GOVERNMENT,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 0,
        ],
        Sub::CHARITY                       => [
            Merchant::CATEGORY                      => 8398,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::CHARITY],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::GREYLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::BLACKLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 0,
        ],
        Sub::CLINIC                        => [
            Merchant::CATEGORY                      => 8062,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::CLINIC],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1549000,
        ],
        Sub::COACHING                      => [
            Merchant::CATEGORY                      => 8299,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::COACHING],
            Merchant::CATEGORY2                     => Category::PVT_EDUCATION,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 6000000,
        ],
        Sub::COLLEGE                       => [
            Merchant::CATEGORY                      => 8220,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::COLLEGE],
            Merchant::CATEGORY2                     => Category::PVT_EDUCATION,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 6000000,
        ],
        Sub::COMMODITIES                   => [
            Merchant::CATEGORY                      => 6211,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::COMMODITIES],
            Merchant::CATEGORY2                     => Category::SECURITIES,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::BLACKLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::BLACKLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 3500000,
        ],
        Sub::CONSULTING                    => [
            Merchant::CATEGORY                      => 7392,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::CONSULTING],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 5000000,
        ],
        Sub::CONSULTING_AND_OUTSOURCING    => [
            Merchant::CATEGORY                      => 7392,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::CONSULTING_AND_OUTSOURCING],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::GREYLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 2500000,
        ],
        Sub::CONTENT_AND_PUBLISHING        => [
            Merchant::CATEGORY                      => 2741,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::CONTENT_AND_PUBLISHING],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1000000,
        ],
        Sub::COOPERATIVES                  => [
            Merchant::CATEGORY                      => 6012,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::COOPERATIVES],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::BLACKLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 3500000,
        ],
        Sub::COUPONS                       => [
            Merchant::CATEGORY                      => 7311,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::COUPONS],
            Merchant::CATEGORY2                     => Category::ECOMMERCE,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1480500,
        ],
        Sub::COURIER                       => [
            Merchant::CATEGORY                      => 4215,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::COURIER],
            Merchant::CATEGORY2                     => Category::LOGISTICS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 4130000,
        ],
        Sub::COWORKING                     => [
            Merchant::CATEGORY                      => 6513,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::COWORKING],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1500000,
        ],
        Sub::CROWDFUNDING                  => [
            Merchant::CATEGORY                      => 6050,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::CROWDFUNDING],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::BLACKLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 3500000,
        ],
        Sub::CRYPTOCURRENCY                => [
            Merchant::CATEGORY                      => 6051,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::CRYPTOCURRENCY],
            Merchant::CATEGORY2                     => Category::CRYPTOCURRENCY,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::BLACKLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::BLACKLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::BLACKLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 3500000,
        ],
        Sub::CRYPTO_MACHINERY              => [
            Merchant::CATEGORY                      => 5999,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::CRYPTO_MACHINERY],
            Merchant::CATEGORY2                     => Category::ECOMMERCE,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::BLACKLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::BLACKLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::BLACKLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1480500,
        ],
        Sub::DAY_CARE                      => [
            Merchant::CATEGORY                      => 8351,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::DAY_CARE],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 6000000,
        ],
        Sub::DEVELOPER                     => [
            Merchant::CATEGORY                      => 6513,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::DEVELOPER],
            Merchant::CATEGORY2                     => Category::HOUSING,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::GREYLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1500000,
        ],
        Sub::DIETICIAN                     => [
            Merchant::CATEGORY                      => 7298,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::DIETICIAN],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1549000,
        ],
        Sub::DISTANCE_LEARNING             => [
            Merchant::CATEGORY                      => 8299,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::DISTANCE_LEARNING],
            Merchant::CATEGORY2                     => Category::PVT_EDUCATION,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 6000000,
        ],
        Sub::DISTRIBUTION_MANAGEMENT       => [
            Merchant::CATEGORY                      => 4214,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::DISTRIBUTION_MANAGEMENT],
            Merchant::CATEGORY2                     => Category::LOGISTICS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 4130000,
        ],
        Sub::DROP_SHIPPING                 => [
            Merchant::CATEGORY                      => 5399,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::DROP_SHIPPING],
            Merchant::CATEGORY2                     => Category::ECOMMERCE,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::GREYLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1480500,
        ],
        Sub::DTH                           => [
            Merchant::CATEGORY                      => 4899,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::DTH],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1000000,
        ],
        Sub::ECOMMERCE_MARKETPLACE         => [
            Merchant::CATEGORY                      => 5399,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::ECOMMERCE_MARKETPLACE],
            Merchant::CATEGORY2                     => Category::ECOMMERCE,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1480500,
        ],
        Sub::EDUCATIONAL                   => [
            Merchant::CATEGORY                      => 8398,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::EDUCATIONAL],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::GREYLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 0,
        ],
        Sub::ELEARNING                     => [
            Merchant::CATEGORY                      => 8299,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::ELEARNING],
            Merchant::CATEGORY2                     => Category::PVT_EDUCATION,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 6000000,
        ],
        Sub::ELECTRICITY                   => [
            Merchant::CATEGORY                      => 4900,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::ELECTRICITY],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1000000,
        ],
        Sub::ELECTRONICS_AND_FURNITURE     => [
            Merchant::CATEGORY                      => 5732,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::ELECTRONICS_AND_FURNITURE],
            Merchant::CATEGORY2                     => Category::ECOMMERCE,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1480500,
        ],
        Sub::END_TO_END_LOGISTICS          => [
            Merchant::CATEGORY                      => 4214,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::END_TO_END_LOGISTICS],
            Merchant::CATEGORY2                     => Category::LOGISTICS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 4130000,
        ],
        Sub::ESPORTS                       => [
            Merchant::CATEGORY                      => 5816,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::ESPORTS],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::BLACKLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 0,
        ],
        Sub::EVENT_PLANNING                => [
            Merchant::CATEGORY                      => 8999,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::EVENT_PLANNING],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 5000000,
        ],
        Sub::FACILITY_MANAGEMENT           => [
            Merchant::CATEGORY                      => 7349,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::FACILITY_MANAGEMENT],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1500000,
        ],
        Sub::FANTASY_SPORTS                => [
            Merchant::CATEGORY                      => 5816,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::FANTASY_SPORTS],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::BLACKLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 0,
        ],
        Sub::FASHION_AND_LIFESTYLE         => [
            Merchant::CATEGORY                      => 5691,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::FASHION_AND_LIFESTYLE],
            Merchant::CATEGORY2                     => Category::ECOMMERCE,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1480500,
        ],
        Sub::FINANCIAL_ADVISOR             => [
            Merchant::CATEGORY                      => 8931,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::FINANCIAL_ADVISOR],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::BLACKLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 3500000,
        ],
        Sub::FITNESS                       => [
            Merchant::CATEGORY                      => 7298,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::FITNESS],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1549000,
        ],
        Sub::FOOD_COURT                    => [
            Merchant::CATEGORY                      => 5814,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::FOOD_COURT],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1000000,
        ],
        Sub::FOREX                         => [
            Merchant::CATEGORY                      => 6010,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::FOREX],
            Merchant::CATEGORY2                     => Category::FOREX,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::BLACKLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 3500000,
        ],
        Sub::FREIGHT                       => [
            Merchant::CATEGORY                      => 4214,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::FREIGHT],
            Merchant::CATEGORY2                     => Category::LOGISTICS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 4130000,
        ],
        Sub::GAME_DEVELOPER                => [
            Merchant::CATEGORY                      => 5816,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::GAME_DEVELOPER],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::BLACKLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 0,
        ],
        Sub::GAMING_MARKETPLACE            => [
            Merchant::CATEGORY                      => 5816,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::GAMING_MARKETPLACE],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::BLACKLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 0,
        ],
        Sub::GAS                           => [
            Merchant::CATEGORY                      => 4900,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::GAS],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1000000,
        ],
        Sub::GET_RICH_SCHEMES              => [
            Merchant::CATEGORY                      => 7361,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::GET_RICH_SCHEMES],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::BLACKLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::BLACKLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::BLACKLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 0,
        ],
        Sub::GIFTING                       => [
            Merchant::CATEGORY                      => 5193,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::GIFTING],
            Merchant::CATEGORY2                     => Category::ECOMMERCE,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1480500,
        ],
        Sub::GROCERY                       => [
            Merchant::CATEGORY                      => 5411,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::GROCERY],
            Merchant::CATEGORY2                     => Category::GROCERY,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::GREYLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1480500,
        ],
        Sub::HEALTH_COACHING               => [
            Merchant::CATEGORY                      => 7298,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::HEALTH_COACHING],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1549000,
        ],
        Sub::HEALTH_PRODUCTS               => [
            Merchant::CATEGORY                      => 5499,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::HEALTH_PRODUCTS],
            Merchant::CATEGORY2                     => Category::ECOMMERCE,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1549000,
        ],
        Sub::HEALTHCARE_MARKETPLACE        => [
            Merchant::CATEGORY                      => 5399,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::HEALTHCARE_MARKETPLACE],
            Merchant::CATEGORY2                     => Category::ECOMMERCE,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1549000,
        ],
        Sub::HOSPITAL                      => [
            Merchant::CATEGORY                      => 8062,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::HOSPITAL],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1549000,
        ],
        Sub::IAAS                          => [
            Merchant::CATEGORY                      => 5817,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::IAAS],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::GREYLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 2500000,
        ],
        Sub::INSURANCE                     => [
            Merchant::CATEGORY                      => 6300,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::INSURANCE],
            Merchant::CATEGORY2                     => Category::INSURANCE,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::GREYLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 3500000,
        ],
        Sub::INTERIOR_DESIGN_AND_ARCHITECT => [
            Merchant::CATEGORY                      => 8911,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::INTERIOR_DESIGN_AND_ARCHITECT],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 5000000,
        ],
        Sub::INTERNET_PROVIDER             => [
            Merchant::CATEGORY                      => 4816,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::INTERNET_PROVIDER],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1000000,
        ],
        Sub::LAB                           => [
            Merchant::CATEGORY                      => 8071,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::LAB],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1549000,
        ],
        Sub::LEGAL                         => [
            Merchant::CATEGORY                      => 8111,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::LEGAL],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 5000000,
        ],
        Sub::LENDING                       => [
            Merchant::CATEGORY                      => 6012,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::LENDING],
            Merchant::CATEGORY2                     => Category::LENDING,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::BLACKLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::BLACKLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 3500000,
        ],
        Sub::MATCHMAKING                   => [
            Merchant::CATEGORY                      => 7273,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::MATCHMAKING],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::GREYLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::BLACKLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1000000,
        ],
        Sub::MESSAGING                     => [
            Merchant::CATEGORY                      => 4821,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::MESSAGING],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::GREYLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1000000,
        ],
        Sub::MOVERS_AND_PACKERS            => [
            Merchant::CATEGORY                      => 4214,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::MOVERS_AND_PACKERS],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 5000000,
        ],
        Sub::MULTI_LEVEL_MARKETING         => [
            Merchant::CATEGORY                      => 5964,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::MULTI_LEVEL_MARKETING],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::BLACKLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::BLACKLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::BLACKLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 5000000,
        ],
        Sub::MULTIPLEX                     => [
            Merchant::CATEGORY                      => 7832,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::MULTIPLEX],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1000000,
        ],
        Sub::MUSIC_STREAMING               => [
            Merchant::CATEGORY                      => 5815,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::MUSIC_STREAMING],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::GREYLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1000000,
        ],
        Sub::MUTUAL_FUND                   => [
            Merchant::CATEGORY                      => 6211,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::MUTUAL_FUND],
            Merchant::CATEGORY2                     => Category::MUTUAL_FUNDS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::BLACKLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::BLACKLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 3500000,
        ],
        Sub::NBFC                          => [
            Merchant::CATEGORY                      => 6012,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::NBFC],
            Merchant::CATEGORY2                     => Category::LENDING,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::BLACKLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 3500000,
        ],
        Sub::NEIGHBOURHOOD_NETWORK         => [
            Merchant::CATEGORY                      => 8699,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::NEIGHBOURHOOD_NETWORK],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::GREYLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1000000,
        ],
        Sub::NEWS                          => [
            Merchant::CATEGORY                      => 5994,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::NEWS],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1000000,
        ],
        Sub::OFFICE_SUPPLIES               => [
            Merchant::CATEGORY                      => 5111,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::OFFICE_SUPPLIES],
            Merchant::CATEGORY2                     => Category::ECOMMERCE,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1480500,
        ],
        Sub::ONLINE_CASINO                 => [
            Merchant::CATEGORY                      => 7801,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::ONLINE_CASINO],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::BLACKLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::BLACKLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::BLACKLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 0,
        ],
        Sub::ONLINE_FOOD_ORDERING          => [
            Merchant::CATEGORY                      => 5811,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::ONLINE_FOOD_ORDERING],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1000000,
        ],
        Sub::OTA                           => [
            Merchant::CATEGORY                      => 4722,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::OTA],
            Merchant::CATEGORY2                     => Category::TRAVEL_AGENCY,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::GREYLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1062300,
        ],
        Sub::PAAS                          => [
            Merchant::CATEGORY                      => 5817,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::PAAS],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::GREYLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 2500000,
        ],
        Sub::PENSION_FUND                  => [
            Merchant::CATEGORY                      => 6012,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::PENSION_FUND],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::BLACKLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 3500000,
        ],
        Sub::PERSONAL                      => [
            Merchant::CATEGORY                      => 8398,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::PERSONAL],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::GREYLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 0,
        ],
        Sub::PROFESSIONAL_COURSES          => [
            Merchant::CATEGORY                      => 8299,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::PROFESSIONAL_COURSES],
            Merchant::CATEGORY2                     => Category::PVT_EDUCATION,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 6000000,
        ],
        Sub::PET_PRODUCTS                  => [
            Merchant::CATEGORY                      => 5995,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::PET_PRODUCTS],
            Merchant::CATEGORY2                     => Category::ECOMMERCE,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1480500,
        ],
        Sub::PHARMACY                      => [
            Merchant::CATEGORY                      => 5912,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::PHARMACY],
            Merchant::CATEGORY2                     => Category::PHARMA,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::BLACKLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::BLACKLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1549000,
        ],
        Sub::PROFESSIONAL_NETWORK          => [
            Merchant::CATEGORY                      => 8699,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::PROFESSIONAL_NETWORK],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::GREYLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1000000,
        ],
        Sub::REALESTATE_CLASSIFIEDS        => [
            Merchant::CATEGORY                      => 6513,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::REALESTATE_CLASSIFIEDS],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1500000,
        ],
        Sub::RELIGIOUS                     => [
            Merchant::CATEGORY                      => 8661,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::RELIGIOUS],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::BLACKLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 0,
        ],
        Sub::RELIGIOUS_PRODUCTS            => [
            Merchant::CATEGORY                      => 5973,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::RELIGIOUS_PRODUCTS],
            Merchant::CATEGORY2                     => Category::ECOMMERCE,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1480500,
        ],
        Sub::RENTAL                        => [
            Merchant::CATEGORY                      => 7394,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::RENTAL],
            Merchant::CATEGORY2                     => Category::ECOMMERCE,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1480500,
        ],
        Sub::REPAIR_AND_CLEANING           => [
            Merchant::CATEGORY                      => 7531,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::REPAIR_AND_CLEANING],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 5000000,
        ],
        Sub::RESTAURANT                    => [
            Merchant::CATEGORY                      => 5812,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::RESTAURANT],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1000000,
        ],
        Sub::RESTAURANT_SEARCH_AND_BOOKING => [
            Merchant::CATEGORY                      => 7299,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::RESTAURANT_SEARCH_AND_BOOKING],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1000000,
        ],
        Sub::RWA                           => [
            Merchant::CATEGORY                      => 7349,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::RWA],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1500000,
        ],
        Sub::SAAS                          => [
            Merchant::CATEGORY                      => 5817,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::SAAS],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::GREYLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 2500000,
        ],
        Sub::SCHOOLS                       => [
            Merchant::CATEGORY                      => 8211,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::SCHOOLS],
            Merchant::CATEGORY2                     => Category::PVT_EDUCATION,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 6000000,
        ],
        Sub::SECURITIES                    => [
            Merchant::CATEGORY                      => 6211,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::SECURITIES],
            Merchant::CATEGORY2                     => Category::SECURITIES,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::BLACKLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::BLACKLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 3500000,
        ],
        Sub::SERVICE_CENTRE                => [
            Merchant::CATEGORY                      => 5511,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::SERVICE_CENTRE],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 5000000,
        ],
        Sub::SERVICES_CLASSIFIEDS          => [
            Merchant::CATEGORY                      => 7311,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::SERVICES_CLASSIFIEDS],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 5000000,
        ],
        Sub::SEXUAL_WELLNESS_PRODUCTS      => [
            Merchant::CATEGORY                      => 5999,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::SEXUAL_WELLNESS_PRODUCTS],
            Merchant::CATEGORY2                     => Category::ECOMMERCE,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::GREYLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1480500,
        ],
        Sub::SOCIAL_NETWORK                => [
            Merchant::CATEGORY                      => 8641,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::SOCIAL_NETWORK],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::GREYLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1000000,
        ],
        Sub::SPACE_RENTAL                  => [
            Merchant::CATEGORY                      => 6513,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::SPACE_RENTAL],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1500000,
        ],
        Sub::SPORTS_PRODUCTS               => [
            Merchant::CATEGORY                      => 5941,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::SPORTS_PRODUCTS],
            Merchant::CATEGORY2                     => Category::ECOMMERCE,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1480500,
        ],
        Sub::STATE                         => [
            Merchant::CATEGORY                      => 9399,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::STATE],
            Merchant::CATEGORY2                     => Category::GOVERNMENT,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 0,
        ],
        Sub::TECHNICAL_SUPPORT             => [
            Merchant::CATEGORY                      => 7379,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::TECHNICAL_SUPPORT],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::BLACKLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::BLACKLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::BLACKLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 2500000,
        ],
        Sub::TELECOM                       => [
            Merchant::CATEGORY                      => 4814,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::TELECOM],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1000000,
        ],
        Sub::TICKETING                     => [
            Merchant::CATEGORY                      => 7832,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::TICKETING],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1000000,
        ],
        Sub::TOBACCO                       => [
            Merchant::CATEGORY                      => 5993,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::TOBACCO],
            Merchant::CATEGORY2                     => Category::ECOMMERCE,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::BLACKLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::BLACKLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::BLACKLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1480500,
        ],
        Sub::TRADING                       => [
            Merchant::CATEGORY                      => 6211,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::TRADING],
            Merchant::CATEGORY2                     => Category::SECURITIES,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::BLACKLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::BLACKLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 3500000,
        ],
        Sub::TRAIN_AND_METRO               => [
            Merchant::CATEGORY                      => 4112,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::TRAIN_AND_METRO],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1000000,
        ],
        Sub::TRAVEL_AGENCY                 => [
            Merchant::CATEGORY                      => 4722,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::TRAVEL_AGENCY],
            Merchant::CATEGORY2                     => Category::TRAVEL_AGENCY,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::GREYLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1062300,
        ],
        Sub::UNIVERSITY                    => [
            Merchant::CATEGORY                      => 8220,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::UNIVERSITY],
            Merchant::CATEGORY2                     => Category::PVT_EDUCATION,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 6000000,
        ],
        Sub::VIDEO_ON_DEMAND               => [
            Merchant::CATEGORY                      => 5815,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::VIDEO_ON_DEMAND],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::GREYLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1000000,
        ],
        Sub::WATER                         => [
            Merchant::CATEGORY                      => 4900,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::WATER],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1000000,
        ],
        Sub::WAREHOUSING                   => [
            Merchant::CATEGORY                      => 4225,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::WAREHOUSING],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::WHITELIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 4130000,
        ],
        Sub::WEAPONS_AND_AMMUNITIONS       => [
            Merchant::CATEGORY                      => 5999,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::WEAPONS_AND_AMMUNITIONS],
            Merchant::CATEGORY2                     => Category::ECOMMERCE,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::BLACKLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::BLACKLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::BLACKLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1480500,
        ],
        Sub::WEB_DEVELOPMENT               => [
            Merchant::CATEGORY                      => 7372,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::WEB_DEVELOPMENT],
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::EMI_ACTIVATION                    => ActivationFlow::GREYLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 2500000,
        ],
        Sub::WHOLESALE                     => [
            Merchant::CATEGORY                      => 5300,
            self::DESCRIPTION                       => Sub::DESCRIPTIONS[Sub::WHOLESALE],
            Merchant::CATEGORY2                     => Category::ECOMMERCE,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::WHITELIST,
            self::EMI_ACTIVATION                    => ActivationFlow::WHITELIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::GREYLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1480500,
        ],
    ];

    /**
     * returns metadata for others category
     * others business category does not have any subcategory associated with it
     *
     * @return array
     */
    public static function getMetaDataForOthersCategory(): array
    {
        return [
            Merchant::CATEGORY                      => 5399,
            Merchant::CATEGORY2                     => Category::OTHERS,
            Entity::ACTIVATION_FLOW                 => ActivationFlow::GREYLIST,
            self::INTERNATIONAL_ACTIVATION          => ActivationFlow::BLACKLIST,
            self::NON_REGISTERED_MAX_PAYABLE_AMOUNT => 1000000,
        ];
    }

    /**
     * returns metadata for given category , subcategory
     * throws BadRequestException if metadata is not defined for subcategory
     *
     * @param string      $category
     * @param null|string $subcategory
     *
     * @return array
     * @throws \RZP\Exception\BadRequestException
     */
    public static function getSubCategoryMetaData(string $category, string $subcategory = null): array
    {
        if ($category === BusinessCategory::OTHERS)
        {
            return self::getMetaDataForOthersCategory();
        }

        if (isset(self::SUB_CATEGORY_METADATA[$subcategory]) === true)
        {
            return self::SUB_CATEGORY_METADATA[$subcategory];
        }

        throw new BadRequestException(
            ErrorCode::BAD_REQUEST_INVALID_SUBCATEGORY,
            [Entity::BUSINESS_SUBCATEGORY => $subcategory]);
    }

    /**
     * returns feature value based on category or sub-category
     *
     * @param string      $feature
     * @param string      $category
     * @param string|null $subCategory
     *
     * @param string|null $defaultValue
     *
     * @return string
     * @throws BadRequestException
     */
    public static function getFeatureValueUsingCategoryOrSubcategory(
        string $feature,
        string $category,
        string $subCategory = null,
        string $defaultValue = null): string
    {
        $subCategoryMetaData = self::getSubCategoryMetaData($category, $subCategory);

        if (isset($subCategoryMetaData[$feature]) === true)
        {
            return $subCategoryMetaData[$feature];
        }

        return $defaultValue;
    }

    /**
     * returns feature value based on category
     *
     * @param string      $feature
     * @param string      $category
     * @param string|null $defaultValue
     *
     * @return string
     * @throws BadRequestException
     */
    public static function getFeatureValueUsingMccCode(
        string $feature,
        string $category,
        string $defaultValue = null): string
    {
        $categoryData = self::fetchCategoryAndSubCategoryByMccCode((int) $category);

        $subCategoryMetaData = self::getSubCategoryMetaData(
            $categoryData[Entity::BUSINESS_CATEGORY],
            $categoryData[Entity::BUSINESS_SUBCATEGORY]
        );

        if (isset($subCategoryMetaData[$feature]) === true)
        {
            return $subCategoryMetaData[$feature];
        }

        return $defaultValue;
    }

    /**
     * Verifies that category value( mcc code) is present in predefined list of mcc
     *
     * @param $mccCode
     *
     * @return bool
     */
    public static function isMccPresentInPredefinedList($mccCode): bool
    {
        $othersCategoryMetaData = self::getMetaDataForOthersCategory();

        if ($othersCategoryMetaData[Merchant::CATEGORY] === $mccCode)
        {
            return true;
        }

        $subCategoriesMetaData = self::SUB_CATEGORY_METADATA;

        foreach ($subCategoriesMetaData as $subCategory => $subCategoryMetaData)
        {
            if ($subCategoryMetaData[Merchant::CATEGORY] === $mccCode)
            {
                return true;
            }
        }

        return false;
    }

    public static function fetchCategoryAndSubCategoryByMccCode($mccCode)
    {
        $othersCategoryMetaData = self::getMetaDataForOthersCategory();

        if ($othersCategoryMetaData[Merchant::CATEGORY] === $mccCode)
        {
            return [
                Entity::BUSINESS_CATEGORY    => BusinessCategory::OTHERS,
                Entity::BUSINESS_SUBCATEGORY => null,
            ];
        }

        $subCategoriesMetaData = self::SUB_CATEGORY_METADATA;

        foreach ($subCategoriesMetaData as $subCategory => $subCategoryMetaData)
        {
            if ($subCategoryMetaData[Merchant::CATEGORY] === $mccCode)
            {
                return [
                    Entity::BUSINESS_CATEGORY    => BusinessCategory::getCategoryFromSubCategory($subCategory),
                    Entity::BUSINESS_SUBCATEGORY => $subCategory,
                ];
            }
        }

        throw new BadRequestException(
            ErrorCode::BAD_REQUEST_MERCHANT_INVALID_MCC_CODE,
            null,
            [
                'mcc_code' => $mccCode,
            ]);
    }
}
