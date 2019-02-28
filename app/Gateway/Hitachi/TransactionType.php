<?php

namespace RZP\Gateway\Hitachi;

class TransactionType
{
    const AUTH    = '00';
    const VERIFY  = 'TS';
    const REFUND  = 'RF';
    const CAPTURE = 'CP';
    const VOID    = 'CN';
    const MOTO    = 'MT';
    const RUPAY   = 'RU';
}
