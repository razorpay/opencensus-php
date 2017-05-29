<?php

namespace RZP\Gateway\Wallet\Mpesa;

class Constants
{
    // Request constants
    const NARRATION        = 'Razorpay';
    const REFUND_NARRATION = 'refund';
    const CHANNEL_ID       = 11;
    const ENTITY_TYPE_ID   = 80;
    const TO_ENTITY_TYPE   = 85;
    const FULL_REVERSAL    = 'F';
    const PARTIAL_REVERSAL = 'P';
    const COMMAND_ID       = 'O';
    const CMDID            = 111;

    // Soap constants
    const USER_ID          = 'userId';
    const PASSWORD         = 'password';

    const WALLET           = 'W';
}
