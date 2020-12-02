<?php

namespace RZP\Models\Merchant\AutoKyc;

use RZP\lib\ConditionParser\Operator;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\Detail\POIStatus;
use RZP\Models\Merchant\Detail\BusinessType;

class Constants
{
    const AUTO_KYC_VERIFICATION_CONDITIONS = [
        BusinessType::NOT_YET_REGISTERED => [
            Operator::AND   =>  [
                Entity::POI_VERIFICATION_STATUS          => [POIStatus::VERIFIED],
                Entity::POA_VERIFICATION_STATUS          => [POIStatus::VERIFIED],
                Entity::BANK_DETAILS_VERIFICATION_STATUS => [POIStatus::VERIFIED],
            ]
        ],

        BusinessType::INDIVIDUAL => [
            Operator::AND   =>  [
                Entity::POI_VERIFICATION_STATUS          => [POIStatus::VERIFIED],
                Entity::POA_VERIFICATION_STATUS          => [POIStatus::VERIFIED],
                Entity::BANK_DETAILS_VERIFICATION_STATUS => [POIStatus::VERIFIED],
            ]
        ],

        BusinessType::PROPRIETORSHIP => [
            Operator::AND   => [
                Entity::POI_VERIFICATION_STATUS          => [POIStatus::VERIFIED],
                Entity::POA_VERIFICATION_STATUS          => [POIStatus::VERIFIED],
                Entity::BANK_DETAILS_VERIFICATION_STATUS => [POIStatus::VERIFIED],
                Operator::OR  => [
                    Entity::GSTIN_VERIFICATION_STATUS               => [POIStatus::VERIFIED],
                    Entity::SHOP_ESTABLISHMENT_VERIFICATION_STATUS  => [POIStatus::VERIFIED],
                ]
            ]
        ],

        BusinessType::PRIVATE_LIMITED => [
            Operator::AND   => [
                Entity::POI_VERIFICATION_STATUS          => [POIStatus::VERIFIED],
                Entity::POA_VERIFICATION_STATUS          => [POIStatus::VERIFIED],
                Entity::BANK_DETAILS_VERIFICATION_STATUS => [POIStatus::VERIFIED],
                Entity::COMPANY_PAN_VERIFICATION_STATUS  => [POIStatus::VERIFIED],
                Entity::CIN_VERIFICATION_STATUS          => [POIStatus::VERIFIED],
            ]
        ],

        BusinessType::PUBLIC_LIMITED => [
            Operator::AND   => [
                Entity::POI_VERIFICATION_STATUS          => [POIStatus::VERIFIED],
                Entity::POA_VERIFICATION_STATUS          => [POIStatus::VERIFIED],
                Entity::BANK_DETAILS_VERIFICATION_STATUS => [POIStatus::VERIFIED],
                Entity::COMPANY_PAN_VERIFICATION_STATUS  => [POIStatus::VERIFIED],
                Entity::CIN_VERIFICATION_STATUS          => [POIStatus::VERIFIED],
            ]
        ],

        BusinessType::LLP => [
            Operator::AND   => [
                Entity::POI_VERIFICATION_STATUS          => [POIStatus::VERIFIED],
                Entity::POA_VERIFICATION_STATUS          => [POIStatus::VERIFIED],
                Entity::BANK_DETAILS_VERIFICATION_STATUS => [POIStatus::VERIFIED],
                Entity::COMPANY_PAN_VERIFICATION_STATUS  => [POIStatus::VERIFIED],
                Entity::CIN_VERIFICATION_STATUS          => [POIStatus::VERIFIED],
            ]
        ],
    ];
}
