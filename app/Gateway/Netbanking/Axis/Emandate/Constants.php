<?php

namespace RZP\Gateway\Netbanking\Axis\Emandate;

class Constants
{
    const VERSION         = '1.0';
    const PMD             = 'AIB';

    /**
     * This value was set to TEST in the documentation
     */
    const TYPE            = 'TEST';
    const NO_MODIFICATION = 'MN';

    /**
     * We can pass any value to this field, since they're not using it as confirmed in the mail.
     */
    const PPI_AMOUNT_TYPE = 'max';
}
