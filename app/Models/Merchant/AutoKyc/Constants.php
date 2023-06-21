<?php

namespace RZP\Models\Merchant\AutoKyc;

use RZP\Constants\Entity as E;
use RZP\lib\ConditionParser\Operator;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\Detail\POIStatus;
use RZP\Models\Merchant\Detail\BusinessType;
use RZP\Models\Merchant\Stakeholder\Entity as SEntity;
use RZP\Models\Merchant\Constants as MerchantConstants;
use RZP\Models\Merchant\VerificationDetail\Entity as VDEntity;


class Constants
{
    const ENTITY = 'entity';
    const IN     = 'in';
    const EXPERIMENT_ID    = 'experiment_id';
    const DEFAULT_VALUE    = 'default_value';

    const DEFAULT_CONDITION = [
        'entity' => E::MERCHANT_DETAIL,
        'in'     => [POIStatus::VERIFIED]
    ];

    const DEFAULT_SIGNATORY_VERIFICATION_CONDITION = [
        'entity'            => E::MERCHANT_VERIFICATION_DETAIL,
        'in'                => [POIStatus::VERIFIED],
        self::EXPERIMENT_ID => 'signatory_validations_experiment_id',
        self::DEFAULT_VALUE => true
    ];


    const POI_CONDITION = self::DEFAULT_CONDITION;

    const POA_CONDITION = self::DEFAULT_CONDITION;

    const BANK_DETAILS_CONDITION = self::DEFAULT_CONDITION;

    const COMPANY_PAN_CONDITION = self::DEFAULT_CONDITION;

    const GSTIN_CONDITION = self::DEFAULT_CONDITION;

    const SHOP_ESTABLISHMENT_CONDITION = self::DEFAULT_CONDITION;

    const MSME_DOC_VERIFICATION_CONDITION = self::DEFAULT_CONDITION;

    const DEFAULT_VERIFICATION_DETAIL_CONDITION = [
        'entity'            => E::MERCHANT_VERIFICATION_DETAIL,
        'in'                => [POIStatus::VERIFIED],
        self::DEFAULT_VALUE => false
    ];


    const CIN_CONDITION = self::DEFAULT_CONDITION;

    const ESIGN_AADHAAR_CONDITION = [
        'entity' => E::STAKEHOLDER,
        'in'     => [POIStatus::VERIFIED]
    ];

    const AADHAAR_WITH_PAN_CONDITION = [
        'entity' => E::STAKEHOLDER,
        'in'     => [POIStatus::VERIFIED]
    ];

    const BANK_DETAILS_VERIFICATION_CONDITION = [
        Entity::BANK_DETAILS_VERIFICATION_STATUS => self::DEFAULT_CONDITION,
    ];

    const POA_VERIFICATION_CONDITION = [
        Operator:: OR => [
            Operator:: AND                  => [
                SEntity::AADHAAR_VERIFICATION_WITH_PAN_STATUS => self::AADHAAR_WITH_PAN_CONDITION,
                SEntity::AADHAAR_ESIGN_STATUS                 => self::ESIGN_AADHAAR_CONDITION,
            ],
            Entity::POA_VERIFICATION_STATUS => self::POA_CONDITION,
        ]
    ];

    const LINKED_ACCOUNT_VERIFICATION_CONDITIONS = [
        Operator:: AND => [
            Entity::BANK_DETAILS_VERIFICATION_STATUS => self::DEFAULT_CONDITION,
        ]
    ];

    const AUTO_KYC_VERIFICATION_CONDITIONS = [
        BusinessType::NOT_YET_REGISTERED => [
            Operator:: AND => [
                Entity::POI_VERIFICATION_STATUS => self::POI_CONDITION,
                Operator:: AND                  => [
                    Operator:: AND => self::BANK_DETAILS_VERIFICATION_CONDITION,
                    Operator:: OR  => self::POA_VERIFICATION_CONDITION
                ]
            ]
        ],

        BusinessType::INDIVIDUAL => [
            Operator::AND => [
                Operator:: AND => [
                    Entity::POI_VERIFICATION_STATUS => self::POI_CONDITION,
                    Operator:: AND                  => [
                        Operator:: AND => self::BANK_DETAILS_VERIFICATION_CONDITION,
                        Operator:: OR  => self::POA_VERIFICATION_CONDITION
                    ]
                ],
                'signatory_validation|number' => self::DEFAULT_SIGNATORY_VERIFICATION_CONDITION,
            ],
        ],

        BusinessType::PROPRIETORSHIP => [
            Operator::AND => [
                Operator:: AND                => [
                    Entity::POI_VERIFICATION_STATUS => self::POI_CONDITION,
                    Operator:: OR                   => [
                        Entity::GSTIN_VERIFICATION_STATUS              => self::GSTIN_CONDITION,
                        Entity::SHOP_ESTABLISHMENT_VERIFICATION_STATUS => self::SHOP_ESTABLISHMENT_CONDITION,
                        Entity::MSME_DOC_VERIFICATION_STATUS           => self::MSME_DOC_VERIFICATION_CONDITION,
                        /* added the following for handling shop establishment document in the new merchant_verification_detail table
                         * the format followed is 'artefact_type|artefact_identifier'
                         */
                        'shop_establishment|doc'                       => self::DEFAULT_VERIFICATION_DETAIL_CONDITION,
                        'gstin|doc'                                    => self::DEFAULT_VERIFICATION_DETAIL_CONDITION
                    ],
                    Operator:: AND                  => [
                        Operator:: AND => self::BANK_DETAILS_VERIFICATION_CONDITION,
                        Operator:: OR  => self::POA_VERIFICATION_CONDITION
                    ]
                ],
                'signatory_validation|number' => self::DEFAULT_SIGNATORY_VERIFICATION_CONDITION,
            ],
        ],

        BusinessType::PRIVATE_LIMITED => [
            Operator::AND => [
                Operator:: AND                => [
                    Entity::POI_VERIFICATION_STATUS         => self::POI_CONDITION,
                    Entity::COMPANY_PAN_VERIFICATION_STATUS => self::COMPANY_PAN_CONDITION,
                    Operator:: AND                          => [
                        Operator:: AND => self::BANK_DETAILS_VERIFICATION_CONDITION,
                        Operator:: OR  => self::POA_VERIFICATION_CONDITION
                    ],
                    Operator:: OR                           => [
                        Entity::CIN_VERIFICATION_STATUS    => self::CIN_CONDITION,
                        'certificate_of_incorporation|doc' => self::DEFAULT_VERIFICATION_DETAIL_CONDITION
                    ]
                ],
                'signatory_validation|number' => self::DEFAULT_SIGNATORY_VERIFICATION_CONDITION,
            ],
        ],

        BusinessType::PUBLIC_LIMITED => [
            Operator::AND => [
                Operator:: AND                => [
                    Entity::POI_VERIFICATION_STATUS         => self::POI_CONDITION,
                    Entity::COMPANY_PAN_VERIFICATION_STATUS => self::COMPANY_PAN_CONDITION,
                    Operator:: AND                          => [
                        Operator:: AND => self::BANK_DETAILS_VERIFICATION_CONDITION,
                        Operator:: OR  => self::POA_VERIFICATION_CONDITION
                    ],
                    Operator:: OR                           => [
                        Entity::CIN_VERIFICATION_STATUS    => self::CIN_CONDITION,
                        'certificate_of_incorporation|doc' => self::DEFAULT_VERIFICATION_DETAIL_CONDITION
                    ]
                ],
                'signatory_validation|number' => self::DEFAULT_SIGNATORY_VERIFICATION_CONDITION,
            ],
        ],

        BusinessType::PARTNERSHIP => [
            Operator::AND => [
                Operator:: AND                => [
                    Entity::POI_VERIFICATION_STATUS          => self::POI_CONDITION,
                    Entity::COMPANY_PAN_VERIFICATION_STATUS  => self::COMPANY_PAN_CONDITION,
                    Entity::BANK_DETAILS_VERIFICATION_STATUS => self::DEFAULT_CONDITION,
                    'partnership_deed|doc'                   => self::DEFAULT_VERIFICATION_DETAIL_CONDITION,
                    Operator:: OR                            => self::POA_VERIFICATION_CONDITION,
                ],
                'signatory_validation|number' => self::DEFAULT_SIGNATORY_VERIFICATION_CONDITION,
            ],
        ],

        BusinessType::TRUST => [
            Operator::AND => [
                Operator:: AND                => [
                    Entity::POI_VERIFICATION_STATUS              => self::POI_CONDITION,
                    Entity::COMPANY_PAN_VERIFICATION_STATUS      => self::COMPANY_PAN_CONDITION,
                    Operator:: OR                                => self::POA_VERIFICATION_CONDITION,
                    Entity::BANK_DETAILS_VERIFICATION_STATUS     => self::DEFAULT_CONDITION,
                    'trust_society_ngo_business_certificate|doc' => self::DEFAULT_VERIFICATION_DETAIL_CONDITION,
                ],
                'signatory_validation|number' => self::DEFAULT_SIGNATORY_VERIFICATION_CONDITION,
            ],
        ],

        BusinessType::SOCIETY => [
            Operator::AND => [
                Operator:: AND                => [
                    Entity::POI_VERIFICATION_STATUS              => self::POI_CONDITION,
                    Entity::COMPANY_PAN_VERIFICATION_STATUS      => self::COMPANY_PAN_CONDITION,
                    Operator:: OR                                => self::POA_VERIFICATION_CONDITION,
                    Entity::BANK_DETAILS_VERIFICATION_STATUS     => self::DEFAULT_CONDITION,
                    'trust_society_ngo_business_certificate|doc' => self::DEFAULT_VERIFICATION_DETAIL_CONDITION
                ],
                'signatory_validation|number' => self::DEFAULT_SIGNATORY_VERIFICATION_CONDITION,
            ],
        ],

        BusinessType::LLP => [
            Operator::AND => [
                Operator:: AND                => [
                    Entity::POI_VERIFICATION_STATUS         => self::POI_CONDITION,
                    Entity::COMPANY_PAN_VERIFICATION_STATUS => self::COMPANY_PAN_CONDITION,
                    Operator:: AND                          => [
                        Operator:: AND => self::BANK_DETAILS_VERIFICATION_CONDITION,
                        Operator:: OR  => self::POA_VERIFICATION_CONDITION
                    ],
                    Operator:: OR                           => [
                        Entity::CIN_VERIFICATION_STATUS    => self::CIN_CONDITION,
                        'certificate_of_incorporation|doc' => self::DEFAULT_VERIFICATION_DETAIL_CONDITION,
                    ]
                ],
                'signatory_validation|number' => self::DEFAULT_SIGNATORY_VERIFICATION_CONDITION,
            ],
        ],
    ];

    const AUTO_KYC_VERIFICATION_CONDITIONS_NO_DOC = [
        BusinessType::NOT_YET_REGISTERED => [
            Operator:: AND => [
                Operator:: OR                            => [
                    Entity::COMPANY_PAN_VERIFICATION_STATUS => self::COMPANY_PAN_CONDITION,
                    Entity::POI_VERIFICATION_STATUS         => self::POI_CONDITION,
                ],
                Entity::BANK_DETAILS_VERIFICATION_STATUS => self::DEFAULT_CONDITION
            ]
        ],

        BusinessType::PROPRIETORSHIP => [
            Operator:: AND => [
                Operator:: OR                            => [
                    Entity::COMPANY_PAN_VERIFICATION_STATUS => self::COMPANY_PAN_CONDITION,
                    Entity::POI_VERIFICATION_STATUS         => self::POI_CONDITION,
                ],
                Entity::BANK_DETAILS_VERIFICATION_STATUS => self::DEFAULT_CONDITION
            ]
        ],

        MerchantConstants::DEFAULT => [
            Operator:: AND => [
                Entity::COMPANY_PAN_VERIFICATION_STATUS  => self::COMPANY_PAN_CONDITION,
                Entity::BANK_DETAILS_VERIFICATION_STATUS => self::DEFAULT_CONDITION,
            ]
        ],
    ];

    const AUTO_KYC_VERIFICATION_CONDITIONS_ROUTE_NO_DOC = [
        BusinessType::NOT_YET_REGISTERED => [
            Operator:: AND => [
                Entity::POI_VERIFICATION_STATUS         => self::POI_CONDITION,
                Entity::BANK_DETAILS_VERIFICATION_STATUS => self::DEFAULT_CONDITION
            ]
        ],

        BusinessType::INDIVIDUAL => [
            Operator:: AND => [
                Entity::POI_VERIFICATION_STATUS         => self::POI_CONDITION,
                Entity::BANK_DETAILS_VERIFICATION_STATUS => self::DEFAULT_CONDITION
            ]
        ],

        BusinessType::PROPRIETORSHIP => [
            Operator:: AND => [
                Operator:: OR                            => [
                    Entity::COMPANY_PAN_VERIFICATION_STATUS => self::COMPANY_PAN_CONDITION,
                    Entity::POI_VERIFICATION_STATUS         => self::POI_CONDITION,
                ],
                Entity::BANK_DETAILS_VERIFICATION_STATUS => self::DEFAULT_CONDITION
            ]
        ],

        MerchantConstants::DEFAULT => [
            Operator:: AND => [
                Entity::COMPANY_PAN_VERIFICATION_STATUS  => self::COMPANY_PAN_CONDITION,
                Entity::BANK_DETAILS_VERIFICATION_STATUS => self::DEFAULT_CONDITION,
            ]
        ],
    ];

    const PARTNER_KYC_VERIFICATION_CONDITIONS = [
        BusinessType::NOT_YET_REGISTERED => [
            Operator:: AND => [
                Entity::POI_VERIFICATION_STATUS => self::POI_CONDITION,
                Operator:: AND                  => self::BANK_DETAILS_VERIFICATION_CONDITION
            ]
        ],

        BusinessType::PROPRIETORSHIP => [
            Operator:: AND => [
                Entity::POI_VERIFICATION_STATUS   => self::POI_CONDITION,
                Entity::GSTIN_VERIFICATION_STATUS => self::GSTIN_CONDITION,
                Operator:: AND                    => self::BANK_DETAILS_VERIFICATION_CONDITION
            ]
        ],

        MerchantConstants:: DEFAULT => [
            Operator:: AND => [
                Entity::COMPANY_PAN_VERIFICATION_STATUS => self::COMPANY_PAN_CONDITION,
                Entity::GSTIN_VERIFICATION_STATUS       => self::GSTIN_CONDITION,
                Operator:: AND                          => self::BANK_DETAILS_VERIFICATION_CONDITION
            ]
        ]
    ];
}
