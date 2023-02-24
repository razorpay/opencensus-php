<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal\Onboarding;
use Illuminate\Support\Facades\App;

class TerminalOnboardingController extends Controller
{
    protected $service = Onboarding\Service::class;

    public function postCreateTerminal()
    {
        $input = Request::all();

        $data = $this->service()->create($input);

        return ApiResponse::json($data);
    }

    public function putTerminalEnable(string $id)
    {
        $data = $this->service()->enableTerminal($id);

        return ApiResponse::json($data);
    }

    public function putTerminalDisable(string $id)
    {
        $data = $this->service()->disableTerminal($id);

        return ApiResponse::json($data);
    }

    public function putTerminalEnableBulk()
    {
        $input = Request::all();

        $response = $this->service()->enableTerminalBulk($input);

        return $response;
    }

    public function fetchTerminals()
    {
        $input = Request::all();

        $data = $this->service()->fetchTerminals($input);

        return ApiResponse::json($data);
    }

    public function postInitiateOnboarding()
    {
        $input = Request::all();

        $response = $this->service()->initiateOnboarding($input);

        return $response;
    }

    public function postUpiTerminalOnboardingBulk()
    {
        $input = Request::all();

        $response = $this->service()->postUpiTerminalOnboardingBulk($input);

        return ApiResponse::json($response->toArrayWithItems());
    }

    public function postUpiOnboardedTerminalEditBulk()
    {
        $input = Request::all();

        $response = $this->service()->postUpiOnboardedTerminalEditBulk($input);

        return ApiResponse::json($response->toArrayWithItems());
    }

    public function postTerminalOnboardCallback(string $gateway, string $mode)
    {
        $input = Request::all();

        $app = App::getFacadeRoot();

        $app['basicauth']->setMode($mode);

        $this->service()->processTerminalOnboardCallback($gateway, $input);

        return ApiResponse::json(['success' => true]);
    }
}
