<?php

namespace Constants;

final class URL
{
    const TXN_CREATE_URL = 'transactions';

    const TXN_CREATE_METHOD = 'post';

    const TXN_REFUND_URL = 'transactions/{id}/refund';

    const TXN_REFUND_METHOD = 'post';

    const TXN_CAPTURE_URL = 'transactions/{id}/capture';

    const TXN_CAPTURE_METHOD = 'post';

    const TXN_JSONP_URL = 'transactions/jsonp';

    const TXN_JSONP_METHOD = 'get';

    const TXN_RETRIEVE_URL = 'transactions/{param?}';

    const TXN_RETRIEVE_METHOD = 'get';

    const TXN_CALLBACK_URL = 'transactions/callback';

    const TXN_CALLBACK_METHOD = 'post';

    protected static $doNotLogURLs = array(
        self::TXN_JSONP_URL);

    public static function getDoNotLogURLs()
    {
        return self::$doNotLogURLs;
    }
}