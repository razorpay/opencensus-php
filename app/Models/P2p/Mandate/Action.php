<?php

namespace RZP\Models\P2p\Mandate;

use RZP\Models\P2p\Base;

class Action extends Base\Action
{
    const CUSTOMER_MANDATE_INITIATE_AUTHORIZE       = 'customerMandateInitiateAuthorize';
    const CUSTOMER_MANDATE_INITIATE_REJECT          = 'customerMandateInitiateReject';
    const CUSTOMER_MANDATE_INITIATE_PAUSE           = 'customerMandateInitiatePause';
    const CUSTOMER_MANDATE_INITIATE_UNPAUSE         = 'customerMandateInitiateUnPause';
    const CUSTOMER_MANDATE_INITIATE_REVOKE          = 'customerMandateInitiateRevoke';

    const CUSTOMER_MANDATE_AUTHORIZE                = 'customerMandateAuthorize';
    const CUSTOMER_MANDATE_REJECT                   = 'customerMandateReject';
    const CUSTOMER_MANDATE_PAUSE                    = 'customerMandatePause';
    const CUSTOMER_MANDATE_UNPAUSE                  = 'customerMandateUnpause';
    const CUSTOMER_MANDATE_REVOKE                   = 'customerMandateRevoke';

}
