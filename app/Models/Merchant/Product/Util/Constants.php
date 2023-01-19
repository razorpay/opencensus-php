<?php

namespace RZP\Models\Merchant\Product\Util;

class Constants
{
    const CHECKOUT        = 'checkout';
    const ACCOUNT_CONFIG  = 'account_config';
    const REQUIREMENTS    = 'requirements';
    const PAYMENT_CAPTURE = 'payment_capture';
    const PAYMENT_CONFIG  = 'payment_config';
    const METHODS         = 'methods';
    const CONFIGURATION   = 'configuration';
    const NOTIFICATIONS   = 'notifications';
    const SETTLEMENTS     = 'settlements';
    const BANK_DETAILS    = 'bank_details';
    const FLASH_CHECKOUT  = 'flash_checkout';
    const FEATURES        = 'features';
    const REFUND          = 'refund';
    const ACCOUNT_ID      = 'account_id';
    const REQUESTED_AT    = 'requested_at';
    const TNC_ACCEPTED    = 'tnc_accepted';
    const OTP             = 'otp';
    const IP              = 'ip';

    const REQUESTED_CONFIGURATION = 'requested_configuration';
    const ACTIVE_CONFIGURATION    = 'active_configuration';

    //Feature names
    const NOFLASHCHECKOUT = 'noflashcheckout';



    //Notifications input constants
    const WHATSAPP = 'whatsapp';
    const SMS      = 'sms';
    const EMAIL    = 'email';

    //Checkout Fields
    const THEME_COLOR = 'theme_color';

    //Checkout input constants
    const LOGO = 'logo';


    //Settlements input constants
    const IFSC_CODE        = 'ifsc_code';
    const ACCOUNT_NUMBER   = 'account_number';
    const BENEFICIARY_NAME = 'beneficiary_name';

    //payment capture input constants
    const MODE                    = 'mode';
    const AUTOMATIC               = 'automatic';
    const MANUAL                  = 'manual';
    const REFUND_SPEED            = 'refund_speed';
    const AUTOMATIC_EXPIRY_PERIOD = 'automatic_expiry_period';
    const MANUAL_EXPIRY_PERIOD    = 'manual_expiry_period';
    const CAPTURE                 = 'capture';
    const CAPTURE_OPTIONS         = 'capture_options';
    const CONFIG                  = 'config';
    const LATE_AUTH               = 'late_auth';
    const PAYMENT_CAPTURE_CONFIGS = [self::LATE_AUTH];

    // payment methods constant
    const NETBANKING = 'netbanking';
    const RETAIL     = 'retail';
    const CORPORATE  = 'corporate';
    const CARDS      = 'cards';
    const UPI        = 'upi';
    const WALLET     = 'wallet';
    const PAYLATER   = 'paylater';
    const INSTRUMENT = 'instrument';
    const TYPE       = 'type';
    const BANK       = 'bank';
    const EMI        = 'emi';
    const ISSUER     = 'issuer';

    // Product request constants
    const STATUS    = 'status';
    const ENABLED   = 'enabled';
    const ACTIVATED = 'activated';
    const REQUESTED = 'requested';
    const COMPLETED = 'completed';
    const FAILED    = 'failed';

    // Configuration types
    const GENERAL                = 'general';
    const PAYMENT_METHODS        = 'payment_methods';
    const PAYMENT_METHODS_UPDATE = 'payment_methods_update';

    // Tnc acceptance
    const TNC = 'tnc';

    // Wallets
    const AIRTELMONEY   = "airtelmoney";
    const AMAZONPAY     = "amazonpay";
    const FREECHARGE    = "freecharge";
    const JIOMONEY      = "jiomoney";
    const SBIBUDDY      = "sbibuddy";
    const MPESA         = "mpesa";
    const OLAMONEY      = "olamoney";
    const PAYZAPP       = "payzapp";
    const PHONEPE       = "phonepe";
    const PHONEPESWITCH = "phonepeswitch";
    const MOBIKWIK      = "mobikwik";
    const PAYTM         = "paytm";
    const PAYUMONEY     = "payumoney";

    // OTP Verificaiton log
    const CONTACT_MOBILE = 'contact_mobile';
    const REFERENCE_NUMBER = 'external_reference_number';
    const OTP_SUBMISSION_TIMESTAMP =  'otp_submission_timestamp';
    const OTP_VERIFICATION_TIMESTAMP =  'otp_verification_timestamp';

    public static $wallets = [
        self::AIRTELMONEY,
        self::AMAZONPAY,
        self::FREECHARGE,
        self::JIOMONEY,
        self::MOBIKWIK,
        self::MPESA,
        self::OLAMONEY,
        self::PAYTM,
        self::PAYZAPP,
        self::PAYUMONEY,
        self::PHONEPE,
        self::PHONEPESWITCH,
        self::SBIBUDDY,
    ];

    // UPI
    const GOOGLE_PAY = "google_pay";

    public static $upiCodes = [
        self::UPI,
        self::GOOGLE_PAY
    ];

    // paylater
    const EPAYLATER = "epaylater";
    const GETSIMPL  = "getsimpl";

    public static $paylaterCodes = [
        self::EPAYLATER,
        self::GETSIMPL
    ];

    //emi
    const CARDLESS_EMI = "cardless_emi";
    const CARD_EMI     = "card_emi";
    const ZESTMONEY    = "zestmoney";
    const EARLYSALARY  = "earlysalary";
    const DEBIT        = "debit";
    const CREDIT       = "credit";
    const PARTNER      = "partner";

    public static $cardlessEmiCodes = [
        self::ZESTMONEY,
        self::EARLYSALARY
    ];

    public static $cardEmiCodes = [
        self::DEBIT,
        self::CREDIT
    ];

    // card networks
    const DICL        = 'dicl';
    const VISA        = 'visa';
    const RUPAY       = 'rupay';
    const AMEX        = 'amex';
    const MASTERCARD  = 'mastercard';
    const MAESTRO     = 'maestro';

    public static $cardNetworks = [
        self::DICL,
        self::VISA,
        self::RUPAY,
        self::AMEX,
        self::MASTERCARD,
        self::MAESTRO
    ];

    public static $paymentMethodInstrumentPrefix = [
        self::NETBANKING  => "pg.netbanking.",
        self::WALLET      => "pg.wallet.",
        self::UPI         => "pg.upi.",
        self::PAYLATER    => "pg.paylater.",
        self::CARDS       => "pg.cards."
    ];

    const CONFIG_UPDATE_FLOW_ENABLED = 'CONFIG_UPDATE_FLOW_ENABLED';

    const PRODUCT_NAME = 'product_name';
}
