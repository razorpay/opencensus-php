<?php

namespace RZP\Models\Merchant\InternationalEnablement;

class Constants
{
    const PERCENTAGE_COMPLETION = 'percentage_completion';
    const ENABLEMENT_PROGRESS   = 'enablement_progress';
    const NEW_FLOW              = 'new_flow';
    const VERSION               = 'version';
    const LAST_UPDATED_AT       = 'last_updated_at';

    const NOT_STARTED = 'not_started';
    const IN_PROGRESS = 'in_progress';
    const SUBMITTED   = 'submitted';

    const RAZORX_VARIANT_NEW_FLOW = 'on';

    const INTERNATIONAL_ENABLEMENT_LOCK_KEY = 'international_enablement_merchant_%s';

    const IEDetailDashboardLink   = '/admin/entity/international_enablement_detail/%s/%s';
    const IEDocumentDownloadLink  = '/admin/api/%s/admin-ufh/%s/files/file_%s/get-signed-url';

    const IE_RAZORX_FEATURE = 'international_enablement_new_flow';

    const INTERNATIONAL_CARDS_ENABLED = 'international_cards_enabled';
    const INTERNATIONAL_ACTIVATION_FORM_INITIATED= 'international_activation_form_initiated';
    const INTERNATIONAL_ACTIVATION_FORM_COMPLETED = 'international_activation_form_completed';
}
