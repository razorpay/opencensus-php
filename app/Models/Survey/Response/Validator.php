<?php

namespace RZP\Models\Survey\Response;

use RZP\Base;

class Validator extends Base\Validator
{
    const TYPEFORM_WEBHOOK = 'typeform_webhook';

    protected static $typeformWebhookRules = [
        Entity::EVENT_ID                                                        => 'required|string|max:50',
        Entity::EVENT_TYPE                                                      => 'required|string|max:20',
        Entity::FORM_RESPONSE                                                   => 'required|array',
        Entity::FORM_RESPONSE . "." . Entity::HIDDEN                            => 'required|array',
        Entity::FORM_RESPONSE . "." . Entity::HIDDEN . "." . Entity::TRACKER_ID => 'sometimes|string|max:14',
        Entity::FORM_RESPONSE . "." . Entity::HIDDEN . "." . Entity::UID        => 'sometimes|string|max:14',
        Entity::FORM_RESPONSE . "." . Entity::HIDDEN . "." . Entity::MID        => 'sometimes|string|max:14',
    ];

    protected static $createRules = [
        Entity::SURVEY_ID           => 'required|string|max:14',
        Entity::TRACKER_ID          => 'required|string|max:14',
    ];
}
