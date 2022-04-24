<?php

namespace RZP\Models\Merchant\WebhookV2;

final class Constant
{
    // Lists request/response keys.
    const ID   = 'id';
    const FILE = 'file';
    const EVENT_IDS = 'event_ids';

    // Default URL for MFN Webhooks
    const MFN_DEFAULT_CUSTOM_WEBHOOK_URL = "https://mfn.rzpmanagedservices.com/webhook_handler";
}
