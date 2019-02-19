<?php

namespace RZP\Jobs;

use Mail;
use Requests;
use Requests_Response;

use RZP\Trace\TraceCode;
use RZP\Services\Beam\Service;
use Razorpay\Trace\Logger as Trace;
use RZP\Mail\Beam\BeamRequestFailure;
use RZP\Models\Settlement\SlackNotification;

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
     * @var string
     */
    protected $fileList;

    /**
     * @var bool
     */
    protected $mock;

    /**
     * BeamJob constructor.
     * Here, mailinfo requires [recipient,subject,body]
     * @param array $request
     * @param array $retryTimeLines
     * @param array $mailInfo
     */
    public function __construct(array $request, array $retryTimeLines, array $mailInfo, bool $mock)
    {
        parent::__construct();

        $this->request        = $request;

        $this->mailInfo       = $mailInfo;

        $this->retryTimeLines = $retryTimeLines;

        $this->mock           = $mock;
    }

    /**
     * Process queue request
     */
    public function handle()
    {
        try
        {
            parent::handle();

            if ($this->mock === false)
            {
                $this->handleRequest();
            }

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

            $this->notify();

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
            $this->notify();

            $this->delete();

            return;
        }

        if (in_array($this->response->status_code, self::HTTP_RETRY_CODES, true) === true)
        {
            if  ($this->attempts() < count($this->retryTimeLines))
            {
                $this->release($this->retryTimeLines[$this->attempts() - 1]);

                return;
            }

            $this->notify();

            $this->delete();

            return;
        }

        if (in_array($this->response->status_code, self::HTTP_SUCCESS_CODES, true) === true)
        {
            $this->delete();

            return;
        }

        $this->notify();

        $this->delete();
    }

    /**
     * Send mail on request failure
     */
    public function sendEmail()
    {
        $mailData = $this->setMailInfo();

        $mailObj = new BeamRequestFailure($mailData);

        Mail::send($mailObj);
    }

    protected function notify()
    {
        try
        {
            $this->sendEmail();

            $operation = $this->mailInfo['filetype'] .' file send failed through Beam';

            $fileInfo = [
                'files'     => $this->fileList,
                'channel'   => $this->mailInfo['channel']
            ];

            (new SlackNotification)->send($operation, $fileInfo, null, $this->attempts());
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                Trace::ERROR,
                TraceCode::BEAM_NOTIFIER_FAILED,
                [
                    'filetype'       => $this->mailInfo['filetype'],
                    'filename'       => $this->fileList,
                ]);
        }
    }

    /**
     * Construct beam mail data
     * @return array
     */
    protected function setMailInfo(): array
    {
        $fileList = [];

        foreach ($this->mailInfo['fileInfo'] as $file)
        {
            $fileParam = explode('/', $file);

            array_push($fileList, $fileParam[count($fileParam) - 1]);
        }

        $this->fileList = implode(",", $fileList);

        $body = 'Hi,\n'. $this->mailInfo['filetype'] .' file send failed through Beam.\n'.
            'Channel  :: ' . $this->mailInfo['channel'] . '\n'.
            'Filename :: ' . $this->fileList . '\n';

        return [
            'body'      => $body,
            'subject'   => $this->mailInfo['subject'],
            'recipient' => $this->mailInfo['recipient'],
        ];
    }
}
