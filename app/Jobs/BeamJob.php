<?php

namespace RZP\Jobs;

use Illuminate\Support\Facades\Config;
use Mail;
use RZP\Services\Beam\Metric;
use \WpOrg\Requests\Response;

use Carbon\Carbon;
use RZP\Diag\EventCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Http\Request\Requests;
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
     * @var \WpOrg\Requests\Response
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
     * @var int time (in seconds) after which the job is killed.
     */
    public $timeout = 600;

    /**
     * BeamJob constructor.
     * Here, mailinfo requires [recipient,subject,body]
     * @param array $request
     * @param array $retryTimeLines
     * @param array $mailInfo
     * @param bool $mock
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
            $return = [];

            parent::handle();

            if ($this->mock === false)
            {
                $return = $this->handleRequest();
            }

            $this->checkRetryOrDelete();

            return $return;

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

            $jobname = '';
            if(isset($this->request["content"]) && isset(json_decode($this->request["content"])->job_name))
            {
                $jobname = json_decode($this->request["content"])->job_name;
            }

            $this->trace->count(Metric::BEAM_RESPONSE_COUNT_TOTAL, [
                "url" => $this->request['url'],
                "job_name" => $jobname,
                "status_code" => 0,
                "success" => false
            ]);

            $this->notify();

            $this->delete();
        }
    }

    public function handleRequest()
    {
        if(isset($this->request["content"]) && isset(json_decode($this->request["content"])->job_name) &&
            json_decode($this->request["content"])->job_name === "rbl_push")
        {
            $this->request["url"] = Config::get('applications.chota_beam.url') . "/push";
        }

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
        $res = json_decode($this->response->body, true);
        $this->trace->count(Metric::BEAM_RESPONSE_COUNT_TOTAL, [
            "url" => $this->request['url'],
            "job_name" => $res->job_name,
            "status_code" => $this->response->status_code
        ]);

        return $res;
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

                $this->raiseSettlementBeamJobEvent(EventCode::BEAM_FILE_PUSH_REQUEST_RETRY);

                return;
            }

            $this->notify();

            $this->delete();

            return;
        }

        if (in_array($this->response->status_code, self::HTTP_SUCCESS_CODES, true) === true)
        {
            $this->raiseSettlementBeamJobEvent(EventCode::BEAM_FILE_PUSH_REQUEST_SUCCESS);

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
            $batchFundTransferId = null;

            $this->raiseSettlementBeamJobEvent(EventCode::BEAM_FILE_PUSH_REQUEST_FAILED);

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

        $this->fileList = implode(',', $fileList);

        $body = 'Hi,\n'. $this->mailInfo['filetype'] .' file send failed through Beam.\n'.
            'Channel  :: ' . $this->mailInfo['channel'] . '\n'.
            'Filename :: ' . $this->fileList . '\n';

        return [
            'body'      => $body,
            'subject'   => $this->mailInfo['subject'],
            'recipient' => $this->mailInfo['recipient'],
        ];
    }

    protected function raiseSettlementBeamJobEvent(array $eventCode)
    {
        $channel = null;

        // Setting this key only when beam job is called
        // for settlements file push.
        if(isset($this->mailInfo['batchFundTransferId']) === true)
        {
            if(isset($this->mailInfo['channel']) === true)
            {
                $channel = $this->mailInfo['channel'];
            }

            $batchFundTransferId = $this->mailInfo['batchFundTransferId'];

            $customProperties = [
                'channel'                           => $channel,
                'batch_fund_transfer_attempt_id'    => $batchFundTransferId,
            ];

            app('diag')->trackSettlementEvent(
                $eventCode,
                null,
                null,
                $customProperties);
        }
    }
}
