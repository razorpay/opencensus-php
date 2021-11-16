<?php

namespace RZP\Models\Survey\Tracker;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Survey;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Merchant\MerchantUser;
use RZP\Exception\BadRequestException;

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

        $this->trace->info(TraceCode::SURVEY_TRIGGER_INPUT, $input);

        $surveyType = $input[Entity::SURVEY_TYPE];

//        $channelInformation = $input[Entity::CHANNEL];

        $survey = $this->repo->survey->get($surveyType);

        if (empty($survey) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_SURVEY_TYPE,
            null,
                [
                    Entity::SURVEY_TYPE => $surveyType
                ]);
        }

        $cohorts = $input[Entity::COHORT_LIST] ?? [];

        if (empty($cohorts) === true)
        {
            $cohorts = $this->core->getSurveyClient($surveyType)
                                  ->getCohorts($survey[Survey\Entity::SURVEY_TTL]);
        }

        $dispatchedCohortCount = 0;

        foreach ($cohorts as $cohort)
        {
            $this->core->dispatchCohortForSurvey($surveyType, $cohort, $survey[Survey\Entity::ID]);

            $dispatchedCohortCount += 1;
        }

        $this->trace->info(TraceCode::SELECTED_COHORT_COUNT, ['survey_type' => $surveyType, 'dispatched_cohort_count' => $dispatchedCohortCount]);

        return [
            'dispatched_cohort_count' => $dispatchedCohortCount
        ];
    }

    /**
     * @param array $input
     * @return array
     * @throws BadRequestException
     */
    public function getPendingSurvey(array $input)
    {
        (new Validator)->validateInput(Validator::BEFORE_PENDING_SURVEY_GET, $input);

        $survey[Survey\Entity::SURVEY_URL] = $input[Entity::USER_ID];

        $userId = $input[Entity::USER_ID];

        $user = $this->repo->user->findOrFailPublic($userId);

        $userEmail = $user[User\Entity::EMAIL];

        $lastTriggeredSurvey = $this->repo->survey_tracker->getTrackersByUserEmail($userEmail);

        // No pending survey, or the last survey is skipped then return null
        if (($lastTriggeredSurvey[Entity::SKIP_IN_APP] === 1) or
            (empty($lastTriggeredSurvey) === true))
        {
            return null;
        }

        $surveyResponse = $this->repo->survey_response->getSurveyResponseByTrackerId($lastTriggeredSurvey[Entity::ID]);

        $surveyId = $lastTriggeredSurvey[Entity::SURVEY_ID];

        $survey = $this->repo->survey->findOrFailPublic($surveyId);

        $merchantIds = (new MerchantUser\Repository)->returnMerchantIdsForUserId($userId, 1);

        $merchantId = $merchantIds[0];

        // Adding survey URL and survey type, to open from dashboard
        $fullUrl = $this->createSurveyUrl($survey[Survey\Entity::SURVEY_URL], $userId,
                                         $merchantId, $lastTriggeredSurvey[Entity::ID]);

        $lastTriggeredSurvey[Entity::SURVEY_URL] = $fullUrl;

        $survey_type = $survey[Survey\Entity::TYPE];

        if($survey_type === 'nps_payouts_api' || $survey_type === 'nps_payouts_dashboard'){
            $lastTriggeredSurvey[Entity::SURVEY_TYPE] = 'nps_payouts';
        } else {
            $lastTriggeredSurvey[Entity::SURVEY_TYPE] = $survey[Survey\Entity::TYPE];
        }

        $this->trace->info(TraceCode::RESPONSE_FOR_PENDING_SURVEY,
            [
                'survey_type' => $lastTriggeredSurvey[Entity::SURVEY_TYPE],
                'survey_url'  => $lastTriggeredSurvey[Entity::SURVEY_URL],
                'survey_id'   => $lastTriggeredSurvey[Entity::SURVEY_ID]
            ]
        );


        return (empty($surveyResponse) === true) ? $lastTriggeredSurvey->toArrayPublic() : null;
    }

    private function createSurveyUrl(string $baseUrl, string $uid, string $mid, string $strackerId)
    {
        return $baseUrl . "?mid=" . $mid . "&uid=" . $uid . "&tracker_id=" . $strackerId;
    }

    /**
     * @param string $id
     * @param array $input
     * @return array
     */
    public function update(string $id, array $input)
    {
        (new Validator)->validateInput(Validator::BEFORE_TRACKER_UPDATE, $input);

        $surveyTracker = $this->repo->survey_tracker->findOrFailPublic($id);

        $surveyTracker = $this->core->edit($surveyTracker, $input);

        return $surveyTracker->toArrayPublic();
    }
}
