<?php

namespace RZP\Gateway\P2p\Upi\Npci;

class ClOutput
{
    const ACTION            = 'action';
    const TYPE              = 'type';
    const NPCI              = 'npci';
    const VECTOR            = 'vector';
    const COUNT             = 'count';
    const TOKEN             = 'token';
    const EXPIRY            = 'expiry';

    // Used as Type for ClAction::GET_CHALLENGE
    const INITIAL           = 'initial';
    const ROTATE            = 'rotate';
}
