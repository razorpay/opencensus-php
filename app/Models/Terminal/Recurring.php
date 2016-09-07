<?php

namespace RZP\Models\Terminal;

class Recurring
{
    // Terminal to be used for non recurring payments
    const NON_RECURRING     = 0;

    // Recurring terminal for first transaction
    const RECURRING_3DS     = 1;

    // Recurring terminal for non 3ds transaction after first succesfull payment
    const RECURRING_N3DS    = 2;
}