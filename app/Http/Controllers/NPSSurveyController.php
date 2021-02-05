<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Models\Survey\Entity;
use RZP\Models\Survey\Service as SurveyService;
use RZP\Models\Survey\Tracker\Service as SurveyTrackerService;

class NPSSurveyController extends Controller
{
    protected $surveyTracker;

    protected $survey;

    public function __construct()
    {
        parent::__construct();

        $this->surveyTracker = new SurveyTrackerService;

        $this->service = new SurveyService;
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

}
