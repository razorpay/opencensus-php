<?php

namespace RZP\Models\Survey\Tracker;

use RZP\Base;

class Validator extends Base\Validator
{
    const BEFORE_COHORT_SELECT          = 'before_cohort_select';

    const BEFORE_PENDING_SURVEY_GET     = 'before_pending_survey_get';

    const BEFORE_TRACKER_UPDATE         = 'before_tracker_update';

    const TYPEFORM_WEBHOOK_HIDDEN_FIELD = 'typeform_webhook_hidden_field';

    protected static $beforeCohortSelectRules = [
        Entity::SURVEY_TYPE         => 'required|string',
        Entity::COHORT_LIST         => 'sometimes|array',
    ];

    protected static $beforePendingSurveyGetRules = [
        Entity::USER_ID             => 'required|string',
    ];

    protected static $beforeTrackerUpdateRules = [
        Entity::SKIP_IN_APP          => 'required|boolean',
    ];

    protected static $createRules = [
        Entity::SURVEY_ID           => 'required|string|max:14',
        Entity::SURVEY_EMAIL        => 'required|string',
        Entity::SURVEY_SENT_AT      => 'required|int',
        Entity::ATTEMPTS            => 'required|int|min:0',
        Entity::SKIP_IN_APP         => 'sometimes|boolean',
    ];

    protected static $editRules = [
        Entity::SKIP_IN_APP         => 'required|boolean',
    ];
}
