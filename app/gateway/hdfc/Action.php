<?php

namespace Gateway\Hdfc;

class Action
{
    const PURCHASE = 1;

    const REFUND = 2;

    const AUTH = 4;

    const CAPTURE = 5;

    const INQUIRY = 8;
}