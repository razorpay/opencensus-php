<?php


namespace RZP\Models\Merchant\M2MReferral\FriendBuy;


class Constants
{
    const URL                        = 'url';
    const APPLICATIONS_FRIEND_BUY    = 'applications.friend_buy';
    const M2M_FRIEND_BUY_CAMPAIGN_ID = 'M2M_FRIEND_BUY_CAMPAIGN_ID';
    const SUCCESS                    = 'success';
    const TIMEOUT                    = 'timeout';
    const CONNECT_TIMEOUT            = 'connect_timeout';

    const KEY                     = 'key';
    const SECRET                  = 'secret';
    const AUTH                    = 'auth';
    const HASH_KEY                = 'hash_key';
    const WEBHOOK                 = 'webhook';
    const X_FRIENDBUY_HMAC_SHA256 = 'x-friendbuy-hmac-sha256';
    const SHA256                  = 'sha256';
    const CONFIG                  = 'config';

    const PAYLOAD          = 'payload';
    const PATH             = 'path';
    const RESPONSE         = 'response';
    const BODY             = 'body';
    const HEADERS          = 'headers';
    const CONTENT_TYPE     = 'Content-Type';
    const APPLICATION_JSON = 'application/json';
    const ACCEPT           = 'Accept';

    // auth response
    const EXPIRES    = 'expires';
    const TOKEN      = 'token';
    const TOKEN_TYPE = 'tokenType';

    //event
    const EMAIL           = 'email';
    const CAMPAIGN_ID     = 'campaignId';
    const CUSTOMER_ID     = 'customerId';
    const AMOUNT          = 'amount';
    const ORDER_ID        = 'orderId';
    const CURRENCY        = 'currency';
    const ID              = 'id';
    const IS_NEW_CUSTOMER = 'isNewCustomer';
    const MTU             = 'mtu';

    //error message
    const REFERENCE = 'reference';
    const CODE      = 'code';
    const MESSAGE   = 'message';
    const ERROR     = 'error';

    //response
    const EVENT_ID   = 'eventId';
    const CREATED_ON = 'createdOn';
    const LINK       = 'link';

    //reward Validation request
    const EVENT_TYPE     = 'eventType';
    const RECIPIENT_TYPE = 'recipientType';
    const ADVOCATE       = 'advocate';
    const EVENT          = 'event';
    const FRIEND         = 'friend';
    const CUSTOMER       = 'customer';
    const ACTOR          = 'actor';

    //reward request
    const TYPE      = 'type';
    const DATA      = 'data';
    const REWARD_ID = 'rewardId';
}
