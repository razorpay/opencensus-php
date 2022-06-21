<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Models\AMPEmail\Service;
use RZP\Http\Requests\MailModoL1FormSubmissionRequest;

class AMPEmailController extends Controller
{

    protected $service = Service::class;

    public function submitMailModoL1Form(MailModoL1FormSubmissionRequest $request)
    {
        $this->service()->submitMailModoL1Form($request);

        return ApiResponse::json([]);
    }
}
