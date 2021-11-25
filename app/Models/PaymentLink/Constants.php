<?php

namespace RZP\Models\PaymentLink;

class Constants
{
    // Input/output keys when doing ufh service requests.
    const RELATIVE_LOCATION        = 'relative_location';
    // Used as file type in ufh for images uplaoded in payment's page description.
    const PAYMENT_LINK_DESCRIPTION = 'payment_link_description';

    // payment page V3 experiment
    const PAYMENT_PAGE_V3 = 'paymentpages_v3';

    const PARTNER_ZAPIER = 'partner_zapier';

    const PARTNER_SHIPROCKET = 'partner_shiprocket';

    const PARTNER_WEBHOOK_SETTINGS_KEY = 'partner_webhook_settings';
}
