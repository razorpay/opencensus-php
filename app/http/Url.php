<?php

namespace Http;

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

    const TXN_RETRIEVE_BY_ID_URL = 'transactions/{id}';
    const TXN_RETRIEVE_MULTIPLE_URL = 'transactions';

    const TXN_RETRIEVE_URL = 'transactions/{param?}';
    const TXN_RETRIEVE_METHOD = 'get';

    const TXN_CALLBACK_URL = 'transactions/callback/{id}';
    const TXN_CALLBACK_METHOD = 'post';

    const MERCHANT_REGISTER = 'merchants';
    const MERCHANT_REGISTER_METHOD = 'post';

    protected $url = array(
        'transaction_create' => array(
            'method' => 'post',
            'url' => 'transactions',
            'action' => 'TransactionController@postIndex'));

    protected static $doNotLogURLs = array(
        self::TXN_JSONP_URL);

    public static function getDoNotLogURLs()
    {
        return self::$doNotLogURLs;
    }

    public static function callback($id)
    {
        $urlSegment = \Http\URL::TXN_CALLBACK_URL;

        $pos = strrpos($urlSegment, '/');

        $urlSegment = substr($urlSegment, 0, $pos);

        $urlSegment .= '/' . $txn->getPublicId();

        $scheme = \Request::getScheme().'://';
        $key = \BasicAuth::getPublicKey();
        $host = \Request::getHost();

        $callbackUrl = $scheme . $key . '@' . $host . '/' . $urlSegment;

        $callbackData['callbackUrl'] = $callbackUrl;
    }

    public static function buildUrl($segment, $arg = null, $auth = null)
    {
        if (isset($arg))
        {
            $pos = strrpos($urlSegment, '/');

            $urlSegment = substr($urlSegment, 0, $pos);

            $urlSegment .= '/' . $txn->getPublicId();


        }
    }
}