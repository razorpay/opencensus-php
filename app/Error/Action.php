<?php

namespace RZP\Error;

class Action
{
    const RETRY = 'RETRY';
    const TOPUP = 'TOPUP';

    const BAD_REQUEST_PAYMENT_OTP_INCORRECT = self::RETRY;

    const BAD_REQUEST_PAYMENT_WALLET_INSUFFICIENT_BALANCE = self::TOPUP;
}
