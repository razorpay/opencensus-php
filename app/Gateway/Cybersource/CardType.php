<?php

namespace RZP\Gateway\Cybersource;

use RZP\Exception;

class CardType
{
    const VISA     = '001';

    const MC       = '002';

    const AMEX     = '003';

    const DICL     = '005';

    const JCB      = '007';

    // Maestro (UK Domestic)
    const MAES_DOM = '024';

    // Maestro (International)
    const MAES_INT = '042';

    const ELO      = '054';

    public static function get($network)
    {
        if (defined(__CLASS__ . '::' . $network))
        {
            return constant(__CLASS__ . '::' . $network);
        }

        // @codeCoverageIgnoreStart
        // Adding this as a defensive code, code should never reach here.
        throw new Exception\LogicException(
            'Unsupported card network',
            null,
            [
                'network' => $network,
            ]);
        // @codeCoverageIgnoreEnd
    }
}
