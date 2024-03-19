<?php

namespace RZP\Jobs;

use App;
use RZP\Services\Xperience;
use RZP\Trace\TraceCode;

class XperienceUserInviteAcceptedRequestJob extends RequestJob
{
    const MAX_ALLOWED_ATTEMPTS = 3;

    protected $metricsEnabled = true;

    public function __construct(array $request)
    {
        parent::__construct($request);

        $this->traceCodeRequest = TraceCode::XPERIENCE_USER_INVITE_ACCEPTED_REQUEST;

        $this->traceCodeResponse = TraceCode::XPERIENCE_USER_INVITE_ACCEPTED_RESPONSE;

        $this->traceCodeError = TraceCode::XPERIENCE_USER_INVITE_ACCEPTED_ERROR;
    }

    /** Trace request. Overriding this as our $request variable contains the request payload directly, instead of containing url, content, options.
     *
     * @return void
     */
    protected function traceRequest()
    {
        $this->trace->info(
            $this->traceCodeRequest,
            [
                'request_payload' => $this->request,
            ]);
    }

    protected function handleRequest()
    {
        $this->traceRequest();

        $app = App::getFacadeRoot();

        /** @var Xperience Xperience */
        $xperience = $app['xperience'];

        $timeStarted = microtime(true);

        $this->response = $xperience->userInviteAccepted($this->request);

        $timeTaken = microtime(true) - $timeStarted;

        $this->trace->info(
            $this->traceCodeResponse,
            [
                'time_taken' => $timeTaken,
                'attempts'   => $this->attempts(),
                'response'   => $this->response,
            ]);
    }
}
