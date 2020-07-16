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

    protected static $typeformWorkflowRules = [
        'parsed_data'    => 'required|array',
        'permission'     => 'required|string|max:100',
        'mid'            => 'required|string|max:50',
    ];
}
