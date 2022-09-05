<?php

namespace RZP\Models\Coupon;

use RZP\Diag\EventCode;
use RZP\Models\Merchant\RazorxTreatment;

class Constants
{

    const MTU_COUPON    = 'OFFERMTU3';
    const M2M_FRIEND    = 'M2MREFEREE';
    const M2M_ADVOCATE1 = 'ADVOCATE1';
    const M2M_ADVOCATE2 = 'ADVOCATE2';
    const M2M_ADVOCATE3 = 'ADVOCATE3';
    const M2M_ADVOCATE4 = 'ADVOCATE4';
    const M2M_ADVOCATE5 = 'ADVOCATE5';

    const EXPERIMENT_NAME         = 'experiment_name';
    const SUCCESS_EVENT_CODE      = 'success_event_code';
    const FAILED_EVENT_CODE       = 'failed_event_code';
    const APPLY_PROMOTION_PRICING = 'apply_promotion_pricing';
    const IS_SYSTEM_COUPON        = 'is_system_coupon';
    const EMAIL = 'email';
    const SLACK = 'slack';

    const COUPON_CONFIG = [
        Constants::M2M_FRIEND    => [
            self::SUCCESS_EVENT_CODE      => EventCode::M2M_APPLY_COUPON_CODE_SUCCESS,
            self::FAILED_EVENT_CODE       => EventCode::M2M_APPLY_COUPON_CODE_FAILED,
            self::APPLY_PROMOTION_PRICING => false,
            self::IS_SYSTEM_COUPON => true
        ],
        Constants::M2M_ADVOCATE1 => [
            self::SUCCESS_EVENT_CODE      => EventCode::M2M_APPLY_COUPON_CODE_SUCCESS,
            self::FAILED_EVENT_CODE       => EventCode::M2M_APPLY_COUPON_CODE_FAILED,
            self::APPLY_PROMOTION_PRICING => false,
            self::IS_SYSTEM_COUPON => true
        ],
        Constants::M2M_ADVOCATE2 => [
            self::SUCCESS_EVENT_CODE      => EventCode::M2M_APPLY_COUPON_CODE_SUCCESS,
            self::FAILED_EVENT_CODE       => EventCode::M2M_APPLY_COUPON_CODE_FAILED,
            self::APPLY_PROMOTION_PRICING => false,
            self::IS_SYSTEM_COUPON => true
        ],
        Constants::M2M_ADVOCATE3 => [
            self::SUCCESS_EVENT_CODE      => EventCode::M2M_APPLY_COUPON_CODE_SUCCESS,
            self::FAILED_EVENT_CODE       => EventCode::M2M_APPLY_COUPON_CODE_FAILED,
            self::APPLY_PROMOTION_PRICING => false,
            self::IS_SYSTEM_COUPON => true
        ],
        Constants::M2M_ADVOCATE4 => [
            self::SUCCESS_EVENT_CODE      => EventCode::M2M_APPLY_COUPON_CODE_SUCCESS,
            self::FAILED_EVENT_CODE       => EventCode::M2M_APPLY_COUPON_CODE_FAILED,
            self::APPLY_PROMOTION_PRICING => false,
            self::IS_SYSTEM_COUPON => true
        ],
        Constants::M2M_ADVOCATE5 => [
            self::SUCCESS_EVENT_CODE      => EventCode::M2M_APPLY_COUPON_CODE_SUCCESS,
            self::FAILED_EVENT_CODE       => EventCode::M2M_APPLY_COUPON_CODE_FAILED,
            self::APPLY_PROMOTION_PRICING => false,
            self::IS_SYSTEM_COUPON => true
        ],
        Constants::MTU_COUPON    => [
            self::EXPERIMENT_NAME         => RazorxTreatment::MTU_COUPON_CODE,
            self::SUCCESS_EVENT_CODE      => EventCode::APPLY_COUPON_CODE_SUCCESS,
            self::FAILED_EVENT_CODE       => EventCode::APPLY_COUPON_CODE_FAILED,
            self::APPLY_PROMOTION_PRICING => false,
            self::IS_SYSTEM_COUPON => true
        ],
        'default'                => [
            self::SUCCESS_EVENT_CODE      => EventCode::SIGNUP_APPLY_COUPON_CODE_SUCCESS,
            self::FAILED_EVENT_CODE       => EventCode::SIGNUP_APPLY_COUPON_CODE_FAILED,
            self::APPLY_PROMOTION_PRICING => true,
            self::IS_SYSTEM_COUPON => false
        ]
    ];
}
