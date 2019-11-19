<?php

namespace RZP\Models\Transfer;

final class Constant
{
    const MAX_ALLOWED_ORDER_TRANSFER_PROCESS_ATTEMPTS = 4;

    const FETCH_STATUS = [Status::PROCESSED, Status::PARTIALLY_REVERSED, Status::REVERSED];
}
