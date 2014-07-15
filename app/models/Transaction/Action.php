<?php

namespace Models\Transaction;

class Action
{
    const AUTHORIZE = 'authorize';
    const CALLBACK = 'callback';
    const CAPTURE = 'capture';
    const REFUND = 'refund';
}
