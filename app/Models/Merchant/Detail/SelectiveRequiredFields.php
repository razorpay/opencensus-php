<?php

namespace RZP\Models\Merchant\Detail;

use RZP\Models\Merchant\Document\Type;

class SelectiveRequiredFields
{
    const POA_DOCUMENTS                           = 'poa_documents';
    const PERSONAL_PAN_DOCUMENTS                  = 'personal_pan_documents';
    const SEBI_REGISTRATION_CERTIFICATE_OR_SLA    = 'sebi_registration_certificate_or_sla';
    const IRDAI_REGISTRATION_CERTIFICATE_OR_SLA   = 'irdai_registration_certificate_or_sla';
    const FFMC_LICENSE_OR_SLA                     = 'ffmc_license_or_sla';
    const NBFC_REGISTRATION_CERTIFICATE_OR_SLA    = 'nbfc_registration_certificate_or_sla';
    const AMFI_CERTIFICATE_OR_SLA                 = 'amfi_certificate_or_sla';
    const PROPRIETORSHIP_BUSINESS_PROOF_DOCUMENTS = 'proprietorship_business_proof_documents';

    /**
     * This contains documents required for unregistered business
     */
    const UNREGISTERED_POA_FIELDS = [
        self::POA_DOCUMENTS => [
            [Type::AADHAR_FRONT, Type::AADHAR_BACK],
            [Type::PASSPORT_FRONT, Type::PASSPORT_BACK],
            [Type::VOTER_ID_FRONT, Type::VOTER_ID_BACK],
            [Type::DRIVER_LICENSE_FRONT, Type::DRIVER_LICENSE_BACK],
        ]
    ];

    const REGISTERED_POA_FIELDS = [
        self::POA_DOCUMENTS => [
            [Type::PROMOTER_ADDRESS_URL],
            [Type::AADHAR_FRONT, Type::AADHAR_BACK],
            [Type::PASSPORT_FRONT, Type::PASSPORT_BACK],
            [Type::VOTER_ID_FRONT, Type::VOTER_ID_BACK],
            [Type::DRIVER_LICENSE_FRONT, Type::DRIVER_LICENSE_BACK],
        ]
    ];

    const PROPRIETORSHIP_PERSONAL_PAN_DOCUMENTS = [
        self::PERSONAL_PAN_DOCUMENTS => [
            [Type::BUSINESS_PAN_URL],
            [Type::PERSONAL_PAN]
        ]
    ];

    const PROPRIETORSHIP_BUSINESS_PROOFS = [
        self::PROPRIETORSHIP_BUSINESS_PROOF_DOCUMENTS => [
            [Type::BUSINESS_PROOF_URL],
            [Type::SHOP_ESTABLISHMENT_CERTIFICATE],
            [Type::GST_CERTIFICATE],
            [Type::MSME_CERTIFICATE],
        ]
    ];

    const MUTUAL_FUND = [
        self::AMFI_CERTIFICATE_OR_SLA => [
            [Type::AMFI_CERTIFICATE],
            [Type::SLA_AMFI_CERTIFICATE],
        ],
    ];

    const LENDING = [
        self::NBFC_REGISTRATION_CERTIFICATE_OR_SLA => [
            [Type::NBFC_REGISTRATION_CERTIFICATE],
            [Type::SLA_NBFC_REGISTRATION_CERTIFICATE],
        ],
    ];

    const INSURANCE = [
        self::IRDAI_REGISTRATION_CERTIFICATE_OR_SLA => [
            [Type::IRDAI_REGISTRATION_CERTIFICATE],
            [Type::SLA_IRDAI_REGISTRATION_CERTIFICATE],
        ],
    ];

    const NBFC = [
        self::NBFC_REGISTRATION_CERTIFICATE_OR_SLA => [
            [Type::NBFC_REGISTRATION_CERTIFICATE],
            [Type::SLA_NBFC_REGISTRATION_CERTIFICATE],
        ],
    ];

    const FOREX = [
        self::FFMC_LICENSE_OR_SLA => [
            [Type::FFMC_LICENSE],
            [Type::SLA_FFMC_LICENSE],
        ],
    ];

    const SECURITIES = [
        self::SEBI_REGISTRATION_CERTIFICATE_OR_SLA => [
            [Type::SEBI_REGISTRATION_CERTIFICATE],
            [Type::SLA_SEBI_REGISTRATION_CERTIFICATE],
        ],
    ];

    const COMMODITIES = [
        self::SEBI_REGISTRATION_CERTIFICATE_OR_SLA => [
            [Type::SEBI_REGISTRATION_CERTIFICATE],
            [Type::SLA_SEBI_REGISTRATION_CERTIFICATE],
        ],
    ];

    const FINANCIAL_ADVISOR = [
        self::SEBI_REGISTRATION_CERTIFICATE_OR_SLA => [
            [Type::SEBI_REGISTRATION_CERTIFICATE],
            [Type::SLA_SEBI_REGISTRATION_CERTIFICATE],
        ],
    ];

    const TRADING = [
        self::SEBI_REGISTRATION_CERTIFICATE_OR_SLA => [
            [Type::SEBI_REGISTRATION_CERTIFICATE],
            [Type::SLA_SEBI_REGISTRATION_CERTIFICATE],
        ],
    ];

    const BANK_PROOF_DOCUMENTS = [
        Type::CANCELLED_CHEQUE,
        Type::BANK_STATEMENT
    ];

    /**
     * list of all selective required fields
     */
    const ALL_SELECTIVE_FIELDS = [
        self::MUTUAL_FUND,
        self::LENDING,
        self::INSURANCE,
        self::NBFC,
        self::FOREX,
        self::SECURITIES,
        self::COMMODITIES,
        self::FINANCIAL_ADVISOR,
        self::TRADING,
        self::UNREGISTERED_POA_FIELDS,
        self::REGISTERED_POA_FIELDS,
        self::PROPRIETORSHIP_BUSINESS_PROOFS,
        self::PROPRIETORSHIP_PERSONAL_PAN_DOCUMENTS,
    ];
}
