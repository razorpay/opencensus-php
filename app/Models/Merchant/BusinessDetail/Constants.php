<?php


namespace RZP\Models\Merchant\BusinessDetail;


class Constants
{
    //website_details
    const ABOUT                      = 'about';
    const CONTACT                    = 'contact';
    const PRIVACY                    = 'privacy';
    const TERMS                      = 'terms';
    const REFUND                     = 'refund';
    const PRICING                    = 'pricing';
    const LOGIN                      = 'login';
    const CANCELLATION               = 'cancellation';
    const COMMENTS                   = 'comments';
    const PHYSICAL_STORE             = 'physical_store';
    const SOCIAL_MEDIA               = 'social_media';
    const WEBSITE_OR_APP             = 'live_website_or_app';
    const OTHERS                     = 'others';
    const WEBSITE_NOT_READY          = 'website_not_ready';
    const WEBSITE_COMPLIANCE_CONSENT = 'website_compliance_consent';
    const WEBSITE_PRESENT            = 'website_present';
    const ANDROID_APP_PRESENT        = 'android_app_present';
    const IOS_APP_PRESENT            = 'ios_app_present';
    const OTHERS_PRESENT             = 'others_present';

    //app_urls
    const PLAYSTORE_URL    = 'playstore_url';
    const APPSTORE_URL     = 'appstore_url';

    // transaction app url constants
    const TXN_URL = 'txn_url';
    const PLAYSTORE_URL_PREFIX = 'https://play.google.com/store/apps/details?id=';
    const TXN_PLAYSTORE_URL_COUNT_LIMIT = 10;
    const TXN_PLAYSTORE_URLS = 'txn_playstore_urls';

    const APP_URLS_FIELDS = [
        self::PLAYSTORE_URL,
        self::APPSTORE_URL
    ];

    const WEBSITE_DETAILS_FIELDS = [
        self::ABOUT,
        self::CONTACT,
        self::PRIVACY,
        self::TERMS,
        self::REFUND,
        self::PRICING,
        self::LOGIN,
        self::CANCELLATION,
        self::COMMENTS,
        self::PHYSICAL_STORE,
        self::SOCIAL_MEDIA,
        self::WEBSITE_OR_APP,
        self::OTHERS,
        self::WEBSITE_NOT_READY,
        self::WEBSITE_COMPLIANCE_CONSENT,
        self::WEBSITE_PRESENT,
        self::ANDROID_APP_PRESENT,
        self::IOS_APP_PRESENT,
        self::OTHERS_PRESENT,
    ];
}
