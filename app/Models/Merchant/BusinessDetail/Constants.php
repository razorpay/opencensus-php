<?php


namespace RZP\Models\Merchant\BusinessDetail;


class Constants
{
    //website_details
    const ABOUT           = 'about';
    const CONTACT         = 'contact';
    const PRIVACY         = 'privacy';
    const TERMS           = 'terms';
    const REFUND          = 'refund';
    const PRICING         = 'pricing';
    const LOGIN           = 'login';
    const CANCELLATION    = 'cancellation';
    const COMMENTS        = 'comments';

    //app_urls
    const PLAYSTORE_URL    = 'playstore_url';
    const APPSTORE_URL     = 'appstore_url';

    // transaction app url constants
    const TXN_URL = 'txn_url';
    const PLAYSTORE_URL_PREFIX = 'https://play.google.com/store/apps/details?id=';
    const TXN_PLAYSTORE_URL_COUNT_LIMIT = 10;
    const TXN_PLAYSTORE_URLS = 'txn_playstore_urls';
}
