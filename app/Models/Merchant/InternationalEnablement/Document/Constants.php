<?php

namespace RZP\Models\Merchant\InternationalEnablement\Document;

use RZP\Models\Merchant\Detail\BusinessCategoriesV2\BusinessCategory as Category;
use RZP\Models\Merchant\Detail\BusinessCategoriesV2\BusinessSubcategory as Subcategory;

class Constants
{
    const FIRC                                      = 'firc';
    const IE_CODE                                   = 'ie_code';
    const INVOICES                                  = 'invoices';
    const BANK_STATEMENT_INWARD_REMITTANCE          = 'bank_statement_inward_remittance';
    const CURRENT_PAYMENT_PARTNER_SETTLEMENT_RECORD = 'current_payment_partner_settlement_record';
    const IATA                                      = 'iata';
    const FCRA                                      = 'fcra';
    const FSSAI                                     = 'fssai';
    const NBFC                                      = 'nbfc';
    const AYUSH_CERTIFICATE                         = 'ayush_certificate';
    const SEBI_CERTIFICATE                          = 'sebi_certificate';
    const FEMA_FFMA_CERTIFICATE                     = 'fema_ffma_certificate';
    const HALLMARK_916_BIS                          = 'hallmark_916_bis';
    const HALLMARK_925                              = 'hallmark_925';
    const AMFI                                      = 'amfi';
    const TRAI                                      = 'trai';
    const GII                                       = 'gii';
    const RERA                                      = 'rera';
    const GAMING_ADDENDUM_CERTIFICATE               = 'gaming_addendum_certificate';
    const HALLMARK_GII                              = 'hallmark_gii';

    const OTHERS = 'others';

    const MAX_DOCUMENTS_PER_TYPE = 3;

    const MANDATORY_DOCUMENT_TYPES = [
        self::BANK_STATEMENT_INWARD_REMITTANCE,
        self::CURRENT_PAYMENT_PARTNER_SETTLEMENT_RECORD,
    ];

    const HALLMARK_GII_DOCUMENTS_TYPES = [
        self::HALLMARK_916_BIS,
        self::HALLMARK_925,
        self::GII,
    ];

    const DOCUMENT_TYPES = [
        self::FIRC,
        self::IE_CODE,
        self::INVOICES,
        self::BANK_STATEMENT_INWARD_REMITTANCE,
        self::CURRENT_PAYMENT_PARTNER_SETTLEMENT_RECORD,
        self::OTHERS,
    ];

    const DOCUMENT_TYPE_VALIDATOR_CSV =
        self::FIRC . ',' .
        self::IE_CODE . ',' .
        self::INVOICES . ',' .
        self::BANK_STATEMENT_INWARD_REMITTANCE . ',' .
        self::CURRENT_PAYMENT_PARTNER_SETTLEMENT_RECORD . ',' .
        self::IATA . ',' .
        self::FCRA . ',' .
        self::FSSAI . ',' .
        self::NBFC . ',' .
        self::AYUSH_CERTIFICATE . ',' .
        self::SEBI_CERTIFICATE . ',' .
        self::FEMA_FFMA_CERTIFICATE . ',' .
        self::HALLMARK_916_BIS . ',' .
        self::HALLMARK_925 . ',' .
        self::AMFI . ',' .
        self::TRAI . ',' .
        self::GII . ',' .
        self::RERA . ',' .
        self::GAMING_ADDENDUM_CERTIFICATE . ',' .
        self::OTHERS;

    const BUSINESS_CATEGORY_SUBCATEGORY_DOCUMENT_TYPE_MAP = [
        Category::TOURS_AND_TRAVEL => [
            Subcategory::AVIATION                           => self::IATA,
        ],
        Category::NOT_FOR_PROFIT => [
            Subcategory::CHARITY                            => self::FCRA,
        ],
        Category::FOOD => [
            Subcategory::FOOD_COURT                         => self::FSSAI,
            Subcategory::ONLINE_FOOD_ORDERING               => self::FSSAI,
            Subcategory::RESTAURANT                         => self::FSSAI,
            Subcategory::CATERING                           => self::FSSAI,
            Subcategory::ALCOHOL                            => self::FSSAI,
            Subcategory::RESTAURANT_SEARCH_AND_BOOKING      => self::FSSAI,
        ],
        Category::FINANCIAL_SERVICES => [
            Subcategory::NBFC                               => self::NBFC,
            Subcategory::LENDING                            => self::NBFC,
            Subcategory::TRADING                            => self::SEBI_CERTIFICATE,
            Subcategory::FINANCIAL_ADVISOR                  => self::SEBI_CERTIFICATE,
            Subcategory::SECURITIES                         => self::SEBI_CERTIFICATE,
            Subcategory::COMMODITIES                        => self::SEBI_CERTIFICATE,
            Subcategory::FOREX                              => self::FEMA_FFMA_CERTIFICATE,
            Subcategory::MUTUAL_FUND                        => self::AMFI,
        ],
        Category::HEALTHCARE => [
          Subcategory::PHARMACY                             => self::AYUSH_CERTIFICATE,
          Subcategory::HEALTH_PRODUCTS                      => self::AYUSH_CERTIFICATE,
          Subcategory::HEALTHCARE_MARKETPLACE               => self::AYUSH_CERTIFICATE,
          Subcategory::MEDICAL_EQUIPMENT_AND_SUPPLY_STORES  => self::AYUSH_CERTIFICATE
        ],
        Category::ECOMMERCE => [
            Subcategory::JEWELLERY_AND_WATCH_STORES         => self::HALLMARK_GII,
        ],
        Category::UTILITIES => [
            Subcategory::INTERNET_PROVIDER                  => self::TRAI,
            Subcategory::BROADBAND                          => self::TRAI,
        ],
        Category::HOUSING => [
            Subcategory::FACILITY_MANAGEMENT                => self::RERA,
            Subcategory::COWORKING                          => self::RERA,
            Subcategory::SPACE_RENTAL                       => self::RERA,
        ],
        Category::GAMING => [
            Subcategory::GAME_DEVELOPER                     => self::GAMING_ADDENDUM_CERTIFICATE,
            Subcategory::ESPORTS                            => self::GAMING_ADDENDUM_CERTIFICATE,
            Subcategory::ONLINE_CASINO                      => self::GAMING_ADDENDUM_CERTIFICATE,
            Subcategory::FANTASY_SPORTS                     => self::GAMING_ADDENDUM_CERTIFICATE,
            Subcategory::GAMING_MARKETPLACE                 => self::GAMING_ADDENDUM_CERTIFICATE,
        ],
    ];

    public static function isMandatoryDocumentType(string $documentType): bool
    {
        return (in_array($documentType, self::MANDATORY_DOCUMENT_TYPES) === true);
    }

    public static function getValidDocumentTypesCSV(): string
    {
        return implode(',', self::DOCUMENT_TYPES);
    }
}
