<?php

namespace EE\Error;

class Action
{
    const RETRY = 'RETRY';

    const BAD_REQUEST_PAYMENT_OTP_INCORRECT = self::RETRY;
}