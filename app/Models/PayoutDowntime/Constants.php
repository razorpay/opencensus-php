<?php

namespace RZP\Models\PayoutDowntime;

class Constants
{
    const MID_LIST                = 'mid_list';

    const PROCESSING              = 'Processing';

    const SENT                    = 'Sent';

    const FAILED                  = 'Failed';

    const YES                     = 'Yes';

    const NO                      = 'No';

    const EMAIL_MESSAGE           = 'email_message';

    const STATUS                  = 'status';

    const CHANNEL                 = 'channel';

    const CURRENT                 = 'current';

    const ENABLED                 = 'Enabled';

    const DISABLED                = 'Disabled';

    const CANCELLED               = 'Cancelled';

    const SCHEDULED               = 'Scheduled';

    const SUBJECT                 = 'subject';

    const FROM                    = 'from';

    const BCC                     = 'bcc';

    const TO                      = 'to';

    const CC                      = 'cc';

    const EMAIL_TYPE              = 'email_type';

    const DEFAULT_EMAIL_SUBJECT   = 'Important Update for your RazorpayX account.';

    const ALLOWED_EMAIL_STATES    = [self::ENABLED, self::DISABLED];

    //pool network is related to nodal accounts. RBL is current account and All means X systems.
    const POOL_NETWORK            = 'Pool Network';
    const RBL                     = 'RBL';
    const ALL                     = 'All';

}
