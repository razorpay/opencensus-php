<?php

namespace RZP\Models\Typeform;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $typeformWebhookRules = [
        'event_id'      => 'required|string|max:50',
        'event_type'    => 'required|string|max:20',
        'form_response' => 'required|array',
    ];
}
