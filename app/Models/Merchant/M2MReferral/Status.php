<?php


namespace RZP\Models\Merchant\M2MReferral;


use RZP\Models\Merchant\M2MReferral\Entity;
use RZP\Exception\BadRequestValidationFailureException;

class Status
{
    //metadata

    //status
    const SIGN_UP           = 'signup';
    const MTU               = 'mtu';
    const SIGNUP_EVENT_SENT = 'signup_event_sent';
    const MTU_EVENT_SENT    = 'mtu_event_sent';
    const REWARDED          = 'rewarded';
    /*
     * Allowed next statuses mapping
     */
    const ALLOWED_NEXT_STATUSES_MAPPING = [
        self::SIGN_UP           => [self::SIGNUP_EVENT_SENT,self::MTU],
        self::SIGNUP_EVENT_SENT => [self::MTU],
        self::MTU               => [self::MTU_EVENT_SENT,self::REWARDED],
        self::MTU_EVENT_SENT    => [self::REWARDED],

    ];
    const VALID_STATUSES                = [
        self::SIGN_UP,
        self::SIGNUP_EVENT_SENT,
        self::MTU_EVENT_SENT,
        self::MTU,
        self::REWARDED,
    ];


    const VALID_REFERRER_STATUS = [
        self::REWARDED,
    ];

    public static function isValidStatusTransition($current, $next)
    {
        return (empty($current) or in_array($next, self::ALLOWED_NEXT_STATUSES_MAPPING[$current], true) === true);
    }


    public static function isValidStatus($type)
    {
        return (in_array($type, self::VALID_STATUSES, true));
    }

    public static function validateStatus($status)
    {
        if (self::isValidStatus($status) === false)
        {
            throw new BadRequestValidationFailureException(
                'Not a valid Referral status ' . $status,
                Entity::STATUS,
                [
                    Entity::STATUS => $status
                ]);
        }
    }


    public static function isValidReferrerStatus($type)
    {
        return (in_array($type, self::VALID_STATUSES, true));
    }

    public static function validateReferrerStatus($status)
    {
        if (empty($status) === false and self::isValidReferrerStatus($status) === false)
        {
            throw new BadRequestValidationFailureException(
                'Not a valid Referral status ' . $status,
                Entity::REFERRER_STATUS,
                [
                    Entity::REFERRER_STATUS => $status
                ]);
        }
    }
}
