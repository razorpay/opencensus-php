<?php

namespace RZP\Models\Survey\Tracker;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'survey_tracker';

    public function getLastSurveySent(string $email, string $surveyId)
    {
        $surveyIdColumn      = $this->dbColumn(Entity::SURVEY_ID);
        $surveyEmailColumn   = $this->dbColumn(Entity::SURVEY_EMAIL);

        return $this->newQuery()
                    ->where($surveyIdColumn, $surveyId)
                    ->where($surveyEmailColumn, $email)
                    ->first();
    }
}
