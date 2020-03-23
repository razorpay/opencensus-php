<?php

namespace RZP\Gateway\Wallet\Amazonpay;

final class Constant
{
    /**
     * Transaction Timeout sent to Amazon, Unit in seconds
     */
    const TIMEOUT        = 300;
    const STORE          = 'Razorpay';
    const PAYMENT_DOMAIN = 'IN_INR';
    const ORDER_REF_ID   = 'OrderReferenceId';

    // When specific refund details are not present in refund/verify Refund response
    const REFUND_NOT_FOUND_ERROR = 'Refund not found in response';
}
