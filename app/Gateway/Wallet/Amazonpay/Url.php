<?php

namespace RZP\Gateway\Wallet\Amazonpay;

/**
 * Both production and UAT urls are the same for Amazon Pay.
 * Mode is identified on their end based on isSandbox parameter.
 * @see README.md for documentation links
 *
 * Class Url
 * @package RZP\Gateway\Wallet\Amazonpay
 */
final class Url
{
    // SDK which we use, requires hostname while creating signature
    const SERVICE_HOSTNAME  = 'amazonpay.amazon.in';

    const TEST_DOMAIN       = 'https://amazonpay.amazon.in';
    const LIVE_DOMAIN       = 'https://amazonpay.amazon.in';

    const AUTHORIZE         = '/initiatePayment';
}
