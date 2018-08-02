<?php

namespace RZP\Jobs;

use Mail;
use Requests;
use Requests_Response;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Mail\Beam\BeamRequestFailure;

class BeamJob extends Job
{
    /**
     * HTTP success codes
     */
    const HTTP_SUCCESS_CODES = [200, 204];

    /**
     * HTTP codes for retries
     */
    const HTTP_RETRY_CODES = [502];

    /**
     * @var string
     */
    protected $queueConfigKey = 'beam_request';

    /**
     * @var array
     */
    protected $request;

    /**
     * Stores wait time between job queue
     * @var array
     */
    protected $retryTimeLines;

    /**
     * @var array
     */
    protected $mailInfo;

    /**
     * @var Requests_Response
     */
    protected $response;

    /**
     * BeamJob constructor.
     * Here, mailinfo requires [recipient,subject,body]
     * @param array $request
     * @param array $retryTimeLines
     * @param array $mailInfo
     */
    public function __construct(array $request, array $retryTimeLines, array $mailInfo)
    {
        parent::__construct();

        $this->request        = $request;

        $this->mailInfo       = $mailInfo;

        $this->retryTimeLines = $retryTimeLines;
    }

    /**
     * Process queue request
     */
    public function handle()
    {
        try
        {
            parent::handle();

            $this->handleRequest();

            $this->checkRetryOrDelete();

        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                Trace::ERROR,
                TraceCode::BEAM_RESPONSE,
                [
                    'request'       => $this->request,
                    'retries'       => $this->retryTimeLines,
                    'mail_info'     => $this->mailInfo
                ]);

            $this->delete();
        }
    }

    public function handleRequest()
    {
        $this->trace->info(
            TraceCode::BEAM_REQUEST,
            [
                'request' => [
                    'url'     => $this->request['url'],
                    'headers' => $this->request['headers'],
                    'content' => $this->request['content'],
                    'options' => $this->request['options'],
                ],
                'attempts'   => $this->attempts(),
            ]
        );

        $timeStarted = microtime(true);

        $this->response = Requests::request(
            $this->request['url'],
            $this->request['headers'],
            $this->request['content'],
            $this->request['method'],
            $this->request['options']
        );

        $apiTime = microtime(true) - $timeStarted;

        $this->trace->info(
            TraceCode::BEAM_RESPONSE,
            [
                'time_taken' => $apiTime,
                'response'   => $this->response,
                'attempts'   => $this->attempts(),
                'url'        => $this->request['url'],
            ]
        );
    }

    /**
     * Checks http status for retry
     * A null response is returned when connection is timed out
     */
    public function checkRetryOrDelete()
    {
        if ($this->response === null)
        {
            $this->sendEmail();

            $this->delete();

            return;
        }

        if ((in_array($this->response->status_code, self::HTTP_RETRY_CODES, true) === true) and
            ($this->attempts() < count($this->retryTimeLines)))
        {
            $this->release($this->retryTimeLines[$this->attempts() - 1]);

            return;
        }

        if (in_array($this->response->status_code, self::HTTP_SUCCESS_CODES, true) === true)
        {
            $this->delete();

            return;
        }

        $this->sendEmail();

        $this->delete();
    }

    /**
     * Send mail on request failure
     */
    public function sendEmail()
    {
        $mailObj = new BeamRequestFailure($this->mailInfo);

        Mail::send($mailObj);
    }
}
