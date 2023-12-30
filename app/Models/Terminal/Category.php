<?php

namespace RZP\Models\Terminal;

use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\Card\Network;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;

class Category
{
    const DEFAULT         = 'default';

    const SECURITIES      = 'securities';
    const COMMODITIES     = 'commodities';
    const GROCERY         = 'grocery';
    const ECOMMERCE       = 'ecommerce';
    const EDUCATION       = 'education';
    const GAMING          = 'gaming';
    const GOVERNMENT      = 'government';
    const GOVT_EDUCATION  = 'govt_education';
    const PVT_EDUCATION   = 'pvt_education';
    const UTILITIES       = 'utilities';
    const CORPORATE       = 'corporate';
    const INSURANCE       = 'insurance';
    const HOUSING         = 'housing';
    const MUTUAL_FUNDS    = 'mutual_funds';
    const TRAVEL_AGENCY   = 'travel_agency';
    const RETAIL_SERVICES = 'retail_services';
    const PHARMA          = 'pharma';
    const LENDING         = 'lending';
    const CRYPTOCURRENCY  = 'cryptocurrency';
    const FINANCE         = 'finance';
    const FOREX           = 'forex';
    const HOSPITALITY     = 'hospitality';
    const LOGISTICS       = 'logistics';
    const OTHERS          = 'others';

    // newly added categories
    const FINANCIAL_SERVICES      = 'financial_services';
    const FOOD_AND_BEVERAGE       = 'food_and_beverage';
    const HEALTHCARE              = 'healthcare';
    const IT_AND_SOFTWARE         = 'it_and_software';
    const MEDIA_AND_ENTERTAINMENT = 'media_and_entertainment';
    const MONEY_TRANSFER          = 'money_transfer';
    const NOT_FOR_PROFIT          = 'not_for_profit';
    const REAL_ESTATE             = 'real_estate';
    const RECHARGES               = 'recharges';
    const SERVICES                = 'services';
    const SOCIAL                  = 'social';
    const TRANSPORT               = 'transport';
    const FUEL_GOVERNMENT         = 'fuel_government';
    const COMMUNICATION           = 'communication';
    const AUTO_RENTAL             = 'auto_rental';
    const FUEL_NON_GOVERNMENT     = 'fuel_nongovernment';
    const FUEL_HPCL               = 'fuel_hpcl';

    // New category 2 addition as a part of the project reduce other category selection
    const  MONEY_ORDER_TRAVELLER_CHEQUE                    = 'money_order_traveller_cheque';
    const  LOAN_DEBT_REPAYMENT                             = 'loan_debt_repayment';
    const  MANUAL_CASH_DISBURSEMENT                        = 'manual_cash_disbursement';
    const  EMPLOYMENT_AGENCIES_TEMPORARY_HELP_SERVICES     = 'employment_agencies_temporary_help_services';
    const  FINANCIAL_INSTITUTIONS_MERCHANDISE_AND_SERVICES = 'financial_institutions_merchandise_and_services';
    const  HEALTH_BEAUTY_SPA                               = 'health_beauty_spa';
    const  ELECTRICAL_PURPOSE_DISTILLED_WATER              = 'electrical_purpose_distilled_water';
    const  AUTOMOBILE_GARAGE_PARKING_SPACE                 = 'automobile_garage_parking_space';
    const  TECH_CONSULTING_AND_OUTSOURCING                 = 'tech_consulting_and_outsourcing';
    const  BPO_KPO_LPO_RPO                                 = 'bpo_kpo_lpo_rpo';
    const  ASTROLOGER                                      = 'astrologer';
    const  TIMBER_STORES                                   = 'timber_stores';
    const  FERTILIZER_DEALERS                              = 'fertilizer_dealers';
    const  PESTICIDES_OR_INSECTICIDES                      = 'pesticides_or_insecticides';
    const  SEEDS                                           = 'seeds';
    const  ART_DEALERS_GALLERIES                           = 'art_dealers_galleries';
    const  ANTIQUE_REPRODUCTION_STORES                     = 'antique_reproduction_stores';
    const  REAL_ESTATE_AGENTS                              = 'real_estate_agents';
    const  CHARITY_CROWDFUNDING                            = 'charity_crowdfunding';
    const  AUTOMOTIVE_BODY_REPAIRS                         = 'automotive_body_repairs';
    const  TATTOO_BODY_PIERCING_PET_GROOMING               = 'tattoo_body_piercing_pet_grooming';
    const  CLEANING_MAINTENANCE_JANITORIAL_SERVICES        = 'cleaning_maintenance_janitorial_services';
    const  PLUMBING_CONTRACTOR                             = 'plumbing_contractor';
    const  AIR_CONDITIONING_HEATING_CONTRACTOR             = 'air_conditioning_heating_contractor';
    const  USED_AUTOMOBILE_AND_TRUCK_DEALERS               = 'used_automobile_and_truck_dealers';
    const  INTERMEDIARIES_FACILITATING_BUYING              = 'intermediaries_facilitating_buying';
    const  WINE_PRODUCERS                                  = 'wine_producers';
    const  CHAMPAGNE_PRODUCERS                             = 'champagne_producers';
    const  ALCOHOLIC_BEVERAGE_WHOLESALERS                  = 'alcoholic_beverage_wholesalers';
    const  DIGITAL_GOLD_PURCHASE                           = 'digital_gold_purchase';
    const  CREDIT_CARD_BILL_PAYMENTS                       = 'credit_card_bill_payments';
    const  LIC                                             = 'lic';
    const  DEBIT_COLLECTION_CHARGES                        = 'debit_collection_charges';
    const  BAIL_AND_BOND_PAYMENTS                          = 'bail_and_bond_payments';
    const  WALLET_TOP_UP                                   = 'wallet_top_up';
    const  DIRECT_MARKETING_CATALOGUE_RETAIL_MERCHANTS     = 'direct_marketing_catalogue_retail_merchants';
    const  ELECTRICAL_VEHICLES_CHARGING_STATIONS_SERVICES  = 'electrical_vehicles_charging_stations_services';
    const  STATIONERY_SUPPLIES                             = 'stationery_supplies';
    const  AUTOMOBILE_AND_TRUCK_SERVICE_SHOP               = 'automobile_and_truck_service_shop';


    /**
     * Categories mapped to invalid will not find an
     * appropriate category to override. Only the category
     * allowed by default will be chosen
     */
    const INVALID = 'invalid';

    /**
     * The list of all possible categories that can be chosen
     * Broking requires an incompatible flag to be added to
     * prevent incompatible methods from choosing the default
     */
    const CATEGORIES_ALL = [
        self::SECURITIES,
        self::COMMODITIES,
        self::GROCERY,
        self::ECOMMERCE,
        self::GAMING,
        self::GOVERNMENT,
        self::GOVT_EDUCATION,
        self::PVT_EDUCATION,
        self::UTILITIES,
        self::CORPORATE,
        self::INSURANCE,
        self::HOUSING,
        self::MUTUAL_FUNDS,
        self::TRAVEL_AGENCY,
        self::PHARMA,
        self::LENDING,
        self::CRYPTOCURRENCY,
        self::FOREX,
        self::HOSPITALITY,
        self::LOGISTICS,
        self::OTHERS,
        self::FINANCIAL_SERVICES,
        self::FOOD_AND_BEVERAGE,
        self::HEALTHCARE,
        self::IT_AND_SOFTWARE,
        self::MEDIA_AND_ENTERTAINMENT,
        self::MONEY_TRANSFER,
        self::NOT_FOR_PROFIT,
        self::REAL_ESTATE,
        self::RECHARGES,
        self::SERVICES,
        self::SOCIAL,
        self::TRANSPORT,
        self::STATIONERY_SUPPLIES,
        self::TIMBER_STORES,
        self::FERTILIZER_DEALERS,
        self::PESTICIDES_OR_INSECTICIDES,
        self::SEEDS,
        self::ART_DEALERS_GALLERIES,
        self::ANTIQUE_REPRODUCTION_STORES,
        self::MONEY_ORDER_TRAVELLER_CHEQUE,
        self::LOAN_DEBT_REPAYMENT,
        self::MANUAL_CASH_DISBURSEMENT,
        self::EMPLOYMENT_AGENCIES_TEMPORARY_HELP_SERVICES,
        self::FINANCIAL_INSTITUTIONS_MERCHANDISE_AND_SERVICES,
        self::DIGITAL_GOLD_PURCHASE,
        self::CREDIT_CARD_BILL_PAYMENTS,
        self::LIC,
        self::DEBIT_COLLECTION_CHARGES,
        self::BAIL_AND_BOND_PAYMENTS,
        self::WALLET_TOP_UP,
        self::HEALTH_BEAUTY_SPA,
        self::ELECTRICAL_PURPOSE_DISTILLED_WATER,
        self::REAL_ESTATE_AGENTS,
        self::TECH_CONSULTING_AND_OUTSOURCING,
        self::BPO_KPO_LPO_RPO,
        self::ASTROLOGER,
        self::CHARITY_CROWDFUNDING,
        self::AUTOMOTIVE_BODY_REPAIRS,
        self::TATTOO_BODY_PIERCING_PET_GROOMING,
        self::CLEANING_MAINTENANCE_JANITORIAL_SERVICES,
        self::AUTOMOBILE_AND_TRUCK_SERVICE_SHOP,
        self::PLUMBING_CONTRACTOR,
        self::AIR_CONDITIONING_HEATING_CONTRACTOR,
        self::USED_AUTOMOBILE_AND_TRUCK_DEALERS,
        self::INTERMEDIARIES_FACILITATING_BUYING,
        self::WINE_PRODUCERS,
        self::CHAMPAGNE_PRODUCERS,
        self::ALCOHOLIC_BEVERAGE_WHOLESALERS,
        self::DIRECT_MARKETING_CATALOGUE_RETAIL_MERCHANTS,
        self::ELECTRICAL_VEHICLES_CHARGING_STATIONS_SERVICES,
        self::AUTOMOBILE_GARAGE_PARKING_SPACE
    ];


    /**
     * INCOMPATIBLE categories will not allow terminals that
     * do not match the corresponding categories.
     * The list below is of the category2 on merchant entity.
     * The terminals marked null or default will be filtered
     * out.
     */
    const TPV = [
        self::SECURITIES,
        self::COMMODITIES,
    ];


    /**
     * By default Check for the name that is mentioned as is.
     * On Adding a new Key, a default should mandatorily be present
     * If it is renamed, then the new name that is mentioned will be
     * used to check for a network category
     *
     * Keys are Merchant categories, values are Network categories
     */

    // in case of any changes in gateway config, please contact smart routing team
    // changes done here won't be reflected in routing
    const CATEGORIES = [
        Method::NETBANKING => [
            self::DEFAULT => self::ECOMMERCE,
            Gateway::NETBANKING_KOTAK => [
                // In Kotak, a utilities terminal needs to be added
                // which will be used to accept lending as well.
                self::DEFAULT   => self::ECOMMERCE,
                self::UTILITIES => self::UTILITIES,
                self::LENDING   => self::UTILITIES,
                // self::FOREX     => self::FINANCE,
            ],
            Gateway::BILLDESK => [
                self::DEFAULT => self::ECOMMERCE,
                self::FOREX   => self::HOUSING,
            ],
        ],
        Method::CARD => [
            self::DEFAULT        => self::ECOMMERCE,
            self::PHARMA         => self::ECOMMERCE,
            Gateway::AMEX     => [
                self::DEFAULT                => self::RETAIL_SERVICES,
                self::GROCERY                => 'sup_hypermrkt_deptstore',
                self::ECOMMERCE              => self::RETAIL_SERVICES,
                self::GOVT_EDUCATION         => self::EDUCATION,
                self::PVT_EDUCATION          => self::EDUCATION,
                self::CORPORATE              => self::INVALID,
                self::INSURANCE              => self::INSURANCE,
                self::HOUSING                => self::HOUSING,
                self::UTILITIES              => self::UTILITIES,
                self::FUEL_GOVERNMENT        => self::FUEL_GOVERNMENT,
                self::COMMUNICATION          => self::COMMUNICATION,
                self::AUTO_RENTAL            => self::AUTO_RENTAL,
                self::FUEL_NON_GOVERNMENT    => self::FUEL_NON_GOVERNMENT,
                self::FUEL_HPCL              => self::FUEL_HPCL,

            ],
        ],
        Method::EMANDATE => [
            self::DEFAULT => self::ECOMMERCE,
        ]
    ];

    public static function isMerchantCategoryValid($category)
    {
        return in_array($category, self::CATEGORIES_ALL, true);
    }

    public static function isNetworkCategoryValid(string $networkCategory, $method, string $gateway): bool
    {
        if ($networkCategory === self::INVALID)
        {
            return false;
        }

        // Get the correct constant for the terminal
        // get the values array and check in array
        $allCategories = array_combine(self::CATEGORIES_ALL, self::CATEGORIES_ALL);

        if (isset(self::CATEGORIES[$method][$gateway]) === true)
        {
            foreach (self::CATEGORIES[$method][$gateway] as $category2 => $networkCategory)
            {
                $allCategories[$category2] = $networkCategory;
            }
        }

        // No need to worry about duplicates. we only need values
        $values = array_values($allCategories);

        return in_array($networkCategory, $values, true);
    }

    public static function getDefaultForMethodAndGateway($method, $gateway)
    {
        return self::getCategoryForMethodAndGateway($method, $gateway, self::DEFAULT);
    }

    public static function getCategoryForMethodAndGateway($method, $gateway, $category2)
    {
        $networkCategory = self::getDefaultNetworkCategory($category2);

        if (isset(self::CATEGORIES[$method][$category2]) === true)
        {
            $networkCategory = self::CATEGORIES[$method][$category2];
        }

        if (isset(self::CATEGORIES[$method][$gateway][$category2]) === true)
        {
            $networkCategory = self::CATEGORIES[$method][$gateway][$category2];
        }

        return $networkCategory;
    }

    public static function getTPVCategories()
    {
        return self::TPV;
    }

    public static function getIncompatibleCategories()
    {
        return self::TPV;
    }

    public static function isMerchantCategoryTPV($category2)
    {
        return in_array($category2, self::TPV, true);
    }

    public static function isMerchantCategoryIncompatible($category)
    {
        $incompatibleCategories = self::getIncompatibleCategories();

        return in_array($category, $incompatibleCategories, true);
    }

    protected static function getDefaultNetworkCategory($category2)
    {
        $networkCategory = null;

        if ($category2 !== self::DEFAULT)
        {
            $networkCategory = $category2;
        }

        return $networkCategory;
    }
}
