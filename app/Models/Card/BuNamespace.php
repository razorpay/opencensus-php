<?php

namespace RZP\Models\Card;

class BuNamespace
{
    // bu_namespaces
    const RAZORPAYX_NON_SAVED_CARDS = 'razorpayx_non_saved_cards';
    const RAZORPAYX_TOKEN_PAN       = 'razorpayx_token_pan';
    const PAYMENTS_TOKEN_PAN        = 'payments_token_pan';
    const RAZORPAYX_NODAL_CERTS     = 'razorpayx_nodal_certs';
    const BU_NAMESPACE_MY           = 'payments_sea_my';

    const BU_NAMESPACE = [
        'MY' => self::BU_NAMESPACE_MY
    ];
}
