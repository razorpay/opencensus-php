<?php

namespace RZP\Models\Survey\Tracker;

use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Jobs\CohortDispatch;
use RZP\Services\HubspotClient;
use Razorpay\Trace\Logger as Trace;
use RZP\Error\PublicErrorDescription;
use RZP\Models\User\Entity as UserEntity;
use RZP\Models\Survey\Entity as SurveyEntity;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Models\Survey\Response\Entity as SurveyResponseEntity;
use RZP\Models\Merchant\MerchantUser\Entity as MerchantUserEntity;

class Core extends Base\Core
{
    public function dispatchCohortForSurvey(string $type, array $cohort, string $surveyId)
    {
        $this->mode = $this->app['rzp.mode'];

        $traceInfo = [
            Entity::COHORT      => $cohort,
            Entity::SURVEY_ID   => $surveyId
        ];

        if ($this->mode === Mode::TEST)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_NPS_SURVEY_NOT_APPLICABLE_IN_TEST_MODE,
                null,
                $traceInfo,
                PublicErrorDescription::BAD_REQUEST_NPS_SURVEY_NOT_APPLICABLE_IN_TEST_MODE);
        }

        try
        {
            $this->trace->info(TraceCode::COHORT_DISPATCH_INIT, $traceInfo);

            $input = [
                PayoutEntity::MERCHANT_ID   => $cohort[PayoutEntity::MERCHANT_ID],
                PayoutEntity::USER_ID       => $cohort[PayoutEntity::USER_ID] ?? null,
                Entity::SURVEY_ID           => $surveyId,
                Entity::SURVEY_TYPE         => $type,
            ];

            CohortDispatch::dispatch($this->mode, $input);

            $this->trace->info(TraceCode::COHORT_DISPATCH_COMPLETE, $traceInfo);
        }
        catch (\Throwable $e)
        {
            // If the dispatch fails due to any reason, cron will
            // pick up these merchants again and attempt to dispatch.

            $data = $traceInfo + [ 'message' => $e->getMessage() ];

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::COHORT_DISPATCH_FAILED,
                $data);
        }
    }

    public function dispatchForSurveyWithMerchantId(string $type, string $merchantId, string $surveyId)
    {
        $survey = $this->repo->survey->findOrFailPublic($surveyId);

        $this->repo->merchant->findOrFailPublic($merchantId);

        $merchantUsers = $this->getSurveyClient($type)->fetchMerchantUsers([$merchantId]);

        foreach ($merchantUsers as $merchantUser)
        {
            $user = $this->repo->user->findOrFailPublic($merchantUser[MerchantUserEntity::USER_ID]);

            $this->dispatchForSurvey($user, $merchantId, $survey);
        }

        return $merchantUsers->toArray();
    }

    public function dispatchForSurveyWithUserId(string $userId, string $merchantId, string $surveyId)
    {
        $survey = $this->repo->survey->findOrFailPublic($surveyId);

        $user = $this->repo->user->findOrFailPublic($userId);

        $this->dispatchForSurvey($user, $merchantId, $survey);
    }

    public function dispatchForSurvey(UserEntity $user, string $merchantId, SurveyEntity $survey)
    {
        $surveyTimeWindow = $survey[SurveyEntity::SURVEY_TTL];

        $currentTimeStamp = Carbon::now(Timezone::IST)->getTimestamp();

        //Current logic is to not sent a survey if any survey was sent to the same email within the buffer time. Hence only checking with email id for across surveys.
        $surveyTrackerEntity = $this->repo->survey_tracker->getLastSurveySent($user[UserEntity::EMAIL]);

        // $surveyTrackerEntity is not null, means a survey email for this user has already been sent
        if (empty($surveyTrackerEntity) === false)
        {
            $surveyLastSentTimestamp = $surveyTrackerEntity[Entity::CREATED_AT];

            $snoozePeriod = Carbon::createFromTimestamp($surveyLastSentTimestamp, Timezone::IST)->addHour($surveyTimeWindow)->getTimestamp();

            // If a survey email is sent to an user, then next email will only be sent after the scheduled SURVEY_TTL time
            if (($surveyLastSentTimestamp <= $currentTimeStamp) and
                ($currentTimeStamp <= $snoozePeriod))
            {
                $this->trace->info(TraceCode::COHORT_EMAIL_ALREADY_SENT_WITHIN_TIMEFRAME, [Entity::X_UID => $user[UserEntity::ID]]);

                return;
            }
        }

        $surveyTrackerEntityInput = [
            Entity::SURVEY_ID       => $survey[Entity::ID],
            Entity::SURVEY_EMAIL    => $user[UserEntity::EMAIL],
            Entity::ATTEMPTS        => 1,
            Entity::SURVEY_SENT_AT  => $currentTimeStamp
        ];

        $surveyTrackerEntity = (new Entity)->build($surveyTrackerEntityInput);

        $this->repo->saveorFail($surveyTrackerEntity);

        $this->trace->info(TraceCode::COHORT_EMAIL_TO_HUBSPOT, [Entity::X_UID => $user[UserEntity::ID]]);

        $hubspotInput = [
            Entity::SURVEY_EMAIL        => $user[UserEntity::EMAIL],
            Entity::MID                 => $merchantId,
            Entity::USER_ID             => $user[UserEntity::ID],
            Entity::SURVEY_ID           => $survey[Entity::ID],
            SurveyEntity::SURVEY_URL    => $survey[SurveyEntity::SURVEY_URL],
            Entity::ID                  => $surveyTrackerEntity->getId()
        ];

        $this->sendToHubspot($hubspotInput);
    }

    public function getSurveyClient(string $type)
    {
        $cohortSelectorService = Entity::BASE_MODEL_DIR .'\\' . SurveyServiceMapper::getClient($type);

        return new $cohortSelectorService();
    }

    private function sendToHubspot(array $hubspotInput)
    {
        /** @var HubspotClient $hubspotClient */
        $hubspotClient = $this->app->hubspot;

        $hubspotClient->trackHubspotEvent($hubspotInput[Entity::SURVEY_EMAIL], [
            Entity::NPS_SURVEY                  => $hubspotInput[Entity::SURVEY_ID],
            Entity::MID                         => $hubspotInput[Entity::MID],
            Entity::X_UID                       => $hubspotInput[Entity::USER_ID],
            SurveyEntity::SURVEY_URL            => $hubspotInput[SurveyEntity::SURVEY_URL],
            SurveyResponseEntity::TRACKER_ID    => $hubspotInput[Entity::ID]
        ]);
    }

    public function edit(Entity $surveyTracker, array $input): Entity
    {
        $surveyTracker->edit($input);

        $this->repo->saveOrFail($surveyTracker);

        return $surveyTracker;
    }
}
