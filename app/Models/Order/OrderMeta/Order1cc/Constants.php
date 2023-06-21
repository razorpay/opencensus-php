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

   const COD_AUTOMATION_REVIEW_EMAIL = "automation_intelligence@razorpay.com";

   const AUTOMATION_FLAG = 'automation';

   const MANUAL_FLAG = 'manual';


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

    const PL_AWAITED = "awaited";
    const PL_MAPPED_AWAITED = "msg_awaited";
    const PL_SENT = "sent";
    const PL_MAPPED_SENT = "msg_sent";
    const PL_FAILED = "failed";
    const PL_MAPPED_FAILED = "msg_failed";
    const PL_EXPIRED = "expired";
    const PL_MAPPED_EXPIRED = "pl_expired";
    const PL_CANCELLED = "cancelled";
    const PL_MAPPED_CANCELLED = "pl_cancelled";
    const PL_PAID = "paid";
    const PL_MAPPED_PAID = "pl_paid";
    const MAGIC_PAYMENT_LINK_STATUS_MAPPING = [
        self::PL_AWAITED => self::PL_MAPPED_AWAITED,
        self::PL_SENT => self::PL_MAPPED_SENT,
        self::PL_FAILED => self::PL_MAPPED_FAILED,
        self::PL_EXPIRED => self::PL_MAPPED_EXPIRED,
        self::PL_CANCELLED => self::PL_MAPPED_CANCELLED,
        self::PL_PAID => self::PL_MAPPED_PAID
    ];
    const MAGIC_PAYMENT_LINK_REVERSE_STATUS_MAPPING = [
        self::PL_MAPPED_AWAITED => self::PL_AWAITED,
        self::PL_MAPPED_SENT => self::PL_SENT,
        self::PL_MAPPED_FAILED => self::PL_FAILED,
        self::PL_MAPPED_EXPIRED => self::PL_EXPIRED,
        self::PL_MAPPED_CANCELLED => self::PL_CANCELLED,
        self::PL_MAPPED_PAID => self::PL_PAID
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
    const BAD_REQUEST_MERCHANT_DISABLED_MANUAL_REVIEW   = 'BAD_REQUEST_MERCHANT_DISABLED_MANUAL_REVIEW';

}
