<?php

namespace Gateway\Base;

class Action
{
    const AUTHORIZE = 'authorize';
    const CAPTURE   = 'capture';
    const REFUND    = 'refund';
    const VERIFY    = 'verify';
}