<?php

namespace RZP\Gateway\Wallet\Amazonpay;

/**
 * The list of config keys used by the AmazonPay SDK
 * Class Config
 * @package RZP\Gateway\Wallet\Amazonpay
 */
final class Config
{
    const MERCHANT_ID         = 'merchant_id';
    const ACCESS_KEY          = 'access_key';
    const SECRET_KEY          = 'secret_key';
    const BASE_URL            = 'base_url';
    const CURRENCY_CODE       = 'currency_code';
    const SANDBOX             = 'sandbox';
    const PLATFORM_ID         = 'platform_id';
    const APPLICATION_NAME    = 'application_name';
    const APPLICATION_VERSION = 'application_version';
    const HANDLE_THROTTLE     = 'handle_throttle';
}
