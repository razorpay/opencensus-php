<?php

namespace Gateway\UPI\ICICI;

use Gateway\Base;

class Action extends Base\Action
{
    const AUTHORIZE     = 'authorize';
    const VERIFY        = 'verify';
}