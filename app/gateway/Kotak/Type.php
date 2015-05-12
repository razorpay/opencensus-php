<?php

namespace Gateway\Kotak;

class Type
{
    const PURCHASE      = '00';
    const AUTHORIZE     = '01';
    const CAPTURE       = '03';
    const REFUND        = '04';
    const STATUS        = '05';
}