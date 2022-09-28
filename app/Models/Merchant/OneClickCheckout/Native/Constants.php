<?php

namespace RZP\Models\Merchant\OneClickCheckout\Native;

use RZP\Models\Merchant\OneClickCheckout;

final class Constants
{
    // order status
    const HOLD          = 'hold';
    const CANCELLED     = 'cancelled';
    const APPROVED      = 'approved';

    // Order status key
    const STATUS        = 'status';
    const POST          = 'POST';
    const ORDER_STATUS_UPDATE_ENDPOINT = '/wp-json/wc/v3/orders/';

    const ORDER_ID_KEY      = 'order_id';

    const ACTION_STATUS_MAPPING = [
        OneClickCheckout\Constants::APPROVE     => self::APPROVED,
        OneClickCheckout\Constants::HOLD        => self::HOLD,
        OneClickCheckout\Constants::CANCEL      => self::CANCELLED
    ];
}
