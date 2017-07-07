<?php

namespace RZP\Error;

class Action
{
    const RETRY = 'RETRY';
    const TOPUP = 'TOPUP';

    // @TODO : WAIT
    const WAIT  = 'WAIT';

    const BAD_REQUEST_PAYMENT_OTP_INCORRECT = self::RETRY;

    const BAD_REQUEST_PAYMENT_WALLET_INSUFFICIENT_BALANCE = self::TOPUP;

    const BAD_REQUEST_PAYMENT_PENDING_AUTHORIZATION = self::WAIT;
}
