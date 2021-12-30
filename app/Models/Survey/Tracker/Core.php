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
use RZP\Models\BankingAccount;
use RZP\Services\HubspotClient;
use Razorpay\Trace\Logger as Trace;
use RZP\Error\PublicErrorDescription;
use RZP\Models\User\Entity as UserEntity;
use RZP\Models\Survey\Entity as SurveyEntity;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Models\Survey\Response\Entity as SurveyResponseEntity;
use RZP\Models\Merchant\MerchantUser\Entity as MerchantUserEntity;
use RZP\Models\BankingAccount\Activation\Detail as ActivationDetail;

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

        $input = [];

        if($survey[SurveyEntity::TYPE] === Entity::NPS_CSAT)
        {
           $input[Entity::ACCOUNT_STATUS] = $this->repo->banking_account->getStatusWithMerchantId($merchantId)->first();
        }

        foreach ($merchantUsers as $merchantUser)
        {
            $user = $this->repo->user->findOrFailPublic($merchantUser[MerchantUserEntity::USER_ID]);

            $input[Entity::USER_ID]      = $user[UserEntity::ID];
            $input[Entity::MID]          = $merchantId;
            $input[Entity::SURVEY_EMAIL] = $user[UserEntity::EMAIL];
            $input[Entity::CONTACT_TYPE] = Entity::USER;

            $this->dispatchForSurvey($input, $survey);
        }

        if($survey[SurveyEntity::TYPE] === Entity::NPS_CSAT)
        {
            $this->sendSurveyToMerchantPocAndBeneficiaryEmail($input, $survey);
        }

        return $merchantUsers->toArray();
    }

    public function sendSurveyToMerchantPocAndBeneficiaryEmail($input, SurveyEntity $survey)
    {
        $merchantId = $input[Entity::MID];

        $emails = $this->repo->banking_account->getMerchantPocAndBeneficiaryEmail($merchantId);
        $merchantPocEmail = $emails[ActivationDetail\Entity::MERCHANT_POC_EMAIL];
        $beneficiaryEmail = $emails[BankingAccount\Entity::BENEFICIARY_EMAIL];

        if(empty($merchantPocEmail) === false)
        {
            $merchantPocEmails = preg_split('/,|&| /',$merchantPocEmail,-1, PREG_SPLIT_NO_EMPTY );

            foreach ($merchantPocEmails as $index => $email)
            {
                $input[Entity::USER_ID] = Entity::DUMMY_UID_FOR_MERCHANT_POC_MAILS.'_'.$index;
                $input[Entity::SURVEY_EMAIL] = $email;
                $input[Entity::CONTACT_TYPE] = Entity::MERCHANT_POC;

                $this->dispatchForSurvey($input, $survey);
            }
        }

        if(empty($beneficiaryEmail) === false)
        {
            $input[Entity::USER_ID] = Entity::DUMMY_UID_FOR_BENEFICIARY_MAILS;
            $input[Entity::SURVEY_EMAIL] = $beneficiaryEmail;
            $input[Entity::CONTACT_TYPE] = Entity::BENEFICIARY;

            $this->dispatchForSurvey($input, $survey);
        }
    }

    public function dispatchForSurveyWithUserId(string $userId, string $merchantId, string $surveyId)
    {
        $survey = $this->repo->survey->findOrFailPublic($surveyId);

        $user = $this->repo->user->findOrFailPublic($userId);

        $input = [
            Entity::USER_ID => $user[UserEntity::ID],
            Entity::MID     => $merchantId,
            Entity::SURVEY_EMAIL => $user[UserEntity::EMAIL],
            Entity::CONTACT_TYPE => Entity::USER
        ];

        $this->dispatchForSurvey($input, $survey);
    }

    public function dispatchForSurvey($input, SurveyEntity $survey)
    {
        $currentTimeStamp = Carbon::now(Timezone::IST)->getTimestamp();

        $email = $input[Entity::SURVEY_EMAIL];
        $merchantId =$input[Entity::MID];
        $uid = $input[Entity::USER_ID];

        if((new PrecedenceMapper())->higherPrecedenceSurveyAlreadySent($email, $survey[SurveyEntity::TYPE], $survey[\RZP\Models\Survey\Entity::SURVEY_TTL]))
        {
            $this->trace->info(TraceCode::COHORT_EMAIL_ALREADY_SENT_FOR_HIGHER_PRECEDENCE_SURVEY, ['merchant_id' => $merchantId, 'user_id' => $uid, 'contact_email' => $email, 'survey_type' => $survey[SurveyEntity::TYPE]]);

            return;
        }
        else
        {
            $this->trace->info(TraceCode::COHORT_EMAIL_GETTING_SENT, ['merchant_id' => $merchantId, 'user_id' => $uid, 'contact_email' => $email, 'survey_type' => $survey[SurveyEntity::TYPE]]);
        }

        $surveyTrackerEntityInput = [
            Entity::SURVEY_ID       => $survey[Entity::ID],
            Entity::SURVEY_EMAIL    => $email,
            Entity::ATTEMPTS        => 1,
            Entity::SURVEY_SENT_AT  => $currentTimeStamp
        ];

        $channel_info = $survey[Entity::CHANNEL];

        if($channel_info === 2){
            $surveyTrackerEntityInput[Entity::SKIP_IN_APP]  = 1;
        }

        $surveyTrackerEntity = (new Entity)->build($surveyTrackerEntityInput);

        $this->repo->saveorFail($surveyTrackerEntity);

        if($channel_info === 2 || $channel_info === 3){
            $hubspotInput = [
                Entity::SURVEY_EMAIL        => $email,
                Entity::SURVEY_TYPE         => $survey[SurveyEntity::TYPE],
                Entity::MID                 => $merchantId,
                Entity::USER_ID             => $uid,
                Entity::SURVEY_ID           => $survey[Entity::ID],
                SurveyEntity::SURVEY_URL    => $survey[SurveyEntity::SURVEY_URL],
                Entity::ID                  => $surveyTrackerEntity->getId(),
                Entity::CONTACT_TYPE        => $input[Entity::CONTACT_TYPE],
                Entity::ACCOUNT_STATUS      => $input[Entity::ACCOUNT_STATUS] ?? null
            ];

            $this->trace->info(TraceCode::COHORT_EMAIL_TO_HUBSPOT, $hubspotInput);

            $this->sendToHubspot($hubspotInput);
        }


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

        if (empty($hubspotInput[Entity::SURVEY_EMAIL]) === false)
        {
            $hubspotClient->trackHubspotEvent($hubspotInput[Entity::SURVEY_EMAIL], [
                Entity::NPS_SURVEY => $hubspotInput[Entity::SURVEY_ID],
                Entity::SURVEY_TYPE => $hubspotInput[Entity::SURVEY_TYPE],
                Entity::MID => $hubspotInput[Entity::MID],
                Entity::X_UID => $hubspotInput[Entity::USER_ID],
                SurveyEntity::SURVEY_URL => $hubspotInput[SurveyEntity::SURVEY_URL],
                SurveyResponseEntity::TRACKER_ID => $hubspotInput[Entity::ID],
                Entity::X_CONTACT_TYPE => $hubspotInput[Entity::CONTACT_TYPE],
                Entity::X_CA_ACCOUNT_STATUS => $hubspotInput[Entity::ACCOUNT_STATUS]
            ]);
        }
    }

    public function edit(Entity $surveyTracker, array $input): Entity
    {
        $surveyTracker->edit($input);

        $this->repo->saveOrFail($surveyTracker);

        return $surveyTracker;
    }
}
