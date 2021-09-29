<?php

namespace RZP\Models\Coupon;

use RZP\Diag\EventCode;
use RZP\Models\Merchant\RazorxTreatment;

class Constants {

    const MTU_COUPON            = 'OFFERMTU2';

    const EXPERIMENT_NAME       = 'experiment_name';
    const SUCCESS_EVENT_CODE    = 'success_event_code';
    const FAILED_EVENT_CODE     = 'failed_event_code';

    const COUPON_CONFIG = [
        Constants::MTU_COUPON => [
            self::EXPERIMENT_NAME       => RazorxTreatment::MTU_COUPON_CODE,
            self::SUCCESS_EVENT_CODE    => EventCode::APPLY_COUPON_CODE_SUCCESS,
            self::FAILED_EVENT_CODE     => EventCode::APPLY_COUPON_CODE_FAILED,
        ],
        'default' => [
            self::SUCCESS_EVENT_CODE    => EventCode::SIGNUP_APPLY_COUPON_CODE_SUCCESS,
            self::FAILED_EVENT_CODE     => EventCode::SIGNUP_APPLY_COUPON_CODE_FAILED,
        ]
    ];
}
