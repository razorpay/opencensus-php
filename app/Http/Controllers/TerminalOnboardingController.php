<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal\Onboarding;

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

    public function fetchTerminals()
    {
        $input = Request::all();

        $data = $this->service()->fetchTerminals($input);

        return ApiResponse::json($data);
    }

    public function postOnboardTerminalVerification()
    {
        // We are currently disabling this route as we don't have any api to verify terminal onboarding on gateway,
        // not removing the dead code as ATOS may provide us verification apis in future
        throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ACCESS_DENIED);

        $input = Request::all();

        $data = $this->service()->verifyTerminals($input);

        $this->trace->info(
            TraceCode::TERMINAL_ONBOARDING_VERIFICATION_CRON_RESPONSE,
            [
                'input'    => $input,
                'response' => $data,
            ]);

        return ApiResponse::json($data);
    }

    public function postOnboardTerminalCreation()
    {
        $input = Request::all();

        $data = $this->service()->onboardTerminals($input);

        $this->trace->info(
            TraceCode::TERMINAL_ONBOARDING_CREATION_CRON_RESPONSE,
            [
                'input'    => $input,
                'response' => $data,
            ]);

        return ApiResponse::json($data);
    }

    /**
     * This is a precautionary API, which will be used using adminAuth, in case we need to change status of a terminalonboarding manually
     * This will update status of input terminal_onboarding_details ids to created
     */
    public function putTerminalOnboardingStatus()
    {
        $input = Request::all();

        $response = $this->service()->updateTerminalOnboardingStatus($input);

        return ApiResponse::json($response);
    }
}
