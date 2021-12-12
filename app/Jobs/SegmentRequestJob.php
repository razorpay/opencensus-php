<?php


namespace RZP\Jobs;

use RZP\Constants\Mode;
use RZP\Http\Request\Requests;
use RZP\Trace\TraceCode;

class SegmentRequestJob extends RequestJob
{
    protected $queueConfigKey = 'merchant_onboarding_escalation';

    public $timeout = 600;

    public function __construct(array $request)
    {
        parent::__construct($request);

        $this->mode = Mode::LIVE;
    }

    protected function handleBatchRequest($method, $content)
    {
        $this->response = Requests::$method(
            $this->request['url'],
            $this->request['headers'],
            $content,
            $this->request['options']);
    }

    protected function handleSequentialRequest($method, $content)
    {
        $jsonBody = json_decode($content, true);
        $batchBody = $jsonBody['batch'];

        $this->trace->info(TraceCode::SEGMENT_JOB_REQUEST, [
            'request_body'  => $batchBody,
        ]);

        foreach ($batchBody as $event)
        {
            $body = json_encode([
                'batch' => [$event]
            ]);

            $this->response = Requests::$method(
                $this->request['url'],
                $this->request['headers'],
                $body,
                $this->request['options']);

            $this->trace->info(TraceCode::SEGMENT_JOB_REQUEST, [
                'type'          => 'sequential',
                'request_body'  => $body,
                'response_body' => $this->response
            ]);
        }
    }

    protected function handleRequest()
    {
        $this->traceRequest();

        $timeStarted = microtime(true);

        $method = $this->request['method'];

        $content = $this->request['content'];

        $batch = $this->request['batch'];

        if(empty($content) === true)
        {
            return;
        }

        if($batch === true)
        {
            $this->handleBatchRequest($method, $content);
        }
        else
        {
            $this->handleSequentialRequest($method, $content);
        }

        $timeTaken = microtime(true) - $timeStarted;

        $this->trace->info(
            $this->traceCodeResponse,
            [
                'time_taken' => $timeTaken,
                'attempts'   => $this->attempts(),
                'response'   => $this->response->body
            ]);

        return [
            self::STATUS_CODE => $this->response->status_code,
            self::BODY        => json_decode($this->response->body, true),
        ];
    }
}
