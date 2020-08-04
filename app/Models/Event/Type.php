<?php

namespace RZP\Models\Event;

/**
 * The events whether they are enabled or disabled are store in bit format.
 * See this link for a guide on bitwise operations:
 * http://stackoverflow.com/questions/47981/how-do-you-set-clear-and-toggle-a-single-bit-in-c-c
 */
class Type
{
    const PAYMENT_AUTHORIZED        = 'payment.authorized';
    const PAYMENT_FAILED            = 'payment.failed';
    const PAYMENT_CAPTURED          = 'payment.captured';
    const ORDER_PAID                = 'order.paid';
    const INVOICE_PAID              = 'invoice.paid';
    const VPA_EDITED                = 'vpa.edited';
    const P2P_CREATED               = 'p2p.created';
    const P2P_REJECTED              = 'p2p.rejected';
    const P2P_TRANSFERRED           = 'p2p.transferred';
    const SUBSCRIPTION_ACTIVATED    = 'subscription.activated';
    const SUBSCRIPTION_PENDING      = 'subscription.pending';
    const SUBSCRIPTION_HALTED       = 'subscription.halted';
    const SUBSCRIPTION_CHARGED      = 'subscription.charged';
    const SUBSCRIPTION_CANCELLED    = 'subscription.cancelled';
    const SUBSCRIPTION_COMPLETED    = 'subscription.completed';
    // const SUBSCRIPTION_EXPIRED      = 'subscription.expired';
    const SUBSCRIPTION_AUTHENTICATED = 'subscription.authenticated';
    const SUBSCRIPTION_PAUSED        = 'subscription.paused';
    const SUBSCRIPTION_RESUMED       = 'subscription.resumed';

    const PAYMENT_CREATED           = 'payment.created';
}
