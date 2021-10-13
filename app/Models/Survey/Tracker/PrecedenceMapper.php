<?php

namespace RZP\Models\Survey\Tracker;

class PrecedenceMapper extends Core
{
    //Surveys will higher precedence will have lower integer mapping. Just like the ranking system.
    public $precedenceMap = [
        Entity::NPS_ACTIVE_CA => 1,
        Entity::NPS_CSAT => 2,
        Entity::NPS_PAYOUTS_DASHBOARD =>3,
        Entity::NPS_PAYOUTS_API => 3
    ];

    // We will sent the survey only if no survey with higher precedence
    // is sent within the SurveyTTL.
    public function higherPrecedenceSurveyAlreadySent($userEmail, $surveyType, $bufferTime)
    {
        $surveysWithinBufferPeriod = $this->repo->survey_tracker->getSurveysSent($userEmail, $bufferTime);

        foreach ($surveysWithinBufferPeriod as $sentSurvey)
        {
            if($this->precedenceMap[$sentSurvey] <=  $this->precedenceMap[$surveyType])
            {
                return true;
            }
        }
        return false;
    }
}
