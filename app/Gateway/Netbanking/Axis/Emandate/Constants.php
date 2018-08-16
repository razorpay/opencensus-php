<?php

namespace RZP\Gateway\Netbanking\Axis\Emandate;

class Constants
{
    const VERSION         = '1.0';

    //
    // "PMD" represents payment mode. Can contain "AIB" or "CD"
    // AIB = Axis Internet Banking
    // CD  = Credit / Debit Cards
    //
    const PMD             = 'AIB';

    // This value was set to TEST in the documentation
    const TYPE_TEST            = 'TEST';
    const TYPE_LIVE            = 'PRD';

    //
    // From the document:
    //
    // Bank system will consider modification/Data entry is there at bank end.
    // After clicking on submit from corporate site to bank site it will take
    // to intermediate page where all details received from corporate site in
    // request ( In PPI field) will display for modification/Data entry and
    // user will submit request to payment gateway page.
    //
    const NO_MODIFICATION = 'MN';

    //
    //We can pass any value to this field, since they're not using it as confirmed in the mail.
    //@see PRE_POP_INFO
    //
    const PPI_AMOUNT_TYPE = 'max';
}
