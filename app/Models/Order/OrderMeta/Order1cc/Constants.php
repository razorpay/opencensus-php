<?php

namespace RZP\Models\Order\OrderMeta\Order1cc;

final class Constants
{
    const ORDER_ACTION_MUTEX_LOCK_TIMEOUT                 = '60';
    const ORDER_ACTION_MUTEX_RETRY_COUNT                  = '2';

    const ACTION = 'action';
    const PLATFORM = 'platform';
    // Possible action values
    const APPROVE                   = 'approve';
    const HOLD                      = 'hold';
    const CANCEL                    = 'cancel';
    //Review Status In Progress Values
    const APPROVAL_INITIATED        = 'approval_initiated';
    const CANCEL_INITIATED          = 'cancel_initiated';
    const HOLD_INITIATED            = 'hold_initiated';
    const APPROVED                  = 'approved';
    const CANCELED                  = 'canceled';


    const COUNT     = 'count';
    const ITEMS     = 'items';
    const ENTITY    = 'entity';
    const HAS_MORE  = 'has_more';


    const ACTION_INTERMEDIATE_REVIEW_STATUS_MAPPING = [
        self::APPROVE     => self::APPROVAL_INITIATED,
        self::HOLD        => self::HOLD_INITIATED,
        self::CANCEL      => self::CANCEL_INITIATED
    ];

    const ACTION_FINAL_REVIEW_STATUS_MAPPING = [
        self::APPROVE     => self::APPROVED,
        self::HOLD        => self::HOLD,
        self::CANCEL      => self::CANCELED
    ];

    const REVIEW_STATUS_ACTION_MAPPING = [
        self::APPROVAL_INITIATED    => self::APPROVE,
        self::HOLD_INITIATED        => self::HOLD,
        self::CANCEL_INITIATED      => self::CANCEL
    ];

    const ACTION_STATUS            = 'status';
    const ACTION_ERROR             = 'error';
    const ACTION_ERROR_CODE        = 'code';
    const ACTION_ERROR_DATA        = 'data';

    const SUCCESS             = 'success';
    const FAILURE             = 'failure';

    const BAD_REQUEST_ACTION_TAKEN_BY_SOMEONE_CODE      = 'BAD_REQUEST_ACTION_TAKEN_BY_SOMEONE';
    const BAD_REQUEST_ACTION_ON_ORDER_IN_PROGRESS_CODE  = 'BAD_REQUEST_ACTION_ON_ORDER_IN_PROGRESS';
    const BAD_REQUEST_ORDER_NOT_FOUND_CODE              = 'BAD_REQUEST_ORDER_NOT_FOUND';

}
