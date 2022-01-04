<?php


namespace RZP\Models\Merchant\M2MReferral;


class Constants
{

    const REFERRAL_AMOUNT          = 'referral_amount';
    const REFERRAL_AMOUNT_CURRENCY = 'referral_amount_currency';
    const INR                      = 'INR';
    const MAX_ALLOWED_REFERRALS    = 'max_allowed_referrals';
    const CAN_REFER                = 'can_refer';
    //UTM Patameters
    const LAST_ATTRIB_UTM       = 'lastAttribUtm';
    const RZP_UTM               = 'rzp_utm';
    const ATTRIBUTIONS          = "attributions";
    const UTM_SOURCE            = "utmSource";
    const UTM_CAMPAIGN          = 'utmCampaign';
    const UTM_CONTENT           = 'utmContent';
    const UTM_MEDIUM            = 'utmMedium';
    const REFERRAL_CODE         = 'referralCode';
    const FIRST_NAME            = 'firstName';
    const FRIEND_BUY_PARAMETERS = [
        self::UTM_SOURCE,
        self::UTM_CAMPAIGN,
        self::UTM_CONTENT,
        self::UTM_MEDIUM,
        self::REFERRAL_CODE,
    ];

    const MOBILE     = "mobile";
    const FRIEND_BUY = "friendbuy";
    //metadata
    const MTU_EVENT_ID     = 'mtu_event_id';
    const REWARD_ID        = 'reward_id';
    const SIGN_UP_EVENT_ID = 'sign_up_event_id';

    const M2M_REFERRAL_SUCCESS_POPUP_COUNT    = 'M2M_REFERRAL_SUCCESS_POPUP_COUNT';
    const M2M_REFERRAL_MIN_TRANSACTION_AMOUNT = 'M2M_REFERRAL_MIN_TRANSACTION_AMOUNT';
    const REFERRER                            = "referrer";
    const REFEREE                             = "referee";
    const REFEREE_DETAILS                     = "referee";
    const REFERRAL_LINK                       = 'referral_link';

    const CREDITS_RECEIVED = 'creditsReceived';
    const REFERRAL_MID     = "referralMID";
    const REFERRAL_COUNT   = "referralCount";

    const REFERRAL_COUNT_COUPON_MAPPING = [
        0 => \RZP\Models\Coupon\Constants::M2M_ADVOCATE1,
        1 => \RZP\Models\Coupon\Constants::M2M_ADVOCATE2,
        2 => \RZP\Models\Coupon\Constants::M2M_ADVOCATE3,
        3 => \RZP\Models\Coupon\Constants::M2M_ADVOCATE4,
        4 => \RZP\Models\Coupon\Constants::M2M_ADVOCATE5,
    ];
}
