<?php

namespace RZP\Gateway\Cybersource;

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
}