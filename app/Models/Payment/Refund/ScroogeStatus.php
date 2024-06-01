<?php

namespace RZP\Models\Payment\Refund;

class ScroogeStatus
{
    const CREATED = 'created';
    const CREATION_FAILED = 'creation_failed';
    const INIT = 'init';
    const ON_HOLD = 'on_hold';
    const FTA_PENDING = 'fta_pending';
    const FILE_INIT = 'file_init';
    const RECON_PENDING = 'recon_pending';
    const DEBIT_VALIDATION_PENDING = 'debit_validation_pending';
    const FILE_SENT = 'file_sent';
    const PROCESSED = 'processed';
    const FAILED = 'failed';
}
