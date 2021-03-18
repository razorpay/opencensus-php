<?php

namespace RZP\Models\Merchant\AutoKyc;

use RZP\Constants\Entity as E;
use RZP\lib\ConditionParser\Operator;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\Detail\POIStatus;
use RZP\Models\Merchant\Detail\BusinessType;
use RZP\Models\Merchant\Stakeholder\Entity as SEntity;


class Constants
{
    const ENTITY = 'entity';
    const IN     = 'in';

    const DEFAULT_CONDITION = [
        'entity'    => E::MERCHANT_DETAIL,
        'in'        => [POIStatus::VERIFIED]
    ];

    const POI_CONDITION = self::DEFAULT_CONDITION;

    const POA_CONDITION = self::DEFAULT_CONDITION;

    const BANK_DETAILS_CONDITION = self::DEFAULT_CONDITION;

    const COMPANY_PAN_CONDITION = self::DEFAULT_CONDITION;

    const GSTIN_CONDITION = self::DEFAULT_CONDITION;

    const SHOP_ESTABLISHMENT_CONDITION = self::DEFAULT_CONDITION;

    const CIN_CONDITION = self::DEFAULT_CONDITION;

    const ESIGN_AADHAAR_CONDITION = [
        'entity'    => E::STAKEHOLDER,
        'in' => [POIStatus::VERIFIED]
    ];

    const AADHAAR_WITH_PAN_CONDITION = [
        'entity'    => E::STAKEHOLDER,
        'in' => [POIStatus::VERIFIED]
    ];

    const BANK_DETAILS_VERIFICATION_CONDITION = [
        Operator::OR => [
            Entity::BANK_DETAILS_VERIFICATION_STATUS        => self::DEFAULT_CONDITION,
            Entity::BANK_DETAILS_DOC_VERIFICATION_STATUS    => self::DEFAULT_CONDITION
        ]
    ];

    const AUTO_KYC_VERIFICATION_CONDITIONS = [
        BusinessType::NOT_YET_REGISTERED => [
            Operator::AND   =>  [
                Entity::POI_VERIFICATION_STATUS          => self::POI_CONDITION,
                SEntity::AADHAAR_ESIGN_STATUS            => self::ESIGN_AADHAAR_CONDITION,
                SEntity::AADHAAR_VERIFICATION_WITH_PAN_STATUS => self::AADHAAR_WITH_PAN_CONDITION,
                Operator::AND => self::BANK_DETAILS_VERIFICATION_CONDITION
            ]
        ],

        BusinessType::INDIVIDUAL => [
            Operator::AND   =>  [
                Entity::POI_VERIFICATION_STATUS          => self::POI_CONDITION,
                SEntity::AADHAAR_ESIGN_STATUS            => self::ESIGN_AADHAAR_CONDITION,
                SEntity::AADHAAR_VERIFICATION_WITH_PAN_STATUS => self::AADHAAR_WITH_PAN_CONDITION,
                Operator::AND => self::BANK_DETAILS_VERIFICATION_CONDITION
            ]
        ],

        BusinessType::PROPRIETORSHIP => [
            Operator::AND   => [
                Entity::POI_VERIFICATION_STATUS          => self::POI_CONDITION,
                SEntity::AADHAAR_ESIGN_STATUS            => self::ESIGN_AADHAAR_CONDITION,
                SEntity::AADHAAR_VERIFICATION_WITH_PAN_STATUS => self::AADHAAR_WITH_PAN_CONDITION,
                Operator::OR  => [
                    Entity::GSTIN_VERIFICATION_STATUS               => self::GSTIN_CONDITION,
                    Entity::SHOP_ESTABLISHMENT_VERIFICATION_STATUS  => self::SHOP_ESTABLISHMENT_CONDITION,
                ],
                Operator::AND => self::BANK_DETAILS_VERIFICATION_CONDITION
            ]
        ],

        BusinessType::PRIVATE_LIMITED => [
            Operator::AND   => [
                Entity::POI_VERIFICATION_STATUS          => self::POI_CONDITION,
                Entity::POA_VERIFICATION_STATUS          => self::POA_CONDITION,
                Entity::COMPANY_PAN_VERIFICATION_STATUS  => self::COMPANY_PAN_CONDITION,
                Entity::CIN_VERIFICATION_STATUS          => self::CIN_CONDITION,
                Operator::AND => self::BANK_DETAILS_VERIFICATION_CONDITION
            ]
        ],

        BusinessType::PUBLIC_LIMITED => [
            Operator::AND   => [
                Entity::POI_VERIFICATION_STATUS          => self::POI_CONDITION,
                Entity::POA_VERIFICATION_STATUS          => self::POA_CONDITION,
                Entity::COMPANY_PAN_VERIFICATION_STATUS  => self::COMPANY_PAN_CONDITION,
                Entity::CIN_VERIFICATION_STATUS          => self::CIN_CONDITION,
                Operator::AND => self::BANK_DETAILS_VERIFICATION_CONDITION
            ]
        ],

        BusinessType::LLP => [
            Operator::AND   => [
                Entity::POI_VERIFICATION_STATUS          => self::POI_CONDITION,
                Entity::POA_VERIFICATION_STATUS          => self::POA_CONDITION,
                Entity::COMPANY_PAN_VERIFICATION_STATUS  => self::COMPANY_PAN_CONDITION,
                Entity::CIN_VERIFICATION_STATUS          => self::CIN_CONDITION,
                Operator::AND => self::BANK_DETAILS_VERIFICATION_CONDITION
            ]
        ],
    ];

    const PARTNER_KYC_VERIFICATION_CONDITIONS = [
        Operator::AND   => [
            Operator::OR => [
                Entity::COMPANY_PAN_VERIFICATION_STATUS  => self::COMPANY_PAN_CONDITION,
                Entity::POI_VERIFICATION_STATUS          => self::POI_CONDITION,
                Entity::GSTIN_VERIFICATION_STATUS        => self::GSTIN_CONDITION
            ],
            Operator::AND => self::BANK_DETAILS_VERIFICATION_CONDITION
        ]
    ];
}
