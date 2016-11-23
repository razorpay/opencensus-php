<?php

namespace RZP\Gateway\Base;

class Action
{
    const PURCHASE  = 'purchase';
    const AUTHORIZE = 'authorize';
    const CAPTURE   = 'capture';
    const REFUND    = 'refund';
    const VOID      = 'void';
    const VERIFY    = 'verify';
    const CALLBACK  = 'callback';
    const REV_AUTH  = 'rev_auth';
}
