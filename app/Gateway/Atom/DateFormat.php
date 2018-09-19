<?php

namespace RZP\Gateway\Atom;

use RZP\Gateway\Base\Action;

class DateFormat
{
    const AUTHORIZE = 'd/m/Y H:i:s';
    const VERIFY    = 'Y-m-d';
    const REFUND    = 'Y-m-d';
    const CALLBACK  = 'D M d H:i:s e Y';

    const ACTION_MAP = [
        Action::AUTHORIZE => DateFormat::AUTHORIZE,
        Action::VERIFY    => DateFormat::VERIFY,
        Action::REFUND    => DateFormat::REFUND,
    ];
}
