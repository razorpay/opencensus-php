<?php

namespace RZP\Models\BankTransferAttempt;

class Version
{
    const V1    = 'v1'; // Settlement reconciliation without retry
    const V2    = 'v2'; // Settlement reconcilaiton with retry based on table bank_transder_attempts
}