<?php

namespace RZP\Models\Survey\Response;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'survey_response';

    public function getSurveyResponseByTrackerId(string $trackerId)
    {
        $surveyTrackerColumn = $this->dbColumn(Entity::TRACKER_ID);

        return $this->newQuery()
                    ->where($surveyTrackerColumn, $trackerId)
                    ->first();
    }
}
