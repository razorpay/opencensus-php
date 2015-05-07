<?php

namespace Gateway\Base;

class Action
{
    const PURCHASE  = 'purchase';
    const AUTHORIZE = 'authorize';
    const CAPTURE   = 'capture';
    const REFUND    = 'refund';
    const VERIFY    = 'verify';
}