<?php

namespace RZP\Models\Payout\Notifications;

class SmsConstants
{
    // Request Params
    const SOURCE   = 'source';
    const SENDER   = 'sender';

    const OWNER_ID                    = 'ownerId';
    const OWNER_TYPE                  = 'ownerType';
    const ORG_ID                      = 'orgId';
    const DESTINATION                 = 'destination';
    const TEMPLATE_NAME               = 'templateName';
    const TEMPLATE_NAMESPACE          = 'templateNamespace';
    const LANGUAGE                    = 'language';
    const CONTENT_PARAMS              = 'contentParams';
    const DELIVERY_CALLBACK_REQUESTED = 'deliveryCallbackRequested';
    const TIMINGS                     = 'timings';
    const MODES                       = 'modes';

    // Razorpayx
    const RAZORPAYX_SENDER                = 'RZPAYX';
    const PAYOUTS_CORE_TEMPLATE_NAMESPACE = 'razorpayx_payouts_core';
    const ENGLISH                         = 'english';
    const DEFAULT_OWNER_ID                = '10000000000000';
}
