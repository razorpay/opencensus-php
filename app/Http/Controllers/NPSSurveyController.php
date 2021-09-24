<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Constants\Mode;
use RZP\Models\Typeform\Auth;
use RZP\Models\Survey\Entity;
use RZP\Models\Survey\Service as SurveyService;
use RZP\Models\Survey\Tracker\Service as SurveyTrackerService;
use RZP\Models\Survey\Response\Service as SurveyResponseService;

class NPSSurveyController extends Controller
{
    protected $surveyTracker;

    protected $survey;

    protected $surveyResponseService;

    public function __construct()
    {
        parent::__construct();

        $this->surveyTracker = new SurveyTrackerService;

        $this->service = new SurveyService;

        $this->surveyResponseService = new SurveyResponseService;
    }

    public function initiateSurvey()
    {
        $input = Request::all();

        $response = $this->surveyTracker->dispatchCohort($input);

        return response()->json($response);
    }

    public function createSurvey()
    {
        $input = Request::all();

        $response = $this->service->create($input);

        return response()->json($response);
    }

    public function editSurvey(string $id)
    {
        $input = Request::all();

        $response = $this->service->edit($id, $input);

        return response()->json($response);
    }

    public function getPendingSurvey()
    {
        $input = Request::all();

        $response = $this->surveyTracker->getPendingSurvey($input);

        return response()->json($response);
    }

    public function editSurveyTracker(string $id)
    {
        $input = Request::all();

        $response = $this->surveyTracker->update($id, $input);

        return response()->json($response);
    }

    public function consumeTypeformWebhook()
    {
        $auth = null;

        $request = Request::getFacadeRoot();

        // This compares hash values of typeform signature, passed in header
        // versus what is stored in config. If this fails then the response
        // is not authenticated
        $auth = Auth::authenticateTypeformWebhook($request);

        if ($auth !== null)
        {
            return $auth;
        }

        $input = $request->all();

        $data = $this->surveyResponseService->processSurveyWebhook($input);

        return ApiResponse::json($data);
    }

    public function pushTypeFormResponsesToDataLake()
    {
        $input = Request::all();

        $data = $this->surveyResponseService->pushTypeFormResponsesToDataLake($input);

        return ApiResponse::json($data);
    }

    private function setMode()
    {
        $this->ba->setModeAndDbConnection(Mode::LIVE);
    }
}
