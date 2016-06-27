<?php

namespace EE\Error;

class Action
{
    const RETRY = 'RETRY';
    const TOPUP = 'TOPUP';

    const BAD_REQUEST_PAYMENT_OTP_INCORRECT = self::RETRY;

    const BAD_REQUEST_PAYMENT_WALLET_INSUFFICIENT_BALANCE = self::TOPUP;

    const BAD_REQUEST_PAYMENT_WALLET_USER_DOES_NOT_EXIST = self::TOPUP;
}