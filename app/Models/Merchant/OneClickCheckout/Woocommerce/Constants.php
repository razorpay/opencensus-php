<?php

namespace RZP\Models\Merchant\OneClickCheckout\Woocommerce;

use RZP\Models\Merchant\OneClickCheckout;

final class Constants
{
    // order status
    const HOLD          = 'on-hold';
    const CANCELLED     = 'cancelled';
    const PROCESSING    = 'processing';

    // Order status key
    const STATUS        = 'status';
    const POST          = 'POST';
    const ORDER_STATUS_UPDATE_ENDPOINT = '/wp-json/wc/v3/orders/';

    const ACTION_STATUS_MAPPING = [
        OneClickCheckout\Constants::APPROVE     => self::PROCESSING,
        OneClickCheckout\Constants::HOLD        => self::HOLD,
        OneClickCheckout\Constants::CANCEL      => self::CANCELLED
    ];
}
