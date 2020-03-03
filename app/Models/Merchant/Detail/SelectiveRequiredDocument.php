<?php

namespace RZP\Models\Merchant\Detail;

use RZP\Models\Merchant\Document\Type;

class SelectiveRequiredDocument
{
    const POA_DOCUMENTS                         = 'poa_documents';
    const SEBI_REGISTRATION_CERTIFICATE_OR_SLA  = 'sebi_registration_certificate_or_sla';
    const IRDAI_REGISTRATION_CERTIFICATE_OR_SLA = 'irdai_registration_certificate_or_sla';
    const FFMC_LICENSE_OR_SLA                   = 'ffmc_license_or_sla';
    const NBFC_REGISTRATION_CERTIFICATE_OR_SLA  = 'nbfc_registration_certificate_or_sla';
    const AMFI_CERTIFICATE_OR_SLA               = 'amfi_certificate_or_sla';

    /**
     * This contains documents required for unregistered business
     */
    const UNREGISTERED = [
        self::POA_DOCUMENTS => [
            [Type::AADHAR_FRONT, Type::AADHAR_BACK],
            [Type::PASSPORT_FRONT, Type::PASSPORT_BACK],
            [Type::VOTER_ID_FRONT, Type::VOTER_ID_BACK],
            [Type::DRIVER_LICENSE_FRONT, Type::DRIVER_LICENSE_BACK],
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

    /**
     * list of all selective required fields
     */
    const ALL_SELECTIVE_FIELDS = [
        SelectiveRequiredDocument::MUTUAL_FUND,
        SelectiveRequiredDocument::LENDING,
        SelectiveRequiredDocument::INSURANCE,
        SelectiveRequiredDocument::NBFC,
        SelectiveRequiredDocument::FOREX,
        SelectiveRequiredDocument::SECURITIES,
        SelectiveRequiredDocument::COMMODITIES,
        SelectiveRequiredDocument::FINANCIAL_ADVISOR,
        SelectiveRequiredDocument::TRADING,
        SelectiveRequiredDocument::UNREGISTERED,
    ];
}
