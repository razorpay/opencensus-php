<?php

namespace RZP\Models\BankTransferAttempt;

class Version
{
    const V1    = 'v1'; // Reconciliation without bank_transfer_attempt
    const V2    = 'v2'; // Reconcilaiton using bank_transder_attempt
}