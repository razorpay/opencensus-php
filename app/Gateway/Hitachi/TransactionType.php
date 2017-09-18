<?php

namespace RZP\Gateway\Hitachi;

class TransactionType
{
    const AUTH    = '00';
    const TXN     = 'TS';
    const REFUND  = 'RF';
    const CAPTURE = 'CP';
    const VOID    = 'CN';
}
