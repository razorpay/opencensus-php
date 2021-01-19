<?php

namespace RZP\Models\Survey\Tracker;

use RZP\Base;

class Validator extends Base\Validator
{
    const BEFORE_COHORT_SELECT = 'before_cohort_select';

    protected static $beforeCohortSelectRules = [
        Entity::SURVEY_ID           => 'required|string|max:14',
        Entity::SURVEY_TYPE         => 'required|string',
        Entity::COHORT_LIST         => 'sometimes|array',
    ];

    protected static $createRules = [
        Entity::SURVEY_ID           => 'required|string|max:14',
        Entity::SURVEY_EMAIL        => 'required|string',
        Entity::SURVEY_SENT_AT      => 'required|int',
        Entity::ATTEMPTS            => 'required|int|min:0',
    ];
}
