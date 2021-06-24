<?php

namespace RZP\Models\Survey\Tracker;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Survey;
use RZP\Constants\Table;
use RZP\Constants\Timezone;

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


    /*SELECT DISTINCT survey.type as type
    FROM survey JOIN survey_tracker ON
    survey.id=survey_tracker.survey_id
    WHERE survey_tracker.survey_email='$email'
    AND survey_tracker.survey_sent_at
    BETWEEN '$startTimeStamp' AND '$endTimeStamp';*/

    public function getSurveysSent($email, $bufferPeriod)
    {
        $endTimeStamp = Carbon::now(Timezone::IST)->getTimestamp();
        $startTimeStamp = Carbon::now(Timezone::IST)->subHours($bufferPeriod)->getTimestamp();

        $surveyTableIdColumn = $this->repo->survey->dbColumn(\RZP\Models\Survey\Entity::ID);
        $surveyTrackerTableSurveyIdColumn =$this->dbColumn(Entity::SURVEY_ID);
        $surveyTrackerTableSurveyEmailColumn =$this->dbColumn(Entity::SURVEY_EMAIL);
        $surveyTrackerTableSurveySentAtColumn =$this->dbColumn(Entity::SURVEY_SENT_AT);

        $selectAttr = $this->repo->survey->dbColumn(\RZP\Models\Survey\Entity::TYPE);

        $query = $this->newQuery()
            ->select($selectAttr)
            ->distinct()
            ->join(Table::SURVEY, $surveyTableIdColumn, '=', $surveyTrackerTableSurveyIdColumn)
            ->where($surveyTrackerTableSurveyEmailColumn, '=', $email)
            ->whereBetween($surveyTrackerTableSurveySentAtColumn, [$startTimeStamp, $endTimeStamp]);

        return $query->get()->pluck(\RZP\Models\Survey\Entity::TYPE);
    }
}
