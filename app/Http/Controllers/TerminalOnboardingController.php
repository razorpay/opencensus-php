<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\Terminal\Onboarding;

class TerminalOnboardingController extends Controller
{
    protected $service = Onboarding\Service::class;

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
}
