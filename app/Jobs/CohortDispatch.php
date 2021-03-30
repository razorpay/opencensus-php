<?php

namespace RZP\Jobs;

use App;

use RZP\Models\User;
use RZP\Trace\TraceCode;
use RZP\Models\Survey\Tracker;

class CohortDispatch extends Job
{
    protected $trace;

    protected $input;

    public function __construct(string $mode, array $input)
    {
        parent::__construct($mode);

        $this->input = $input;
    }

    public function handle()
    {
        parent::handle();

        $merchantId = $this->input[Tracker\Entity::MERCHANT_ID];

        $surveyId = $this->input[Tracker\Entity::SURVEY_ID];

        $userId = $this->input[User\Entity::USER_ID] ?? null;

        $type = $this->input[Tracker\Entity::SURVEY_TYPE];

        $traceData = [ Tracker\Entity::SURVEY_ID => $surveyId ];

        $this->trace->info(
            TraceCode::COHORT_LIST_PROCESS_REQUEST,
            $this->input);

        try
        {
            if (empty($userId) === false)
            {
                $user = (new Tracker\Core)->dispatchForSurveyWithUserId($userId, $merchantId, $surveyId);

                //TODO: log all the user ids
                $this->trace->info(
                    TraceCode::COHORT_USER_PROCESS_SUCCESS,
                    $traceData );
            }
            else
            {
                $user = (new Tracker\Core)->dispatchForSurveyWithMerchantId($type, $merchantId, $surveyId);

                //TODO: log all the user ids
                $this->trace->info(
                    TraceCode::COHORT_MERCHANT_PROCESS_SUCCESS,
                    $traceData
                    );
            }
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode:: COHORT_SCHEDULE_PROCESS_JOB_FAILURE_EXCEPTION,
                $this->input);
        }
        finally
        {
            $this->delete();
        }
    }
}
