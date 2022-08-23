<?php

namespace RZP\Error;

class Action
{
    // These actions are meant to inform checkout about the next action which it should take.
    const RETRY = 'RETRY';

    const TOPUP = 'TOPUP';

    const PENDING = 'PENDING';

    const BAD_REQUEST_PAYMENT_OTP_INCORRECT = self::RETRY;

    const BAD_REQUEST_PAYMENTS_INVALID_OTP_TRY_NEW = self::RETRY;

    const BAD_REQUEST_PAYMENT_WALLET_INSUFFICIENT_BALANCE = self::TOPUP;

    const BAD_REQUEST_PAYMENT_PENDING_AUTHORIZATION = self::PENDING;
}
