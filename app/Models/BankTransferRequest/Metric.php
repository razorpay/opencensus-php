<?php

namespace RZP\Models\BankTransferRequest;

final class Metric
{
    //Labels
    const LABEL_MODE        = 'mode';
    const LABEL_GATEWAY     = 'gateway';
    const LABEL_ENVIRONMENT = 'environment';

    const BANK_TRANSFER_SAVE_REQUESTS_TOTAL = 'bank_transfer_save_requests_total';
}
