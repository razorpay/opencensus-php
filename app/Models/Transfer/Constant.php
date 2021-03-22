<?php

namespace RZP\Models\Transfer;

final class Constant
{
    const MAX_ALLOWED_ORDER_TRANSFER_PROCESS_ATTEMPTS = 4;

    const MAX_ALLOWED_PAYMENT_TRANSFER_PROCESS_ATTEMPTS = 1;

    const FETCH_STATUS = [Status::PROCESSED, Status::PARTIALLY_REVERSED, Status::REVERSED];

    const PAYMENT = 'payment';

    const CHUNK = 500;

    const ORDER ='order';
}
