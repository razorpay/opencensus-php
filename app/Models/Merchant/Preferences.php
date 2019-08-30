<?php

namespace RZP\Models\Merchant;

use Config;
use RZP\Models\Payment\Gateway;
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
    const MID_RBL_RETAIL_ASSETS     = 'D83Pk7NqU6URGe';

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

    const MID_CLEARTAX         = 'AGQJfLbWcmjxDX';
    const MID_APARTMENTADDA    = '9NVPPQuTqF4cYx';
    const MID_INVEZTA          = '8YQygO7pzP3Gut';

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
    ];

    public static function checkZohoHeaders(Headers $headers)
    {
        $expectedHeader = Config::get('applications.zoho.header');

        return ($expectedHeader === $headers->get(self::X_AGGREGATOR_HEADER));
    }
}
