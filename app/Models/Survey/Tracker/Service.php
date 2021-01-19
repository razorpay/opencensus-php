<?php

namespace RZP\Models\Survey\Tracker;

use RZP\Exception;
use RZP\Models\Base;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    /**
     * @param array $input
     * @return array
     * @throws Exception\BadRequestException
     */
    public function dispatchCohort(array $input)
    {
        (new Validator)->validateInput(Validator::BEFORE_COHORT_SELECT, $input);

        $type = $input[Entity::SURVEY_TYPE];

        $surveyId = $input[Entity::SURVEY_ID];

        $cohorts = $input[Entity::COHORT_LIST] ?? [];

        $this->repo->survey->findOrFailPublic($surveyId);

        if (empty($cohorts) === true)
        {
            $cohorts = $this->core->getSurveyClient($type)
                                  ->getCohorts();
        }

        $dispatchedCohortCount = 0;

        foreach ($cohorts as $cohort)
        {
            $this->core->dispatchCohortForSurvey($type, $cohort, $surveyId);

            $dispatchedCohortCount += 1;
        }

        return [
            'dispatched_cohort_count' => $dispatchedCohortCount
        ];
    }
}
