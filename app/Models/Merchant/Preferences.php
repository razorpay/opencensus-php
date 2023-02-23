<?php

namespace RZP\Models\Merchant;

use Config;
use RZP\Models\Card\Network;
use RZP\Models\Merchant\Methods\EmiType;
use RZP\Models\Payment\Gateway;
use RZP\Models\Feature\Constants as Feature;
use Symfony\Component\HttpFoundation\HeaderBag as Headers;

class Preferences
{
    const MID_SOCH                  = '6QGdVzDAIpBniU';
    const MID_ZOMATO                = '6H7N6hlcv29OMG';
    const MID_IPAY                  = '6VS1z0fmis8fn6';
    const MID_CUREFIT               = '6vwsEbqse39D4d';
    const MID_DSPBLACKROCK          = '7thBRSDflu7NHL';
    const MID_GOALWISE_TPV          = '7BfRNg10LH7N6T';
    const MID_GOALWISE_NON_TPV      = '8ytYezIThlseJd';
    const MID_WEALTHAPP             = '9LYKZiz2kpFFtY';
    const MID_MONEYVIEW             = '8hXTLsmoM3F6PH';
    const MID_WEALTHY               = '8lv4idBRY4C9c0';
    const MID_PIGGY                 = '9IjdEkLQb0j2ro';
    const MID_PIGGY_TPV             = 'BADGdiwSiwi1g2';
    const MID_SHELL                 = '9LMdTQdjgMJ6uR';
    const MID_SHELL_2               = '9R0AsTqocyuP1W';
    const MID_PAISABAZAAR           = '9dhe2WRR0XCQz6';
    const MID_PAISABAZAAR_GOLD      = 'AiWdjAyyF4RKBa';
    const MID_BPCL                  = '9C04GG1wPzKCUP';
    const MID_SRI_CHAITANYA         = '8f9o3YjPGZEcdU';
    const MID_UBER                  = '82LK42BGTN2bOe';
    const MID_AMIT_MAHBUBANI        = '7SVOQZGZuwHr4I';
    const MID_ICICI_LOMBARD         = 'AXRuIp5uiz5Jsp';
    const MID_KARVY                 = 'AmReTNPu1KFKBn';
    const MID_ANGEL_BROKING         = 'AC4DJNMIX9xXOz';
    const MID_SHELLHATCH            = 'A7W1rwbYMRmn6M';
    const MID_PAISABAZAAR_MARKETING = 'B1uh6CFFBKk35S';
    const MID_AMIT_RBLCARD          = 'BcVn9Oy1aSkcOa';
    const MID_AMIT_RBLLOAN          = 'BcVzB5W2m4noKJ';
    const MID_RBLCARD               = 'BYUXW3iBH0P0zU';
    const MID_DELINQUENT_LOANS      = 'C3oXor5gWBUoWB';
    const MID_RBLLOAN               = 'BUjzZmAEXnXVJs';
    const MID_RBLBFL                = 'BjdSExY3hArAHm';
    const MID_RBL_TOTAL_BASE        = 'BoccLxCbqWFXmU';
    const MID_DMI_FINANCE           = 'BU4wKuO2IisLWY';
    const MID_VARTHANA_FINANCE      = 'BpqmTAX1XcFMvB';
    const MID_INDIABULLS_FINANCE    = 'BXdV62dMAbb869';
    const MID_DREAM11               = '6L6z7NYQywAaP0';
    const MID_RBLLENDING            = 'BOX702yaBbEfJo';
    const MID_APOLLO_MUNICH         = 'BYqeLRvN6FfCCY';
    const MID_SWIGGY_DROPPT         = 'CTwAEBRfwEjEme';
    const MID_SURYODAY_BANK         = 'CxRu8Yxj1LgPnw';
    const MID_RELIANCE_AMC          = 'CR3D37POcSDpR3';
    const MID_KALMADI_HIGH_SCHOOL   = '7icgzKgnv7IMbP';
    const MID_RI_PARAMEDICAL        = '7icw1On5t9IXsB';
    const MID_MICROCON_2017         = '7oDON3kPcJ7H0m';
    const MID_KALMADI_PRE_PRIMARY   = '88IcLp3vpTAiRs';
    const MID_KALMADI_SECONDARY     = '88Mb148fYyG8IV';
    const MID_AISSMS_POLY_PUNE      = '8D5KopQF8YFlp8';
    const MID_ALL_INDIA_SHIVAJI     = '8D6kwfRAxV53kG';
    const MID_AISSMS_COE_ME_PUNE    = '8D6ljzbPGn7vlU';
    const MID_AISSMS_HMCT_PUNE      = '8D6mpG7TaWEexQ';
    const MID_AISSMS_HMCT_BSC_PUNE5 = '8D6nDCfvutIgnL';
    const MID_AISSMS_POLY_PUNE_1_SS = '8D6nbb1N6W5Vsi';
    const MID_AISSMS_COF_PHD        = '8D6oBTMSWEHWXZ';
    const MID_AISSMS_IOM_PUNE       = '8D6oVXlSDPDHE4';
    const MID_AISSMS_INSTITUTE_IT   = '8D6p49fCibBw0q';
    const MID_AISSMS_COE_PUNE       = '8DLX1GnevI3yrF';
    const MID_AISSMS_HMCT_MHMCT     = '8DLoeDaskH75I4';
    const MID_AISSMS_COF_M_PHARM    = '8DLrylJRCO1W7n';
    const MID_AISSMS_COF_B_PHARM    = '8DLwBBvuGh6CSP';
    const MID_BOMBAY_SAPPERS_ARMY   = '8l7NbZVeQn3JLz';
    const MID_NAGPUR_TRAFFIC_POLICE = 'BfJ6bcwgtERLYS';
    const MID_BL_INT_SMART_SCHOOL   = 'BhTHR8Ph7VfgeB';
    const MID_BL_INT_SMART_SCHOOL_2 = 'BlLkcctXMtQpWm';
    const MID_VFH_BOB_MERCHANTS     = 'CENIroHjft6MH1';
    const MID_GOVT_DEG_COLL_VFH     = 'CEUCo0PzZebQqv';
    const MID_GOVT_POLY_COLL_VFH    = 'CEUCp0yYH2PBjS';
    const MID_DEV_IN_NATIONAL_VFH   = 'CEUCpvmdnlxUd2';
    const MID_IIM_KOZHIKODE_VFH     = 'CEUCszAS02gylE';
    const MID_BHAVANS_VIDYA_VFH     = 'CEUCtxZ9OhPXMK';
    const MID_BRAIN_TREE_INT_VFH    = 'CEUCumhjuv3OYG';
    const MID_AIR_FORCE_GURG_VFH    = 'CEUCwmIlZZVqEx';
    const MID_OXFORD_COE_VFH        = 'CEUCyNOdaKhkBG';
    const MID_THOMAS_PUBLIC_VFH     = 'CEUCzA5V6jOtYa';
    const MID_NAVKIS_KKA_BANG_VFH   = 'CEUCzz9bKuNlW4';
    const MID_ST_XAVIR_COW_AL_VFH   = 'CEUD0mgsjHekBs';
    const MID_BALDWIN_ED_EXT_HS_VFH = 'CEUD1Ya6UhsH3l';
    const MID_SITWANTO_DMKS_VFH     = 'CEUD2N3pCDXMlu';
    const MID_LAKSHMI_JANARDAN_VFH  = 'CEUD3I5rcFeTXC';
    const MID_MSRIT_VFH             = 'CEUD4D78dukeZt';
    const MID_MNNIT_ALD_VFH         = 'CEUD5PWePwwWHu';
    const MID_SHARADA_VIDYALAYA_VFH = 'CEUD6vBEYaK4uQ';
    const MID_MOUNT_GUIDE_INT_VFH   = 'CEUDBZLpM0qIhW';
    const MID_MOUNT_CARMEL_VFH      = 'CEUDE0eVVBY5jk';
    const MID_VELTECH_UNI_VFH       = 'CEUDFJWTDScF4o';
    const MID_INST_MANPOWER_CAR_VFH = 'CEhSTrXvwDrvg6';
    const MID_NAVKIS_KINDER_KARE_MYS= 'CTkZcQkexhhnBT';
    const MID_NAVKIS_EDU_MYS        = 'CTomG5COHkph2k';
    const MID_NAVKIS_EDU_BANGLORE   = 'CTp4xOKuVpKmnC';
    const MID_MARIA_MONTE_HOUSE_CHI = 'CTpCbGJ3hxmvhl';
    const MID_PRES_WARDEN_STJE_SOC  = 'CTpMjoSzhhVOTJ';
    const MID_UHUDA_RERA            = 'CV04yKB3sjIYdG';
    const MID_YAMUNA_EXPRESSWAY     = 'CjeLqZN5ToFLox';
    const MID_PT_SENDERLAL_OPEN_UNI = 'CmOpbOIG251EkR';
    const MID_SDM_YOGA_AND_NATURE   = 'CoMA8GeyufpdOA';
    const MID_ARMY_PUBLIC_SCHOOL    = 'Coie6L2Dma3sI1';
    const MID_RBL_PDD_BANK          = 'CvUGpq6RlNHdQK';
    const MID_RBL_PDD_CREDIT        = 'Cya5vz9ti9rO25';
    const MID_BFL_BANK              = 'CvUFJHEqgYwE85';
    const MID_BFL_CARD              = 'Cya3FzbrKbxMGg';
    const MID_RBL_LAPOD             = 'CzQAGjwnr3RSqw';
    const MID_RBL_PL_NON_DEL_CUST   = 'DAeLo1KdwN2BTW';
    const MID_RBL_RETAIL_ASSETS     = 'AtgdEIzM6qtWmS';
    const MID_RBL_RETAIL_CUSTOMER   = 'FG2fcantbZvOhI';
    const MID_RBL_RETAIL_PRODUCT    = 'FG3C2VzQpgMxb2';
    const MID_RBL_LENDING           = 'FpHNbOB8YmlzWy';
    const MID_RBL_INTERIM_PROCESS   = 'D83Pk7NqU6URGe';
    const MID_RBL_INTERIM_PROCESS3  = 'EegNef3rx3UvuV';
    const MID_RBL_INTERIM_PROCESS4  = 'HBkKfo9bYjgqu8';
    const MID_RBL_INTERIM_PROCESS5  = 'Hek2tjDRJHNgO0';
    const MID_MSR_LAW_CLG_VFH       = 'CxqHOiYBc8yG4U';
    const MID_BOB                   = 'CxOgfvYhxGztjJ';
    const MID_BOB_2                 = 'DyLpdroA9jOWcY';
    const MID_BOB_3                 = 'DyLsgu8Kh683Ja';
    const MID_BAGIC                 = 'CYseUgx4bt9VFp';
    const MID_BAGIC_2               = 'IEH5RyDZ4IrnCx';
    const MID_IMPACT_SCHOOL_ARCH    = 'D89MU9wL8ptnEM';
    const MID_VEL_TECH_HIGH_TECH    = 'CxrfYIK8mrctAN';
    const MID_MSRIT_EXAM_FEES_VFH   = 'CxqX32TCfZaHnQ';
    const MID_INST_ENG_AND_TECH_LKO = 'D88kPwDCDxbBu3';
    const MID_RBL_AGRI_LOAN         = 'DX4AnDB4Z9kzg0';
    const MID_RBL_INTERIM_PROCESS2  = 'DqPTv7SI18A7y8';
    const MID_LENDING_KART          = 'DfLPWHXDWcfB2Y';
    const MID_BFL                   = 'ChcYXdL7jtknMN';
    const MID_ICICI_PRUDENTIAL      = 'DyP8dTjuXkgcAA';
    const MID_SCRIP_BOX             = 'E8D4A78IIz4SAB';
    const MID_ET_MONEY              = 'CBcPtPwFgpjdUp';
    const MID_RBL_HEMANT            = 'EgquGKAHJNz0oD';
    const MID_RBL_BANK              = 'Er3H2qzJ3EVt4u';
    const MID_RBL_BANK_LTD          = 'Er4CQHti6YfgOG';
    const MID_RBL_BANK_1            = 'Er3owFcMpDfMvD';
    const MID_RBL_BANK_2            = 'FG3uzKzif6law5';
    const MID_STASHFIN              = 'Ao42qLIgNsuREt';
    const MID_VOCATIONAL_EDU        = 'F5NF8QNN7XAJ2w';
    const MID_BSE                   = 'FlaHVYQCGKbK2t';
    const MID_INDIABONDS            = 'IanlMZTWfq1Y7v';
    const MID_ICIC_SEC              = 'Kj3sw5mDSZkyXs';
    const MID_CRED_AVENUE           = 'LJO3Ll3t8JXabv';
    const MID_EDELWEISS_ECL         = 'FfaKyVTNaPTBXf';
    const MID_EDELWEISS_EHFL        = 'Fg1qjtRFHMvHSy';
    const MID_EDELWEISS_ERFL        = 'Fg2AWmybDBvRBM';
    const MID_ETSY                  = 'HscZ2md6SOPF3U';

    const MID_CLIX_CAPITAL            = 'AxEq4Z2U8Gd8vH';
    const MID_CLIX_CAPITAL_SERVICES   = 'Bkeuzp5jlMNhzD';
    const MID_CLIX_CAPITAL_SERVICES_1 = 'Bkeu2nWxjx9HaZ';
    const MID_CLIX_CAPITAL_SERVICES_2 = 'BkeuOuZR4CAI4P';
    const MID_CLIX_HOUSING_FINANCE    = 'DdURgPS1iee3jc';
    const MID_CLIX_FINANCE            = 'DdUJZmctBydK1m';
    const MID_CLIX_FINANCE_1          = 'DdU3RrefV6jkck';
    const MID_BOB_FIN                 = 'Evfc1S0zDq0daP';

    const MID_GEPL_CAPITAL_PVT_LTD        = 'EhtHoWq8Bx2EU9';
    const MID_GEPL_CAPITAL_PVT_LTD_1      = 'Ep10N8KxDvJilQ';
    const MID_GEPL_COMMODITIES_PVT_LTD    = 'EzJPtuXMDJRlxl';
    const MID_GEPL_COMMODITIES_PVT_LTD_1  = 'EzJQOzN6kg48LY';

    const MID_NSDL_MERCHANTS        = 'Anjg29UHP4PlvQ';
    const MID_AIRTEL                = 'AqUQQH9neAMkUG';

    const MID_ADWORTH_BUSINESS_SOL_LTD = 'ENgpyPMGtUTujD';
    const MID_BALAJI_TRADERS           = 'EAFMzRGMFH7GHa';
    const MID_TRUEVENTURE_TECH_PVT_LTD = 'EHhGINJDXP39jk';
    const MID_ASHISH_TRADING_COMPANY   = 'E2hBu7TFTKWi2p';
    const MID_VISION_ENTERPRISES       = 'EcGUwvUuLnsAkZ';
    const MID_HARMODE_OVERSEAS         = 'E7VLZYcXf53sn3';
    const MID_NANCY_ENTERPRISES        = 'E2LpblTR74gXko';
    const MID_VSHOP_ZONE               = 'F6mioBF6tWNsZX';
    const MID_MOBILE_STREET            = 'FMggLzFFDOUiXi';
    const MID_TREASURE_DEALS           = 'Exg6hvE8d3hAqU';
    const MID_BULKBAZAR                = 'EHwQQQErNniPV6';
    const MID_AIRTEL_PAYMENTS_BANK     = 'Car8R5NEEKAwc5';
    const MID_DIGIPAY                  = 'EO3VAyOndYiQpQ';
    const MID_MAHALAXMI_TRADERS        = 'FuedAHX6XiIEce';
    const MID_RAJESH_TELECOM           = 'FrWPMIvlTuDhKe';
    const MID_GENEXPRO                 = 'Fz4wOMKiOQX9ez';
    const MID_ANDHRA_INDUSTRIAL_CORP   = 'BTcsIgeI0fvT6a';
    const MID_OAKSTER_MEDIA_TECH       = 'G2hWoiPFTdsBbC';
    const MID_MILLIONSTRO              = 'G7loPlVW2DIxdv';
    const MID_AMAZING_KART             = 'G7mPROV5i51GJt';
    const MID_BHARTI_AIRTEL            = 'Fwwnzcx3FaqF4X';

    const MID_ADITYA_BIRLA_HEALTH     = 'F0sFCmi0LOeeGc';
    const MID_ADITYA_BIRLA_HEALTH_1   = 'EuxJCz8cZV9V63';
    const MID_ADITYA_BIRLA_HEALTH_2   = 'ExO4eKBgjHgbNd';
    const MID_ADITYA_BIRLA_HEALTH_3   = 'F40u24NuYoOvib';
    const MID_ADITYA_BIRLA_HEALTH_4   = 'FeME1n6GwlEifd';
    const MID_ADITYA_BIRLA_HEALTH_5   = 'FYAgmcCOAfGASp';

    const MID_KARNATAKA_UDYOG_MITRA = 'El3yN2k0PWFCWs';

    const DEMO_ACCOUNT         = '100DemoAccount';
    const MID_ENDURANCE        = [
        '9YAQd3b47mdIQY', '9ZO8jNaR0OORNH', '9Y9m9XscC6Kh4W',
        '8WRMdGzG1z5Eqw', '9naAGQdroegWIX', '9Y9m9XscC6Kh4W',
        '9okVtwZr5vLm4K', '9oklLp2FhXTolM', 'A0ERwPs8muf9YS',
        'A0GNi6PHlqy5zX', 'A0HuEfx39zhjr9', 'A5ONBRrNJ7dS1K',
        'A5MmRVEM3qf6QJ', 'A5OZ1qi9tgwnZB', 'A5OeZOCaeyQQ8Q',
    ];

    const MID_IRCTC = [
        '8byazTDARv4Io0',
        'AEPXwjSlJJhfUl',
        '9m4CChGex4ENkR',
        'AEsxERLbWiBuUG',
        '8ST00QgEPT14cE',
        '8YPFnW5UOM91H7',
        '90xVmQJTCEJ6GH'
    ];

    const MID_IXIGO = [
        'GtG3WLjGVjzx2n',
        'IZfauLUI88K21W',
        'GtFwVSbNTDTM9C',
        'HZ7EjkfJ8atulh',
        'GCwhxngAcMtWC8',
        '8RerE9oY0d7rbC',
        'GDJYY4pJqT0cQ5',
        'EOQRaXICwJIuoy', // internal MID
    ];

    const MID_CLEARTAX         = 'AGQJfLbWcmjxDX';
    const MID_APARTMENTADDA    = '9NVPPQuTqF4cYx';
    const MID_INVEZTA          = '8YQygO7pzP3Gut';

    const MID_ONBOARDING_PENNY_TESTING = '100000Razorpay';

    /**
     * This needs to go in DB, for hotfix we are keeping it here
     * Maintains lists of gateways excluded for a merchant
     */
    const MERCHANT_GATEWAY_BLACKLIST = [
        // Soch
        self::MID_SOCH => [
            Gateway::HDFC,
        ],

        // Zomato
        // Merchant does not allow gateways that send card info from client side
        self::MID_ZOMATO => [
            Gateway::AXIS_MIGS,
            Gateway::FIRST_DATA,
        ],
    ];

    const CUSTOMER_TRANSACTION_HISTORY_ENABLED_MID = [
        self::MID_SHELL,
        self::DEMO_ACCOUNT,
        self::MID_SHELL_2,
    ];

    public static $merchantSharedTerminalsBlackList = [
        self::MID_DSPBLACKROCK,
    ];

    /**
     * Maintains lists of gateways allowed for a merchant
     */
    const MERCHANT_GATEWAY_WHITELIST = [
        // Ipay
        // Merchant requires gateways with Dynamic Merchant Descriptor
        self::MID_IPAY => [
            Gateway::FIRST_DATA,
            Gateway::CYBERSOURCE,
        ],
    ];

    /**
     * We do not want to reject cybersource for these merchants
     */

    // in case of any changes in gateway config, please contact smart routing team
    // changes done here won't be reflected in routing
    const CYBERSOURCE_MERCHANT_WHITELIST = [
        self::MID_ZOMATO,
        self::MID_IPAY,
    ];

    const X_AGGREGATOR_HEADER = 'x-aggregator';

    //
    // Skip settlements for few merchants
    // Details in: https://github.com/razorpay/api/issues/5830
    // Temporary, until https://github.com/razorpay/api/pull/6161
    // is merged
    //
    const NO_SETTLEMENT_MIDS = [
        self::MID_GOALWISE_NON_TPV,
        self::MID_GOALWISE_TPV,
        self::MID_MONEYVIEW,
        self::MID_WEALTHY,
        self::MID_PIGGY,
        self::MID_PIGGY_TPV,
        self::MID_PAISABAZAAR,
        self::MID_PAISABAZAAR_GOLD,
        self::MID_BPCL,
        self::MID_SRI_CHAITANYA,
        self::MID_CLEARTAX,
        self::MID_APARTMENTADDA,
        self::MID_INVEZTA,
        self::MID_KARVY,
        self::MID_ANGEL_BROKING,
        self::MID_SHELLHATCH,
        self::MID_PAISABAZAAR_MARKETING,
    ];

    const ONLY_NEFT_SETTLEMENT_MIDS = [
        self::MID_PIGGY,
        self::MID_PIGGY_TPV,
        self::MID_KALMADI_HIGH_SCHOOL,
        self::MID_RI_PARAMEDICAL,
        self::MID_MICROCON_2017,
        self::MID_KALMADI_PRE_PRIMARY,
        self::MID_KALMADI_SECONDARY,
        self::MID_AISSMS_POLY_PUNE ,
        self::MID_ALL_INDIA_SHIVAJI,
        self::MID_AISSMS_COE_ME_PUNE,
        self::MID_AISSMS_HMCT_PUNE,
        self::MID_AISSMS_HMCT_BSC_PUNE5,
        self::MID_AISSMS_POLY_PUNE_1_SS,
        self::MID_AISSMS_COF_PHD,
        self::MID_AISSMS_IOM_PUNE,
        self::MID_AISSMS_INSTITUTE_IT,
        self::MID_AISSMS_COE_PUNE,
        self::MID_AISSMS_HMCT_MHMCT,
        self::MID_AISSMS_COF_M_PHARM,
        self::MID_AISSMS_COF_B_PHARM,
        self::MID_BOMBAY_SAPPERS_ARMY,
        self::MID_NAGPUR_TRAFFIC_POLICE,
        self::MID_BL_INT_SMART_SCHOOL,
        self::MID_BL_INT_SMART_SCHOOL_2,
        self::MID_VFH_BOB_MERCHANTS ,
        self::MID_GOVT_DEG_COLL_VFH ,
        self::MID_GOVT_POLY_COLL_VFH,
        self::MID_DEV_IN_NATIONAL_VFH,
        self::MID_IIM_KOZHIKODE_VFH,
        self::MID_BHAVANS_VIDYA_VFH,
        self::MID_BRAIN_TREE_INT_VFH,
        self::MID_AIR_FORCE_GURG_VFH,
        self::MID_OXFORD_COE_VFH,
        self::MID_THOMAS_PUBLIC_VFH,
        self::MID_NAVKIS_KKA_BANG_VFH,
        self::MID_ST_XAVIR_COW_AL_VFH,
        self::MID_BALDWIN_ED_EXT_HS_VFH,
        self::MID_SITWANTO_DMKS_VFH,
        self::MID_LAKSHMI_JANARDAN_VFH,
        self::MID_MSRIT_VFH,
        self::MID_MNNIT_ALD_VFH,
        self::MID_SHARADA_VIDYALAYA_VFH,
        self::MID_MOUNT_GUIDE_INT_VFH,
        self::MID_MOUNT_CARMEL_VFH,
        self::MID_VELTECH_UNI_VFH,
        self::MID_INST_MANPOWER_CAR_VFH,
        self::MID_NAVKIS_KINDER_KARE_MYS,
        self::MID_NAVKIS_EDU_MYS,
        self::MID_NAVKIS_EDU_BANGLORE,
        self::MID_MARIA_MONTE_HOUSE_CHI,
        self::MID_PRES_WARDEN_STJE_SOC,
        self::MID_UHUDA_RERA,
        self::MID_YAMUNA_EXPRESSWAY,
        self::MID_PT_SENDERLAL_OPEN_UNI,
        self::MID_SDM_YOGA_AND_NATURE,
        self::MID_ARMY_PUBLIC_SCHOOL,
        self::MID_MSR_LAW_CLG_VFH,
        self::MID_IMPACT_SCHOOL_ARCH,
        self::MID_VEL_TECH_HIGH_TECH,
        self::MID_MSRIT_EXAM_FEES_VFH,
        self::MID_INST_ENG_AND_TECH_LKO,
        self::MID_VOCATIONAL_EDU,
        // slack thread for ref: https://razorpay.slack.com/archives/C15277TQB/p1614088830075000?thread_ts=1612939005.084100&cid=C15277TQB
        self::MID_BHARTI_AIRTEL,
        //slack thread for ref: https://razorpay.slack.com/archives/CC4EUSBNG/p1623743273021800
        self::MID_KARNATAKA_UDYOG_MITRA,
    ];

    const TRANSFER_SETTLED_WEBHOOK_MIDS = [
        self::MID_BSE,
        self::MID_INDIABONDS,
        self::MID_ICIC_SEC,
        self::MID_CRED_AVENUE,
    ];

    /**
     * This is in regard to RBI compliance. The change is temporary and will be reverted soon.
     */
    const BLOCK_LINKED_ACCOUNT_CREATION_MIDS = [
        self::MID_ETSY,
    ];

    const NO_MERCHANT_INVOICE_PARENT_MIDS =  [
        self::MID_NSDL_MERCHANTS,
    ];

    /**
     * Array of merchant ids for which invoice should not be generated.
     * Disabled for Airtel Payments Bank currently.
     */
    const NO_MERCHANT_INVOICE_MIDS = [
        self::MID_AIRTEL,
        self::MID_ADWORTH_BUSINESS_SOL_LTD,
        self::MID_BALAJI_TRADERS,
        self::MID_TRUEVENTURE_TECH_PVT_LTD,
        self::MID_ASHISH_TRADING_COMPANY,
        self::MID_VISION_ENTERPRISES,
        self::MID_HARMODE_OVERSEAS,
        self::MID_NANCY_ENTERPRISES,
        self::MID_VSHOP_ZONE,
        self::MID_MOBILE_STREET,
        self::MID_TREASURE_DEALS,
        self::MID_BULKBAZAR,
        self::MID_AIRTEL_PAYMENTS_BANK,
        self::MID_DIGIPAY,
        self::MID_MAHALAXMI_TRADERS,
        self::MID_RAJESH_TELECOM,
        self::MID_GENEXPRO,
        self::MID_ANDHRA_INDUSTRIAL_CORP,
        self::MID_OAKSTER_MEDIA_TECH,
        self::MID_MILLIONSTRO,
        self::MID_AMAZING_KART,
    ];

    // MSwipe Configurations
    const MSWIPE_PARTNER_MID            = 'BiKdKIgjODkDca';
    const MSWIPE_PRICING_PLAN_ID        = 'CeW6THajbzAkCC';
    const MSWIPE_SETTLEMENT_SCHEDULE_ID = '7xc78ePv15g3bz';
    const MSWIPE_FEATURE_LIST           = [
        Feature::EMAIL_OPTIONAL,
        Feature::EXPOSE_CARD_IIN,
        Feature::USE_MSWIPE_TERMINALS,
    ];
    const MSWIPE_METHOD_LIST            = [
        Methods\Entity::CREDIT_CARD   => 1,
        Methods\Entity::DEBIT_CARD    => 1,
        Methods\Entity::NETBANKING    => 1,
        Methods\Entity::EMI           => [EmiType::DEFAULT_TYPES],
        Methods\Entity::UPI           => 0,
        Methods\Entity::BANK_TRANSFER => 0,
        Methods\Entity::MOBIKWIK      => 1,
        Methods\Entity::FREECHARGE    => 1,
        Methods\Entity::AIRTELMONEY   => 1,
        Methods\Entity::PAYZAPP       => 1,
        Methods\Entity::JIOMONEY      => 1,
        Methods\Entity::PAYUMONEY     => 1,
        Methods\Entity::MPESA         => 1,
        Methods\Entity::PHONEPE       => 1,
        Methods\Entity::CARD_NETWORKS => [
            Network::DICL => 0,
        ]
    ];

    public static function checkZohoHeaders(Headers $headers)
    {
        $expectedHeader = Config::get('applications.zoho.header');

        return ($expectedHeader === $headers->get(self::X_AGGREGATOR_HEADER));
    }
}
