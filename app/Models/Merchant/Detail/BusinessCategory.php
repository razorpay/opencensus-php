<?php

namespace RZP\Models\Merchant\Detail;

use RZP\Models\Merchant\Detail\BusinessSubcategory as Sub;

class BusinessCategory
{
    const CODE                    = 'code';
    const DESCRIPTION             = 'description';
    const SUBCATEGORIES           = 'subcategories';

    // Business Category Codes
    const FINANCIAL_SERVICES      = 'financial_services';
    const EDUCATION               = 'education';
    const HEALTHCARE              = 'healthcare';
    const UTILITIES               = 'utilities';
    const GOVERNMENT              = 'government';
    const LOGISTICS               = 'logistics';
    const TOURS_AND_TRAVEL        = 'tours_and_travel';
    const TRANSPORT               = 'transport';
    const ECOMMERCE               = 'ecommerce';
    const FOOD                    = 'food';
    const IT_AND_SOFTWARE         = 'it_and_software';
    const GAMING                  = 'gaming';
    const MEDIA_AND_ENTERTAINMENT = 'media_and_entertainment';
    const SERVICES                = 'services';
    const HOUSING                 = 'housing';
    const NOT_FOR_PROFIT          = 'not_for_profit';
    const SOCIAL                  = 'social';
    const OTHERS                  = 'others';

    //subcategory moved to top level categories
    const  COUPONS                              = 'coupons';
    const  REPAIR_AND_CLEANING                  = 'repair_and_cleaning';
    const  ACCOUNTING                           = 'accounting';
    const  TELECOMMUNICATION_SERVICE            = 'telecommunication_service';
    const  SERVICE_CENTRE                       = 'service_centre';
    const  COWORKING                            = 'coworking';
    const  CAB_HAILING                          = 'cab_hailing';
    const  GROCERY                              = 'grocery';
    const  PAAS                                 = 'paas';
    const  SAAS                                 = 'saas';
    const  WEB_DEVELOPMENT                      = 'web_development';
    const  CHARITY                              = 'charity';
    const  FASHION_AND_LIFESTYLE                = 'fashion_and_lifestyle';
    const  DROP_SHIPPING                        = 'drop_shipping';
    const  CONSULTING_AND_OUTSOURCING           = 'consulting_and_outsourcing';
    const  CATERING                             = 'catering';
    const  HEALTH_COACHING                      = 'health_coaching';
    const  COMPUTER_PROGRAMMING_DATA_PROCESSING = 'computer_programming_data_processing';
    const  UTILITIES_ELECTRIC_GAS_OIL_WATER     = 'utilities_electric_gas_oil_water';


    // Business Category Descriptions
    const DESCRIPTIONS = [
        self::FINANCIAL_SERVICES         => 'Financial Services',
        self::EDUCATION                  => 'Education',
        self::HEALTHCARE                 => 'Healthcare',
        self::UTILITIES                  => 'Utilities-General',
        self::GOVERNMENT                 => 'Government Bodies',
        self::LOGISTICS                  => 'Logistics',
        self::TOURS_AND_TRAVEL           => 'Tours and Travel',
        self::TRANSPORT                  => 'Transport',
        self::ECOMMERCE                  => 'Ecommerce',
        self::FOOD                       => 'Food and Beverage',
        self::IT_AND_SOFTWARE            => 'IT and Software',
        self::GAMING                     => 'Gaming',
        self::MEDIA_AND_ENTERTAINMENT    => 'Media and Entertainment',
        self::SERVICES                   => 'Services',
        self::HOUSING                    => 'Housing and Real Estate',
        self::NOT_FOR_PROFIT             => 'Not-For-Profit',
        self::SOCIAL                     => 'Social',
        self::OTHERS                     => 'Others',

        self::COUPONS                              => 'Ad/Coupons/Deals Services',
        self::REPAIR_AND_CLEANING                  => 'Automotive Repair Shops',
        self::ACCOUNTING                           => 'Accounting Services',
        self::TELECOMMUNICATION_SERVICE            => 'Pre/Post Paid/Telecom Services',
        self::SERVICE_CENTRE                       => 'Service Centre',
        self::COWORKING                            => 'Real Estate Agents/Rentals',
        self::CAB_HAILING                          => 'Cab Service',
        self::GROCERY                              => 'Grocery',
        self::PAAS                                 => 'Platform as a Service',
        self::SAAS                                 => 'Software as a Service',
        self::WEB_DEVELOPMENT                      => 'Web designing/Development',
        self::CHARITY                              => 'Charity',
        self::FASHION_AND_LIFESTYLE                => 'Fashion and Lifestyle',
        self::DROP_SHIPPING                        => 'General Merchandise Stores',
        self::CONSULTING_AND_OUTSOURCING           => 'Consulting/PR Services',
        self::CATERING                             => 'Caterers',
        self::HEALTH_COACHING                      => 'Health and Beauty Spas',
        self::COMPUTER_PROGRAMMING_DATA_PROCESSING => 'Computer Programming/Data Processing',
        self::UTILITIES_ELECTRIC_GAS_OIL_WATER     => 'Utilities–Electric, Gas, Water, Oil',
    ];

    // Business Category to Subcategories Details mapping
    const SUBCATEGORY_MAP = [
        self::ECOMMERCE => [
            Sub::ECOMMERCE_MARKETPLACE,
            Sub::AGRICULTURE,
            Sub::BOOKS,
            Sub::ELECTRONICS_AND_FURNITURE,
            Sub::COUPONS,
            Sub::RENTAL,
            Sub::FASHION_AND_LIFESTYLE,
            Sub::GIFTING,
            Sub::GROCERY,
            Sub::BABY_PRODUCTS,
            Sub::OFFICE_SUPPLIES,
            Sub::WHOLESALE,
            Sub::RELIGIOUS_PRODUCTS,
            Sub::PET_PRODUCTS,
            Sub::SPORTS_PRODUCTS,
            Sub::ARTS_AND_COLLECTIBLES,
            Sub::SEXUAL_WELLNESS_PRODUCTS,
            Sub::DROP_SHIPPING,
            Sub::CRYPTO_MACHINERY,
            Sub::TOBACCO,
            Sub::WEAPONS_AND_AMMUNITIONS,
            Sub::STAMPS_AND_COINS_STORES,
            Sub::AUTOMOBILE_PARTS_AND_EQUIPEMENTS,
            Sub::OFFICE_EQUIPMENT,
            Sub::GARDEN_SUPPLY_STORES,
            Sub::HOUSEHOLD_APPLIANCE_STORES,
            Sub::NON_DURABLE_GOODS,
            Sub::ELECTRICAL_PARTS_AND_EQUIPMENT,
            Sub::WIG_AND_TOUPEE_SHOPS,
            Sub::GIFT_NOVELTY_AND_SOUVENIR_SHOPS,
            Sub::DUTY_FREE_STORES,
            Sub::OFFICE_AND_COMMERCIAL_FURNITURE,
            Sub::DRY_GOODS,
            Sub::BOOKS_AND_PUBLICATIONS,
            Sub::CAMERA_AND_PHOTOGRAPHIC_STORES,
            Sub::MEAT_SUPPLY_STORES,
            Sub::LEATHER_GOODS_AND_LUGGAGE,
            Sub::SNOWMOBILE_DEALERS,
            Sub::MEN_AND_BOYS_CLOTHING_STORES,
            Sub::PAINT_SUPPLY_STORES,
            Sub::AUTOMOTIVE_PARTS,
            Sub::JEWELLERY_AND_WATCH_STORES,
            Sub::AUTO_STORE_HOME_SUPPLY_STORES,
            Sub::TENT_STORES,
            Sub::PETROLEUM_AND_PETROLEUM_PRODUCTS,
            Sub::DEPARTMENT_STORES,
            Sub::SHOE_STORES_RETAIL,
            Sub::AUTOMOTIVE_TIRE_STORES,
            Sub::SPORT_APPAREL_STORES,
            Sub::CHEMICALS_AND_ALLIED_PRODUCTS,
            Sub::FIREPLACE_PARTS_AND_ACCESSORIES,
            Sub::COMMERCIAL_EQUIPMENTS,
            Sub::FAMILY_CLOTHING_STORES,
            Sub::FABRIC_AND_SEWING_STORES,
            Sub::CAMPER_RECREATIONAL_AND_UTILITY_TRAILER_DEALERS,
            Sub::RECORD_SHOPS,
            Sub::HOME_SUPPLY_WAREHOUSE,
            Sub::CLOCKS_AND_SILVERWARE_STORES,
            Sub::ART_SUPPLY_STORES,
            Sub::PAWN_SHOPS,
            Sub::SCHOOL_SUPPLIES_AND_STATIONERY,
            Sub::OPTICIANS_OPTICAL_GOODS_AND_EYEGLASSE_STORES,
            Sub::WATCH_AND_JEWELLERY_REPAIR_STORES,
            Sub::WHOLESALE_FOOTWEAR_STORES,
            Sub::ANTIQUE_STORES,
            Sub::PLUMBING_AND_HEATING_EQUIPMENT,
            Sub::VARIETY_STORES,
            Sub::LIQUOR_STORES,
            Sub::BOAT_DEALERS,
            Sub::COSMETIC_STORES,
            Sub::HOME_FURNISHING_STORES,
            Sub::TELECOMMUNICATION_EQUIPMENT_STORES,
            Sub::WOMEN_CLOTHING,
            Sub::FLORISTS,
            Sub::COMMERCIAL_PHOTOGRAPHY_AND_GRAPHIC_DESIGN_SERVICES,
            Sub::BUILDING_MATRIAL_STORES,
            Sub::CANDY_NUT_CONFECTIONERY_SHOPS,
            Sub::GLASS_AND_WALLPAPER_STORES,
            Sub::VIDEO_GAME_SUPPLY_STORES,
            Sub::DRAPERY_AND_WINDOW_COVERINGS_STORES,
            Sub::UNIFORMS_AND_COMMERCIAL_CLOTHING_STORES,
            Sub::AUTOMOTIVE_PAINT_SHOPS,
            Sub::DURABLE_GOODS_STORES,
            Sub::FUR_SHOPS,
            Sub::INDUSTRIAL_SUPPLIES,
            Sub::MOTORCYCLE_SHOPS_AND_DEALERS,
            Sub::CHILDREN_AND_INFANTS_WEAR_STORES,
            Sub::COMPUTER_SOFTWARE_STORES,
            Sub::WOMEN_ACCESSORY_STORES,
            Sub::BOOKS_PERIODICALS_AND_NEWSPAPER,
            Sub::FLOOR_COVERING_STORES,
            Sub::CRYSTAL_AND_GLASSWARE_STORES,
            Sub::HARDWARE_EQUIPMENT_AND_SUPPLY_STORES,
            Sub::DISCOUNT_STORES,
            Sub::COMPUTERS_PERIPHERAL_EQUIPMENT_SOFTWARE,
            Sub::AUTOMOBILE_AND_TRUCK_DEALERS,
            Sub::AIRCRAFT_AND_FARM_EQUIPMENT_DEALERS,
            Sub::ANTIQUE_SHOPS_SALES_AND_REPAIRS,
            Sub::BICYCLE_STORES,
            Sub::HEARING_AIDS_STORES,
            Sub::MUSIC_STORES,
            Sub::CONSTRUCTION_MATERIALS,
            Sub::ACCESSORY_AND_APPAREL_STORES,
            Sub::SECOND_HAND_STORES,
            Sub::FUEL_DEALERS,
            sub::FURNITURE_AND_HOME_FURNISHING_STORE,
        ],

        self::EDUCATION => [
            Sub::COLLEGE,
            Sub::SCHOOLS,
            Sub::UNIVERSITY,
            Sub::PROFESSIONAL_COURSES,
            Sub::DISTANCE_LEARNING,
            Sub::DAY_CARE,
            Sub::COACHING,
            Sub::ELEARNING,
            Sub::VOCATIONAL_AND_TRADE_SCHOOLS,
            Sub::SPORTING_CLUBS,
            Sub::DANCE_HALLS_STUDIOS_AND_SCHOOLS,
            Sub::CORRESPONDENCE_SCHOOLS,
        ],

        self::FASHION_AND_LIFESTYLE => [
            Sub::FASHION_AND_LIFESTYLE,
        ],

        self::FOOD => [
            Sub::ONLINE_FOOD_ORDERING,
            Sub::RESTAURANT,
            Sub::FOOD_COURT,
            Sub::CATERING,
            Sub::ALCOHOL,
            Sub::RESTAURANT_SEARCH_AND_BOOKING,
            Sub::DAIRY_PRODUCTS,
            Sub::BAKERIES,
        ],

        self::GROCERY => [
            Sub::GROCERY,
        ],

        self::IT_AND_SOFTWARE => [
            Sub::SAAS,
            Sub::PAAS,
            Sub::IAAS,
            Sub::CONSULTING_AND_OUTSOURCING,
            Sub::WEB_DEVELOPMENT,
            Sub::TECHNICAL_SUPPORT,
            Sub::DATA_PROCESSING,
        ],

        self::HEALTHCARE => [
            Sub::PHARMACY,
            Sub::CLINIC,
            Sub::HOSPITAL,
            Sub::LAB,
            Sub::DIETICIAN,
            Sub::FITNESS,
            Sub::HEALTH_COACHING,
            Sub::HEALTH_PRODUCTS,
            Sub::HEALTHCARE_MARKETPLACE,
            Sub::OSTEOPATHS,
            Sub::MEDICAL_EQUIPMENT_AND_SUPPLY_STORES,
            Sub::DRUG_STORES,
            Sub::PODIATRISTS_AND_CHIROPODISTS,
            Sub::DENTISTS_AND_ORTHODONTISTS,
            Sub::HARDWARE_STORES,
            Sub::OPHTHALMOLOGISTS,
            Sub::ORTHOPEDIC_GOODS_STORES,
            Sub::HEALTH_PRACTITIONERS_MEDICAL_SERVICES,
            Sub::TESTING_LABORATORIES,
            Sub::DOCTORS,
        ],

        self::SERVICES => [
            Sub::REPAIR_AND_CLEANING,
            Sub::INTERIOR_DESIGN_AND_ARCHITECT,
            Sub::MOVERS_AND_PACKERS,
            Sub::LEGAL,
            Sub::EVENT_PLANNING,
            Sub::SERVICE_CENTRE,
            Sub::CONSULTING,
            Sub::AD_AND_MARKETING,
            Sub::SERVICES_CLASSIFIEDS,
            Sub::MULTI_LEVEL_MARKETING,
            Sub::CONSTRUCTION_SERVICES,
            Sub::ARCHITECTURAL_SERVICES,
            Sub::CAR_WASHES,
            Sub::MOTOR_HOME_RENTALS,
            Sub::STENOGRAPHIC_AND_SECRETARIAL_SUPPORT_SERVICES,
            Sub::CHIROPRACTORS,
            Sub::AUTOMOTIVE_SERVICE_SHOPS,
            Sub::SHOE_REPAIR_SHOPS,
            Sub::TELECOMMUNICATION_SERVICE,
            Sub::FINES,
            Sub::SECURITY_AGENCIES,
            Sub::TYPE_SETTING_AND_ENGRAVING_SERVICES,
            Sub::SMALL_APPLIANCE_REPAIR_SHOPS,
            Sub::PHOTOGRAPHY_LABS,
            Sub::DRY_CLEANERS,
            Sub::ELECTRONIC_REPAIR_SHOPS,
            Sub::CLEANING_AND_SANITATION_SERVICES,
            Sub::NURSING_CARE_FACILITIES,
            Sub::DIRECT_MARKETING,
            Sub::VETERINARY_SERVICES,
            Sub::AFFLIATED_AUTO_RENTAL,
            Sub::ALIMONY_AND_CHILD_SUPPORT,
            Sub::AIRPORT_FLYING_FIELDS,
            Sub::TIRE_RETREADING_AND_REPAIR_SHOPS,
            Sub::TELEVISION_CABLE_SERVICES,
            Sub::RECREATIONAL_AND_SPORTING_CAMPS,
            Sub::AGRICULTURAL_COOPERATIVES,
            Sub::CARPENTRY_CONTRACTORS,
            Sub::WRECKING_AND_SALVAGING_SERVICES,
            Sub::AUTOMOBILE_TOWING_SERVICES,
            Sub::BARBER_AND_BEAUTY_SHOPS,
            Sub::VIDEO_TAPE_RENTAL_STORES,
            Sub::GOLF_COURSES,
            Sub::MISCELLANEOUS_REPAIR_SHOPS,
            Sub::MOTOR_HOMES_AND_PARTS,
            Sub::DEBT_MARRIAGE_PERSONAL_COUNSELING_SERVICE,
            Sub::AIR_CONDITIONING_AND_REFRIGERATION_REPAIR_SHOPS,
            Sub::TAILORS,
            Sub::MASSAGE_PARLORS,
            Sub::HORSE_OR_DOG_RACING,
            Sub::CREDIT_REPORTING_AGENCIES,
            Sub::HEATING_AND_PLUMBING_CONTRACTORS,
            Sub::ELECTRICAL_CONTRACTORS,
            Sub::CARPET_AND_UPHOLSTERY_CLEANING_SERVICES,
            Sub::ROOFING_AND_METAL_WORK_CONTRACTORS,
            Sub::INTERNET_SERVICE_PROVIDERS,
            Sub::LAUNDRY_SERVICES,
            Sub::RECREATIONAL_CAMPS,
            Sub::MASONRY_CONTRACTORS,
            Sub::EXTERMINATING_AND_DISINFECTING_SERVICES,
            Sub::AMBULANCE_SERVICES,
            Sub::FUNERAL_SERVICES_AND_CREMATORIES,
            Sub::METAL_SERVICE_CENTRES,
            Sub::COPYING_AND_BLUEPRINTING_SERVICES,
            Sub::FUEL_DISPENSERS,
            Sub::LOTTERY,
            Sub::WELDING_REPAIR,
            Sub::MOBILE_HOME_DEALERS,
            Sub::CONCRETE_WORK_CONTRACTORS,
            Sub::BOAT_RENTALS,
            Sub::PERSONAL_SHOPPERS_AND_SHOPPING_CLUBS,
            Sub::DOOR_TO_DOOR_SALES,
            Sub::TRAVEL_RELATED_DIRECT_MARKETING,
            Sub::LOTTERY_AND_BETTING,
            Sub::BANDS_ORCHESTRAS_AND_MISCELLANEOUS_ENTERTAINERS,
            Sub::FURNITURE_REPAIR_AND_REFINISHING,
            Sub::DIRECT_MARKETING_AND_SUBSCRIPTION_MERCHANTS,
            Sub::TYPEWRITER_STORES_SALES_SERVICE_AND_RENTALS,
            Sub::DIRECT_MARKETING_INSURANCE_SERVICES,
            Sub::BUSINESS_SERVICES,
            Sub::INBOUND_TELEMARKETING_MERCHANTS,
            Sub::RECREATION_SERVICES,
            Sub::SWIMMING_POOLS,
            Sub::OUTBOUND_TELEMARKETING_MERCHANTS,
            Sub::PUBLIC_WAREHOUSING,
            Sub::CLOTHING_RENTAL_STORES,
            Sub::CONTRACTORS,
            Sub::TRANSPORTATION_SERVICES,
            Sub::ELECTRIC_RAZOR_STORES,
            Sub::SERVICE_STATIONS,
            sub::PHOTOGRAPHIC_STUDIO,
            sub::PROFESSIONAL_SERVICES,
        ],

        self::WEB_DEVELOPMENT => [
            Sub::WEB_DEVELOPMENT,
        ],

        self::ACCOUNTING => [
            Sub::ACCOUNTING,
        ],

        self::COUPONS => [
            Sub::COUPONS,
        ],

        self::REPAIR_AND_CLEANING => [
            Sub::REPAIR_AND_CLEANING,
        ],

        self::CAB_HAILING => [
            Sub::CAB_HAILING,
        ],

        self::CATERING => [
            Sub::CATERING,
        ],

        self::CHARITY => [
            Sub::CHARITY,
        ],

        self::COMPUTER_PROGRAMMING_DATA_PROCESSING => [
            Sub::COMPUTER_PROGRAMMING_DATA_PROCESSING,
        ],

        self::CONSULTING_AND_OUTSOURCING => [
            Sub::CONSULTING_AND_OUTSOURCING,
        ],

        self::FINANCIAL_SERVICES => [
            Sub::MUTUAL_FUND,
            Sub::LENDING,
            Sub::CRYPTOCURRENCY,
            Sub::INSURANCE,
            Sub::NBFC,
            Sub::COOPERATIVES,
            Sub::PENSION_FUND,
            Sub::FOREX,
            Sub::SECURITIES,
            Sub::COMMODITIES,
            Sub::ACCOUNTING,
            Sub::FINANCIAL_ADVISOR,
            Sub::CROWDFUNDING,
            Sub::TRADING,
            Sub::BETTING,
            Sub::GET_RICH_SCHEMES,
            Sub::MONEYSEND_FUNDING,
            Sub::WIRE_TRANSFERS_AND_MONEY_ORDERS,
            Sub::TAX_PREPARATION_SERVICES,
            Sub::TAX_PAYMENTS,
            Sub::DIGITAL_GOODS,
            Sub::ATMS,
        ],

        self::GAMING => [
            Sub::GAME_DEVELOPER,
            Sub::ESPORTS,
            Sub::ONLINE_CASINO,
            Sub::FANTASY_SPORTS,
            Sub::GAMING_MARKETPLACE,
        ],

        self::DROP_SHIPPING => [
            Sub::DROP_SHIPPING,
        ],

        self::GOVERNMENT => [
            Sub::CENTRAL,
            Sub::STATE,
            Sub::INTRA_GOVERNMENT_PURCHASES,
            Sub::GOVERMENT_POSTAL_SERVICES,
        ],

        self::HEALTH_COACHING => [
            Sub::HEALTH_COACHING,
        ],

        self::HOUSING => [
            Sub::DEVELOPER,
            Sub::FACILITY_MANAGEMENT,
            Sub::RWA,
            Sub::COWORKING,
            Sub::REALESTATE_CLASSIFIEDS,
            Sub::SPACE_RENTAL,
        ],

        self::LOGISTICS => [
            Sub::FREIGHT,
            Sub::COURIER,
            Sub::WAREHOUSING,
            Sub::DISTRIBUTION,
            Sub::END_TO_END_LOGISTICS,
            Sub::COURIER_SERVICES,
        ],

        self::MEDIA_AND_ENTERTAINMENT => [
            Sub::VIDEO_ON_DEMAND,
            Sub::MUSIC_STREAMING,
            Sub::MULTIPLEX,
            Sub::CONTENT_AND_PUBLISHING,
            Sub::TICKETING,
            Sub::NEWS,
            Sub::VIDEO_GAME_ARCADES,
            Sub::VIDEO_TAPE_PRODUCTION_AND_DISTRIBUTION,
            Sub::BOWLING_ALLEYS,
            Sub::BILLIARD_AND_POOL_ESTABLISHMENTS,
            Sub::AMUSEMENT_PARKS_AND_CIRCUSES,
            Sub::TICKET_AGENCIES,
        ],

        self::NOT_FOR_PROFIT => [
            Sub::CHARITY,
            Sub::EDUCATIONAL,
            Sub::RELIGIOUS,
            Sub::PERSONAL,
        ],

        self::OTHERS => [
        ],

        self::PAAS => [
            Sub::PAAS,
        ],

        self::COWORKING => [
            Sub::COWORKING,
        ],

        self::SAAS => [
            Sub::SAAS,
        ],

        self::SERVICE_CENTRE => [
            Sub::SERVICE_CENTRE,
        ],

        self::SOCIAL => [
            Sub::MATCHMAKING,
            Sub::SOCIAL_NETWORK,
            Sub::MESSAGING,
            Sub::PROFESSIONAL_NETWORK,
            Sub::NEIGHBOURHOOD_NETWORK,
            Sub::AUTOMOBILE_ASSOCIATIONS_AND_CLUBS,
            Sub::POLITICAL_ORGANIZATIONS,
            Sub::COUNTRY_AND_ATHLETIC_CLUBS,
            Sub::ASSOCIATIONS_AND_MEMBERSHIP,
        ],

        self::TELECOMMUNICATION_SERVICE => [
            Sub::TELECOMMUNICATION_SERVICE,
        ],

        self::TOURS_AND_TRAVEL => [
            Sub::AVIATION,
            Sub::ACCOMMODATION,
            Sub::OTA,
            Sub::TRAVEL_AGENCY,
            Sub::TOURIST_ATTRACTIONS_AND_EXHIBITS,
            Sub::AQUARIUMS_DOLPHINARIUMS_AND_SEAQUARIUMS,
            Sub::TIMESHARES,
        ],

        self::TRANSPORT => [
            Sub::CAB_HAILING,
            Sub::BUS,
            Sub::TRAIN_AND_METRO,
            Sub::AUTOMOBILE_RENTALS,
            Sub::CRUISE_LINES,
            Sub::PARKING_LOTS_AND_GARAGES,
            Sub::BRIDGE_AND_ROAD_TOLLS,
            Sub::FREIGHT_TRANSPORT,
            Sub::TRUCK_AND_UTILITY_TRAILER_RENTALS,
            Sub::TRANSPORTATION,
        ],

        self::UTILITIES => [
            Sub::ELECTRICITY,
            Sub::GAS,
            Sub::TELECOM,
            Sub::WATER,
            Sub::CABLE,
            Sub::BROADBAND,
            Sub::DTH,
            Sub::INTERNET_PROVIDER,
            Sub::BILL_AND_RECHARGE_AGGREGATORS,
        ],

        self::UTILITIES_ELECTRIC_GAS_OIL_WATER => [
            Sub::UTILITIES_ELECTRIC_GAS_OIL_WATER,
        ],
    ];

    public static function getCategoryFromSubCategory(string $subCategory)
    {
        foreach (self::SUBCATEGORY_MAP as $category => $subCategories)
        {
            if (in_array($subCategory, $subCategories, true) === true)
            {
                return $category;
            }
        }

        return self::OTHERS;
    }

    public static function getWhitelistedSubCategory(string $category)
    {
        if (isset(self::SUBCATEGORY_MAP[$category]) === true)
        {
            $subCategories = self::SUBCATEGORY_MAP[$category];

            foreach ($subCategories as $subCategory)
            {
                $subCategoryMap = BusinessSubCategoryMetaData::SUB_CATEGORY_METADATA[$subCategory];

                if($subCategoryMap[Entity::ACTIVATION_FLOW] === ActivationFlow::WHITELIST)
                {
                    return $subCategory;
                }
            }
        }

        return null;
    }
}
