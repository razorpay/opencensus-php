<?php

namespace RZP\Gateway\Netbanking\Icici;

use RZP\Gateway\Base;

class Action extends Base\Action
{
    // TODO: Rename this class to Mode instead and don't use Base\Action at all?

    const PAY                   = 'P';
    const INQUIRY               = 'V';
    const STANDING_INSTRUCTIONS = 'SI';
}
