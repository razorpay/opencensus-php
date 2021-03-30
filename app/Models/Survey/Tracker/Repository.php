<?php

namespace RZP\Models\Survey\Tracker;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'survey_tracker';

    public function getLastSurveySent(string $email)
    {
        $surveyEmailColumn   = $this->dbColumn(Entity::SURVEY_EMAIL);

        return $this->newQuery()
                    ->where($surveyEmailColumn, $email)
                    ->orderBy(Entity::CREATED_AT, 'desc')
                    ->first();
    }

    public function getTrackersBySurveyId(string $email, string $surveyId)
    {
        $surveyIdColumn      = $this->dbColumn(Entity::SURVEY_ID);
        $surveyEmailColumn   = $this->dbColumn(Entity::SURVEY_EMAIL);

        // Return the last triggered survey
        return $this->newQuery()
                    ->where($surveyIdColumn, $surveyId)
                    ->where($surveyEmailColumn, $email)
                    ->orderBy(Entity::CREATED_AT, 'desc')
                    ->first();
    }

    public function getTrackersByUserEmail(string $email)
    {
        $surveyEmailColumn   = $this->dbColumn(Entity::SURVEY_EMAIL);

        // Return the last triggered survey
        return $this->newQuery()
                    ->where($surveyEmailColumn, $email)
                    ->orderBy(Entity::CREATED_AT, 'desc')
                    ->first();
    }

    public function getTrackerByUserEmail(string  $email)
    {
        $surveyEmailColumn   = $this->dbColumn(Entity::SURVEY_EMAIL);

        return $this->newQuery()
                    ->where($surveyEmailColumn, $email)
                    ->orderBy(Entity::CREATED_AT, 'desc')
                    ->first();
    }
}
