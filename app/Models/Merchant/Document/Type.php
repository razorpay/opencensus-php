<?php

namespace RZP\Models\Merchant\Document;
use RZP\Constants\Entity as E;
use RZP\Models\FileStore\Format;

class Type
{
    const SEBI_REGISTRATION_CERTIFICATE  = 'sebi_registration_certificate';
    const CPV_REPORT                     = 'cpv_report';
    const IRDAI_REGISTRATION_CERTIFICATE = 'irdai_registration_certificate';
    const FFMC_LICENSE                   = 'ffmc_license';
    const NBFC_REGISTRATION_CERTIFICATE  = 'nbfc_registration_certificate';
    const AMFI_CERTIFICATE               = 'amfi_certificate';

    //sla refer to service level agreement
    const SLA_SEBI_REGISTRATION_CERTIFICATE  = 'sla_sebi_registration_certificate';
    const SLA_IRDAI_REGISTRATION_CERTIFICATE = 'sla_irdai_registration_certificate';
    const SLA_FFMC_LICENSE                   = 'sla_ffmc_license';
    const SLA_NBFC_REGISTRATION_CERTIFICATE  = 'sla_nbfc_registration_certificate';
    const SLA_AMFI_CERTIFICATE               = 'sla_amfi_certificate';
    const SLA_IATA_CERTIFICATE               = 'sla_iata_certificate';

    //optional documents
    const AFFILIATION_CERTIFICATE = 'affiliation_certificate';
    const IATA_CERTIFICATE        = 'iata_certificate';

    const PPI_LICENSE                  = 'ppi_license';
    const DRIVER_LICENSE_FRONT         = 'driver_license_front';
    const DRIVER_LICENSE_BACK          = 'driver_license_back';
    const AADHAR_FRONT                 = 'aadhar_front';
    const AADHAR_BACK                  = 'aadhar_back';
    const AADHAR_XML                   = 'aadhar_xml';
    const AADHAR_ZIP                   = 'aadhar_zip';
    const PASSPORT_BACK                = 'passport_back';
    const PASSPORT_FRONT               = 'passport_front';
    const VOTER_ID_FRONT               = 'voter_id_front';
    const VOTER_ID_BACK                = 'voter_id_back';
    const CANCELLED_CHEQUE             = 'cancelled_cheque';
    const BUSINESS_PROOF_URL           = 'business_proof_url';
    const BUSINESS_OPERATION_PROOF_URL = 'business_operation_proof_url';
    const BUSINESS_PAN_URL             = 'business_pan_url';
    const ADDRESS_PROOF_URL            = 'address_proof_url';
    const PROMOTER_PROOF_URL           = 'promoter_proof_url';
    const PROMOTER_PAN_URL             = 'promoter_pan_url';
    const PROMOTER_ADDRESS_URL         = 'promoter_address_url';
    const FORM_12A_URL                 = 'form_12a_url';
    const FORM_80G_URL                 = 'form_80g_url';
    const MEMORANDUM_OF_ASSOCIATION    = 'memorandum_of_association';
    const ARTICLE_OF_ASSOCIATION       = 'article_of_association';
    const BOARD_RESOLUTION             = 'board_resolution';
    const BANK_VERIFICATION_LETTER     = 'bank_verification_letter';

    // For KYC service integration
    const PERSONAL_PAN    = 'personal_pan';
    const AADHAAR         = 'aadhaar';
    const PASSPORT        = 'passport';
    const VOTERS_ID       = 'voters_id';
    const DRIVERS_LICENSE = 'drivers_license';

    const SHOP_ESTABLISHMENT_CERTIFICATE = "shop_establishment_certificate";
    const GST_CERTIFICATE                = "gst_certificate";
    const MSME_CERTIFICATE               = "msme_certificate";
    const BANK_STATEMENT                 = "bank_statement";

    //proof types
    const INDIVIDUAL_PROOF_OF_ADDRESS        = 'individual_proof_of_address';
    const INDIVIDUAL_PROOF_OF_IDENTIFICATION = 'individual_proof_of_identification';
    const BUSINESS_PROOF_OF_IDENTIFICATION   = 'business_proof_of_identification';
    const ADDITIONAL_DOCUMENTS               = 'additional_documents';
    const POI_IDENTIFICATION_NUMBER          = 'poi_identification_number';
    const POA_IDENTIFICATION_NUMBER          = 'poa_identification_number';
    const IDENTIFICATION_NUMBER              = 'identification_number';

    const OTHER                  = 'other';
    const WEBSITE_SCREENSHOT     = 'website_screenshot';
    const GAMING_ADDENDUM        = 'gaming_addendum';
    const LEGAL_OPINION          = 'legal_opinion';
    const BUSINESS_CORRESPONDENT = 'business_correspondent';


    //FIRS Documents
    const FIRS_FILE = 'firs_file';
    const FIRS_ZIP  = 'firs_zip';

    const FIRS_ICICI_FILE = 'firs_icici_file';
    const FIRS_ICICI_ZIP  = 'firs_icici_zip';

    const FIRS_FIRSTDATA_FILE = 'firs_firstdata_file';
    const FIRS_FIRSTDATA_SUM_FILE = 'firs_firstdata_sum_file';

    // EmerchantPay Onboarding Documents

    const EMERCHANTPAY_GST_CERTIFICATE    = 'emerchantpay_gst_certificate';
    const EMERCHANTPAY_PROOF_OF_OWNERSHIP = 'emerchantpay_proof_of_ownership';
    const EMERCHANTPAY_AADHAAR            = 'emerchantpay_aadhaar';
    const EMERCHANTPAY_PAN                = 'emerchantpay_pan';
    const EMERCHANTPAY_PASSPORT           = 'emerchantpay_passport';


    const FSSAI_CERTIFICATE                 = 'fssai_certificate';
    const AYUSH_CERTIFICATE                 = 'ayush_certificate';
    const IRDA_CERTIFICATE                  = 'irda_certificate';
    const FDA_CERTIFICATE                   = 'fda_certificate';
    const DOT_CERTIFICATE                   = 'dot_certificate';
    const TRAI_CERTIFICATE                  = 'trai_certificate';
    const RBI_CERTIFICATE                   = 'rbi_certificate';
    const DGCA_CERTIFICATE                  = 'dgca_certificate';
    const NATIONAL_HOUSING_BANK_CERTIFICATE = 'national_housing_bank_certificate';
    const DEALERSHIP_RIGHTS_CERTIFCATE      = 'dealership_rights_certificate';
    const PCI_DSS_CERTIFICATE               = 'pci_dss_certificate';
    const GII_CERTIFICATE                   = 'gii_certificate';
    const PHARMACY_DRUG_LICENSE             = 'pharmacy_drug_license';
    const FORM_20_20b_21_21b                = 'form_20_20b_21_21b';
    const INVOICE                           = 'invoice';
    const SLA_DEALERSHIP_AGREEMENT          = 'sla_dealership_agreement';
    const RESELLER_AGREEMENT                = 'reseller_agreement';
    const LIQUOR_LICENSE                    = 'liquor_license';
    const FORM_8A                           = 'form_8a';
    const FORM_10AC                         = 'form_10ac';
    const IRCTC_AGENT_AGREEMENT             = 'irctc_agent_agreement';
    const UNDERTAKING                       = 'undertaking';
    const EPF_SCHEME_CERTIFICATE            = 'epf_scheme_certificate';
    const PROOF_OF_PROFESSION               = 'proof_of_profession';
    const BIS_CERTIFICATE                   = 'bis_certificate';
    const GIA_CERTIFICATE                   = 'gia_certificate';
    const PM_WANI_CERTIFICATE               = 'pm_wani_certificate';
    const PESO_LICENSE                     =  'peso_license';
    const DOMAIN_OWNERSHIP_DOCUMENT         = 'domain_ownership_document';
    const IEC_LICENSE                       = 'iec_license';
    const BUSINESS_CORRESPONDENT_DOCUMENT   = 'business_correspondent_document';
    const MMTC_PAMP_LICENSE                 = 'mmtc_pamp_license';
    const SAFEGOLD_PARTNERSHIP_DOCUMENT     = 'safegold_partnership_document';
    const BRAND_TIE_UP_DOCUMENT             = 'brand_tie_up_document';
    const FDA_LICENSE                       = 'fda_license';
    const FSSAI_LICENSE                     = 'fssai_license';
    const MERCHANT_SERVICE_ARGUMENT         = 'merchant_service_agreement';
    const LEGAL_OPINION_DOCUMENT            = 'legal_opinion_document';
    const UNDERTAKING_DOCUMENT              = 'undertaking_document';
    const MANUFACTURING_LICENSE             = 'manufacturing_license';
    const SLA_DOCUMENT                      = 'sla_document';
    const RERA_LICENSE                      = 'rera_license';
    const COPYWRITE_LICENSE                 = 'copywrite_license';
    const TRADE_LICENSE                     = 'trade_license';
    const IATO_LICENSE                      = 'iato_license';
    const BBPS_DOCUMENT                     = 'bbps_document';
    const MSO_DOCUMENT                      = 'mso_document';
    const GOVT_AUTHORISATION_LETTER         = 'govt_authorisation_letter';
    const CANCELLED_CHEQUE_VIDEO            = 'cancelled_cheque_video';
    const BOARD_RESOLUTION_LETTER           = 'board_resolution_letter';

    const NC_ADDITIONAL_DOCUMENTS = [

        self::FSSAI_CERTIFICATE,
        self::AYUSH_CERTIFICATE,
        self::SEBI_REGISTRATION_CERTIFICATE,
        self::FFMC_LICENSE,
        self::FORM_12A_URL,
        self::FORM_80G_URL,
        self::NBFC_REGISTRATION_CERTIFICATE,
        self::BIS_CERTIFICATE,
        self::IRDA_CERTIFICATE,
        self::AMFI_CERTIFICATE,
        self::IATA_CERTIFICATE,
        self::FDA_CERTIFICATE,
        self::DOT_CERTIFICATE,
        self::TRAI_CERTIFICATE,
        self::RBI_CERTIFICATE,
        self::DGCA_CERTIFICATE,
        self::NATIONAL_HOUSING_BANK_CERTIFICATE,
        self::AFFILIATION_CERTIFICATE,
        self::DEALERSHIP_RIGHTS_CERTIFCATE,
        self::PCI_DSS_CERTIFICATE,
        self::GII_CERTIFICATE,
        self::PHARMACY_DRUG_LICENSE,
        self::FORM_20_20b_21_21b,
        self::INVOICE,
        self::SLA_DEALERSHIP_AGREEMENT,
        self::RESELLER_AGREEMENT,
        self::LIQUOR_LICENSE,
        self::FORM_8A,
        self::FORM_10AC,
        self::IRCTC_AGENT_AGREEMENT,
        self::UNDERTAKING,
        self::EPF_SCHEME_CERTIFICATE,
        self::PROOF_OF_PROFESSION,
        self::GIA_CERTIFICATE,
        self::PM_WANI_CERTIFICATE,
        self::PESO_LICENSE,
        self::DOMAIN_OWNERSHIP_DOCUMENT,
        self::IEC_LICENSE,
        self::BUSINESS_CORRESPONDENT_DOCUMENT,
        self::MMTC_PAMP_LICENSE,
        self::SAFEGOLD_PARTNERSHIP_DOCUMENT,
        self::PPI_LICENSE,
        self::BRAND_TIE_UP_DOCUMENT,
        self::FDA_LICENSE,
        self::FSSAI_LICENSE,
        self::MERCHANT_SERVICE_ARGUMENT,
        self::LEGAL_OPINION_DOCUMENT,
        self::UNDERTAKING_DOCUMENT,
        self::MANUFACTURING_LICENSE,
        self::SLA_DOCUMENT,
        self::RERA_LICENSE,
        self::COPYWRITE_LICENSE,
        self::TRADE_LICENSE,
        self::IATO_LICENSE,
        self::BBPS_DOCUMENT,
        self::MSO_DOCUMENT,
        self::GOVT_AUTHORISATION_LETTER,
        self::CPV_REPORT,
        self::BOARD_RESOLUTION_LETTER
    ];

    const DOCUMENT_DESCRIPTION_MAP = [
        self::FSSAI_CERTIFICATE                 => "FSSAI certificate",
        self::AYUSH_CERTIFICATE                 => "Ayush certificate",
        self::SEBI_REGISTRATION_CERTIFICATE     => "SEBI Registration Certificate",
        self::FFMC_LICENSE                      => "FFMC License",
        self::FORM_12A_URL                      => "Form 12A Allotment Letter",
        self::FORM_80G_URL                      => "Form 80G Allotment Letter",
        self::NBFC_REGISTRATION_CERTIFICATE     => "NBFC Registration Certificatee",
        self::BIS_CERTIFICATE                   => "BIS certificate",
        self::IRDA_CERTIFICATE                  => "IRDA certificate",
        self::AMFI_CERTIFICATE                  => "AMFI Certificate",
        self::IATA_CERTIFICATE                  => "IATA Certificate",
        self::FDA_CERTIFICATE                   => "FDA certificate",
        self::DOT_CERTIFICATE                   => "DOT certificate",
        self::TRAI_CERTIFICATE                  => "TRAI certificate",
        self::RBI_CERTIFICATE                   => "RBI certificate",
        self::DGCA_CERTIFICATE                  => "DGCA certificate",
        self::NATIONAL_HOUSING_BANK_CERTIFICATE => "Certificate issued by National Housing Bank",
        self::AFFILIATION_CERTIFICATE           => "Affiliation Certificate",
        self::DEALERSHIP_RIGHTS_CERTIFCATE      => "Dealership rights",
        self::PCI_DSS_CERTIFICATE               => "AOC/Certificate PCI-DSS",
        self::GII_CERTIFICATE                   => "GII certificate",
        self::PHARMACY_DRUG_LICENSE             => "Pharmacy or Retail/wholesale Drug License",
        self::FORM_20_20b_21_21b                => "Form 20/21/20B/21B",
        self::INVOICE                           => "Invoice",
        self::SLA_DEALERSHIP_AGREEMENT          => "SLA/Dealership agreement",
        self::RESELLER_AGREEMENT                => "Re-seller agreement",
        self::LIQUOR_LICENSE                    => "Brewery addendum or Liquor license",
        self::FORM_8A                           => "Form 8A",
        self::FORM_10AC                         => "Form 10AC",
        self::IRCTC_AGENT_AGREEMENT             => "IRCTC agent agreement",
        self::UNDERTAKING                       => "Undertaking",
        self::EPF_SCHEME_CERTIFICATE            => "EPF scheme certificate",
        self::PROOF_OF_PROFESSION               => "Proof of profession",
        self::GIA_CERTIFICATE                   => "GIA certificate",
        self::PM_WANI_CERTIFICATE               => "PM WANI certificate",
        self::PESO_LICENSE                     =>  "PESO license",
        self::DOMAIN_OWNERSHIP_DOCUMENT         => "Domain ownership invoice / Self Declaration / AOC for PCI DSS",
        self::IEC_LICENSE                       => "IEC license",
        self::BUSINESS_CORRESPONDENT_DOCUMENT   => "Business correspondent document",
        self::MMTC_PAMP_LICENSE                 => "MMTC PAMP license",
        self::SAFEGOLD_PARTNERSHIP_DOCUMENT     => "Partnership document with safegold",
        self::PPI_LICENSE                       => "PPI license ( open and semi-closed )",
        self::BRAND_TIE_UP_DOCUMENT             => "Tie-up with brands for gift Vouchers/Paper",
        self::FDA_LICENSE                       => "FDA license",
        self::FSSAI_LICENSE                     => "FSSAI license",
        self::MERCHANT_SERVICE_ARGUMENT         => "Merchant Service Agreement (MSA)",
        self::LEGAL_OPINION_DOCUMENT            => "Legal Opinion from Legal firm",
        self::UNDERTAKING_DOCUMENT              => "Undertaking document",
        self::MANUFACTURING_LICENSE             => "Manufacturing License",
        self::SLA_DOCUMENT                      => "Service level Agreement/tie-up document",
        self::RERA_LICENSE                      => "RERA License",
        self::COPYWRITE_LICENSE                 => "Copywrite License",
        self::TRADE_LICENSE                     => "Trade license",
        self::IATO_LICENSE                      => "IATO license",
        self::BBPS_DOCUMENT                     => "BBPS document",
        self::MSO_DOCUMENT                      => "MSO/ Local cable opertor",
        self::GOVT_AUTHORISATION_LETTER         => "Govt authorisation Letter",
        self::CPV_REPORT                        => "CPV report",
        self::BOARD_RESOLUTION_LETTER           => "Board Resolution Letter"
    ];

    const PROOF_TYPES = [
        self::INDIVIDUAL_PROOF_OF_ADDRESS,
        self::INDIVIDUAL_PROOF_OF_IDENTIFICATION,
        self::BUSINESS_PROOF_OF_IDENTIFICATION,
        self::ADDITIONAL_DOCUMENTS,
    ];

    const PROOF_TYPE_ENTITY_MAPPING = [
        self::INDIVIDUAL_PROOF_OF_ADDRESS        => E::STAKEHOLDER,
        self::INDIVIDUAL_PROOF_OF_IDENTIFICATION => E::STAKEHOLDER,
        self::BUSINESS_PROOF_OF_IDENTIFICATION   => E::MERCHANT,
        self::ADDITIONAL_DOCUMENTS               => E::MERCHANT,
    ];

    const BANK_PROOF_DOCUMENTS = [
        self::CANCELLED_CHEQUE,
        self::BANK_STATEMENT,
    ];

    const VALID_DOCUMENTS = [
        self::SEBI_REGISTRATION_CERTIFICATE,
        self::IRDAI_REGISTRATION_CERTIFICATE,
        self::FFMC_LICENSE,
        self::NBFC_REGISTRATION_CERTIFICATE,
        self::AMFI_CERTIFICATE,

        self::SLA_SEBI_REGISTRATION_CERTIFICATE,
        self::SLA_IRDAI_REGISTRATION_CERTIFICATE,
        self::SLA_FFMC_LICENSE,
        self::SLA_NBFC_REGISTRATION_CERTIFICATE,
        self::SLA_AMFI_CERTIFICATE,
        self::SLA_IATA_CERTIFICATE,

        self::AFFILIATION_CERTIFICATE,
        self::IATA_CERTIFICATE,

        self::PPI_LICENSE,
        self::DRIVER_LICENSE_BACK,
        self::DRIVER_LICENSE_FRONT,
        self::AADHAR_FRONT,
        self::AADHAR_BACK,
        self::AADHAR_XML,
        self::AADHAR_ZIP,

        self::PASSPORT_FRONT,
        self::PASSPORT_BACK,
        self::VOTER_ID_FRONT,
        self::VOTER_ID_BACK,
        self::CANCELLED_CHEQUE,
        self::BANK_VERIFICATION_LETTER,
        self::BUSINESS_PROOF_URL,
        self::BUSINESS_OPERATION_PROOF_URL,
        self::BUSINESS_PAN_URL,
        self::ADDRESS_PROOF_URL,
        self::PROMOTER_PROOF_URL,
        self::PROMOTER_PAN_URL,
        self::PROMOTER_ADDRESS_URL,
        self::FORM_12A_URL,
        self::FORM_80G_URL,
        self::MEMORANDUM_OF_ASSOCIATION,
        self::ARTICLE_OF_ASSOCIATION,
        self::BOARD_RESOLUTION,
        self::PERSONAL_PAN,

        self::SHOP_ESTABLISHMENT_CERTIFICATE,
        self::GST_CERTIFICATE,
        self::MSME_CERTIFICATE,
        self::BANK_STATEMENT,

        self::FIRS_FILE,
        self::FIRS_ZIP,

        self::WEBSITE_SCREENSHOT,
        self::GAMING_ADDENDUM,
        self::LEGAL_OPINION,
        self::BUSINESS_CORRESPONDENT,

        self::FIRS_ICICI_FILE,
        self::FIRS_ICICI_ZIP,

        self::FIRS_FIRSTDATA_FILE,
        self::FIRS_FIRSTDATA_SUM_FILE,

        self::EMERCHANTPAY_GST_CERTIFICATE,
        self::EMERCHANTPAY_PROOF_OF_OWNERSHIP,
        self::EMERCHANTPAY_AADHAAR,
        self::EMERCHANTPAY_PAN,
        self::EMERCHANTPAY_PASSPORT,

        self::CANCELLED_CHEQUE_VIDEO,
        self::OTHER,

        self::FSSAI_CERTIFICATE,
        self::AYUSH_CERTIFICATE,
        self::FFMC_LICENSE,
        self::BIS_CERTIFICATE,
        self::IRDA_CERTIFICATE,
        self::FDA_CERTIFICATE,
        self::DOT_CERTIFICATE,
        self::TRAI_CERTIFICATE,
        self::RBI_CERTIFICATE,
        self::SLA_DEALERSHIP_AGREEMENT,
        self::DGCA_CERTIFICATE,
        self::NATIONAL_HOUSING_BANK_CERTIFICATE,
        self::DEALERSHIP_RIGHTS_CERTIFCATE,
        self::PCI_DSS_CERTIFICATE,
        self::GII_CERTIFICATE,
        self::PHARMACY_DRUG_LICENSE,
        self::FORM_20_20b_21_21b,
        self::INVOICE,
        self::RESELLER_AGREEMENT,
        self::LIQUOR_LICENSE,
        self::FORM_8A,
        self::FORM_10AC,
        self::IRCTC_AGENT_AGREEMENT,
        self::UNDERTAKING,
        self::EPF_SCHEME_CERTIFICATE,
        self::PROOF_OF_PROFESSION,
        self::GIA_CERTIFICATE,
        self::PM_WANI_CERTIFICATE,
        self::PESO_LICENSE,
        self::DOMAIN_OWNERSHIP_DOCUMENT,
        self::IEC_LICENSE,
        self::BUSINESS_CORRESPONDENT_DOCUMENT,
        self::MMTC_PAMP_LICENSE,
        self::SAFEGOLD_PARTNERSHIP_DOCUMENT,
        self::BRAND_TIE_UP_DOCUMENT,
        self::FDA_LICENSE,
        self::FSSAI_LICENSE,
        self::MERCHANT_SERVICE_ARGUMENT,
        self::LEGAL_OPINION_DOCUMENT,
        self::UNDERTAKING_DOCUMENT,
        self::MANUFACTURING_LICENSE,
        self::SLA_DOCUMENT,
        self::RERA_LICENSE,
        self::COPYWRITE_LICENSE,
        self::TRADE_LICENSE,
        self::IATO_LICENSE,
        self::BBPS_DOCUMENT,
        self::MSO_DOCUMENT,
        self::GOVT_AUTHORISATION_LETTER,
        self::BOARD_RESOLUTION_LETTER,
        self::CPV_REPORT
    ];

    const DOCUMENT_TYPE_VALIDATIONS = [
        self::CANCELLED_CHEQUE_VIDEO => Format::VALID_VIDEO_EXTENSIONS
    ];

    const DOCUMENT_TYPE_TO_PROOF_TYPE_MAPPING = [
        self::CANCELLED_CHEQUE_VIDEO            => self::ADDITIONAL_DOCUMENTS,
        self::FSSAI_CERTIFICATE                 => self::ADDITIONAL_DOCUMENTS,
        self::AYUSH_CERTIFICATE                 => self::ADDITIONAL_DOCUMENTS,
        self::BIS_CERTIFICATE                   => self::ADDITIONAL_DOCUMENTS,
        self::IRDA_CERTIFICATE                  => self::ADDITIONAL_DOCUMENTS,
        self::FDA_CERTIFICATE                   => self::ADDITIONAL_DOCUMENTS,
        self::DOT_CERTIFICATE                   => self::ADDITIONAL_DOCUMENTS,
        self::TRAI_CERTIFICATE                  => self::ADDITIONAL_DOCUMENTS,
        self::RBI_CERTIFICATE                   => self::ADDITIONAL_DOCUMENTS,
        self::DGCA_CERTIFICATE                  => self::ADDITIONAL_DOCUMENTS,
        self::NATIONAL_HOUSING_BANK_CERTIFICATE => self::ADDITIONAL_DOCUMENTS,
        self::DEALERSHIP_RIGHTS_CERTIFCATE      => self::ADDITIONAL_DOCUMENTS,
        self::PCI_DSS_CERTIFICATE               => self::ADDITIONAL_DOCUMENTS,
        self::GII_CERTIFICATE                   => self::ADDITIONAL_DOCUMENTS,
        self::PHARMACY_DRUG_LICENSE             => self::ADDITIONAL_DOCUMENTS,
        self::SLA_DEALERSHIP_AGREEMENT          => self::ADDITIONAL_DOCUMENTS,
        self::FORM_20_20b_21_21b                => self::ADDITIONAL_DOCUMENTS,
        self::INVOICE                           => self::ADDITIONAL_DOCUMENTS,
        self::RESELLER_AGREEMENT                => self::ADDITIONAL_DOCUMENTS,
        self::LIQUOR_LICENSE                    => self::ADDITIONAL_DOCUMENTS,
        self::FORM_8A                           => self::ADDITIONAL_DOCUMENTS,
        self::FORM_10AC                         => self::ADDITIONAL_DOCUMENTS,
        self::IRCTC_AGENT_AGREEMENT             => self::ADDITIONAL_DOCUMENTS,
        self::UNDERTAKING                       => self::ADDITIONAL_DOCUMENTS,
        self::EPF_SCHEME_CERTIFICATE            => self::ADDITIONAL_DOCUMENTS,
        self::PROOF_OF_PROFESSION               => self::ADDITIONAL_DOCUMENTS,
        self::GIA_CERTIFICATE                   => self::ADDITIONAL_DOCUMENTS,
        self::PM_WANI_CERTIFICATE               => self::ADDITIONAL_DOCUMENTS,
        self::PESO_LICENSE                      => self::ADDITIONAL_DOCUMENTS,
        self::DOMAIN_OWNERSHIP_DOCUMENT         => self::ADDITIONAL_DOCUMENTS,
        self::IEC_LICENSE                       => self::ADDITIONAL_DOCUMENTS,
        self::BUSINESS_CORRESPONDENT_DOCUMENT   => self::ADDITIONAL_DOCUMENTS,
        self::MMTC_PAMP_LICENSE                 => self::ADDITIONAL_DOCUMENTS,
        self::SAFEGOLD_PARTNERSHIP_DOCUMENT     => self::ADDITIONAL_DOCUMENTS,
        self::BRAND_TIE_UP_DOCUMENT             => self::ADDITIONAL_DOCUMENTS,
        self::FDA_LICENSE                       => self::ADDITIONAL_DOCUMENTS,
        self::FSSAI_LICENSE                     => self::ADDITIONAL_DOCUMENTS,
        self::MERCHANT_SERVICE_ARGUMENT         => self::ADDITIONAL_DOCUMENTS,
        self::LEGAL_OPINION_DOCUMENT            => self::ADDITIONAL_DOCUMENTS,
        self::UNDERTAKING_DOCUMENT              => self::ADDITIONAL_DOCUMENTS,
        self::MANUFACTURING_LICENSE             => self::ADDITIONAL_DOCUMENTS,
        self::SLA_DOCUMENT                      => self::ADDITIONAL_DOCUMENTS,
        self::RERA_LICENSE                      => self::ADDITIONAL_DOCUMENTS,
        self::COPYWRITE_LICENSE                 => self::ADDITIONAL_DOCUMENTS,
        self::TRADE_LICENSE                     => self::ADDITIONAL_DOCUMENTS,
        self::IATO_LICENSE                      => self::ADDITIONAL_DOCUMENTS,
        self::BBPS_DOCUMENT                     => self::ADDITIONAL_DOCUMENTS,
        self::MSO_DOCUMENT                      => self::ADDITIONAL_DOCUMENTS,
        self::GOVT_AUTHORISATION_LETTER         => self::ADDITIONAL_DOCUMENTS,
        self::SEBI_REGISTRATION_CERTIFICATE     => self::ADDITIONAL_DOCUMENTS,
        self::IRDAI_REGISTRATION_CERTIFICATE    => self::ADDITIONAL_DOCUMENTS,
        self::FFMC_LICENSE                      => self::ADDITIONAL_DOCUMENTS,
        self::NBFC_REGISTRATION_CERTIFICATE     => self::ADDITIONAL_DOCUMENTS,
        self::AMFI_CERTIFICATE                  => self::ADDITIONAL_DOCUMENTS,
        self::BOARD_RESOLUTION_LETTER           => self::ADDITIONAL_DOCUMENTS,

        self::SLA_SEBI_REGISTRATION_CERTIFICATE  => self::ADDITIONAL_DOCUMENTS,
        self::SLA_IRDAI_REGISTRATION_CERTIFICATE => self::ADDITIONAL_DOCUMENTS,
        self::SLA_FFMC_LICENSE                   => self::ADDITIONAL_DOCUMENTS,
        self::SLA_NBFC_REGISTRATION_CERTIFICATE  => self::ADDITIONAL_DOCUMENTS,
        self::SLA_AMFI_CERTIFICATE               => self::ADDITIONAL_DOCUMENTS,
        self::SLA_IATA_CERTIFICATE               => self::ADDITIONAL_DOCUMENTS,

        self::AFFILIATION_CERTIFICATE => self::ADDITIONAL_DOCUMENTS,
        self::IATA_CERTIFICATE        => self::ADDITIONAL_DOCUMENTS,

        self::PPI_LICENSE                  => self::ADDITIONAL_DOCUMENTS,
        self::DRIVER_LICENSE_BACK          => self::INDIVIDUAL_PROOF_OF_ADDRESS,
        self::DRIVER_LICENSE_FRONT         => self::INDIVIDUAL_PROOF_OF_ADDRESS,
        self::AADHAR_FRONT                 => self::INDIVIDUAL_PROOF_OF_ADDRESS,
        self::AADHAR_BACK                  => self::INDIVIDUAL_PROOF_OF_ADDRESS,
        self::AADHAR_ZIP                   => self::INDIVIDUAL_PROOF_OF_ADDRESS,
        self::AADHAR_XML                   => self::INDIVIDUAL_PROOF_OF_ADDRESS,
        self::PASSPORT_FRONT               => self::INDIVIDUAL_PROOF_OF_ADDRESS,
        self::PASSPORT_BACK                => self::INDIVIDUAL_PROOF_OF_ADDRESS,
        self::VOTER_ID_FRONT               => self::INDIVIDUAL_PROOF_OF_ADDRESS,
        self::VOTER_ID_BACK                => self::INDIVIDUAL_PROOF_OF_ADDRESS,
        self::CANCELLED_CHEQUE             => self::ADDITIONAL_DOCUMENTS,
        self::BANK_VERIFICATION_LETTER     => self::ADDITIONAL_DOCUMENTS,
        self::BUSINESS_PROOF_URL           => self::BUSINESS_PROOF_OF_IDENTIFICATION,
        self::BUSINESS_OPERATION_PROOF_URL => self::ADDITIONAL_DOCUMENTS,
        self::BUSINESS_PAN_URL             => self::BUSINESS_PROOF_OF_IDENTIFICATION,
        self::ADDRESS_PROOF_URL            => self::ADDITIONAL_DOCUMENTS,
        self::PROMOTER_PROOF_URL           => self::ADDITIONAL_DOCUMENTS,
        self::PROMOTER_PAN_URL             => self::INDIVIDUAL_PROOF_OF_IDENTIFICATION,
        self::PROMOTER_ADDRESS_URL         => self::INDIVIDUAL_PROOF_OF_ADDRESS,
        self::FORM_12A_URL                 => self::ADDITIONAL_DOCUMENTS,
        self::FORM_80G_URL                 => self::ADDITIONAL_DOCUMENTS,
        self::MEMORANDUM_OF_ASSOCIATION    => self::ADDITIONAL_DOCUMENTS,
        self::ARTICLE_OF_ASSOCIATION       => self::ADDITIONAL_DOCUMENTS,
        self::BOARD_RESOLUTION             => self::ADDITIONAL_DOCUMENTS,
        self::PERSONAL_PAN                 => self::INDIVIDUAL_PROOF_OF_IDENTIFICATION,

        self::SHOP_ESTABLISHMENT_CERTIFICATE => self::BUSINESS_PROOF_OF_IDENTIFICATION,
        self::GST_CERTIFICATE                => self::BUSINESS_PROOF_OF_IDENTIFICATION,
        self::MSME_CERTIFICATE               => self::BUSINESS_PROOF_OF_IDENTIFICATION,
        self::BANK_STATEMENT                 => self::ADDITIONAL_DOCUMENTS,

        self::FIRS_FILE => self::ADDITIONAL_DOCUMENTS,
        self::FIRS_ZIP  => self::ADDITIONAL_DOCUMENTS,

        self::OTHER                  => self::ADDITIONAL_DOCUMENTS,
        self::WEBSITE_SCREENSHOT     => self::ADDITIONAL_DOCUMENTS,
        self::BUSINESS_CORRESPONDENT => self::ADDITIONAL_DOCUMENTS,
        self::LEGAL_OPINION          => self::ADDITIONAL_DOCUMENTS,
        self::GAMING_ADDENDUM        => self::ADDITIONAL_DOCUMENTS,

        self::FIRS_ICICI_FILE => self::ADDITIONAL_DOCUMENTS,
        self::FIRS_ICICI_ZIP  => self::ADDITIONAL_DOCUMENTS,

        self::FIRS_FIRSTDATA_FILE => self::ADDITIONAL_DOCUMENTS,
        self::FIRS_FIRSTDATA_SUM_FILE => self::ADDITIONAL_DOCUMENTS,

        self::EMERCHANTPAY_GST_CERTIFICATE    => self::ADDITIONAL_DOCUMENTS,
        self::EMERCHANTPAY_PROOF_OF_OWNERSHIP => self::ADDITIONAL_DOCUMENTS,
        self::EMERCHANTPAY_AADHAAR            => self::ADDITIONAL_DOCUMENTS,
        self::EMERCHANTPAY_PAN                => self::ADDITIONAL_DOCUMENTS,
        self::EMERCHANTPAY_PASSPORT           => self::ADDITIONAL_DOCUMENTS,
        self::CPV_REPORT                      => self::ADDITIONAL_DOCUMENTS,
    ];

    /**
     * Following documents needs to perform for OCR
     *
     * @var array
     */
    protected static $documentsToPerformOcr    = [
        self::AADHAR_FRONT,
        self::AADHAR_BACK,
        self::PASSPORT_FRONT,
        self::VOTER_ID_FRONT,
        self::MSME_CERTIFICATE,
        self::SHOP_ESTABLISHMENT_CERTIFICATE,
        self::GST_CERTIFICATE,
        self::BUSINESS_PROOF_URL
    ];

    protected static $poaDocuments             = [
        self::AADHAR_FRONT,
        self::PASSPORT_FRONT,
        self::VOTER_ID_FRONT,
    ];

    protected static $jointValidationDocuments = [
        self::AADHAR_FRONT,
        self::AADHAR_BACK,
    ];

    public static function isValid($value)
    {
        return (in_array($value, self::VALID_DOCUMENTS) === true);
    }

    public static function getRelatedDocumentType($documentType)
    {
        if ($documentType === self::AADHAR_FRONT)
        {
            return self::AADHAR_BACK;
        }

        return self::AADHAR_FRONT;
    }

    public static function isJointValidationDocumentType($documentType): bool
    {
        if (empty($documentType))
        {
            return false;
        }

        return in_array($documentType, self::$jointValidationDocuments, true);
    }

    public static function isDocumentTypeToPerformOcr($documentType): bool
    {
        if (empty($documentType))
        {
            return false;
        }

        return in_array($documentType, self::$documentsToPerformOcr, true);
    }

    public static function isPoaDocument($documentType): bool
    {
        if (empty($documentType))
        {
            return false;
        }

        return in_array($documentType, self::$poaDocuments, true);
    }

    public static function isValidProofType($value)
    {
        return (in_array($value, self::PROOF_TYPES) === true);
    }

    public static function getValidDocumentForEntity(string $entityType)
    {
        $proofMappings = [];

        foreach (self::DOCUMENT_TYPE_TO_PROOF_TYPE_MAPPING as $documentType => $proofType)
        {
            $proofMappings[$proofType][] = $documentType;
        }

        $proofs = [];
        foreach (self::PROOF_TYPE_ENTITY_MAPPING as $proofType => $entity)
        {
            if ($entity === $entityType)
            {
                $proofs[$proofType] = $entity;
            }
        }

        $validMappings = array_intersect_key($proofMappings, $proofs);

        return array_reduce($validMappings, function($array, $item) {
            return array_merge($array, $item);
        }, []);

    }
}
