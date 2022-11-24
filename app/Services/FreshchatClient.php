<?php

namespace RZP\Services;

use File;
use Mail;
use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;
use RZP\Mail\Admin\FreshchatReport;
use RZP\Exception\BadRequestException;
use RZP\Exception\IntegrationException;

class FreshchatClient
{
    protected $app;

    protected $config;

    const CONTENT_TYPE      = 'Content-Type';
    const AUTHORIZATION     = 'Authorization';

    const REPORT_EVENT_CONVERSATION_AGENT_ASSIGNED   = 'Conversation-Agent-Assigned';

    const REPORT_FORMAT_CSV                          = 'csv';

    const CACHE_KEY_REPORT_METADATA                  = 'freshchat_extract_report_metadata';

    const REPORT_RETRIEVAL_ID_CACHE_TTL              = 30 * 60; // 30 minutes (in seconds)

    // Routes

    const PATH_EXTRACT_REPORT       = 'reports/raw';
    const PATH_RETRIEVE_REPORT      = 'reports/raw/%s';

    const FRESHCHAT_TIME_FORMAT_STRING = 'Y-m-d\TH:i:s\Z';

    public function __construct($app)
    {
        $this->app = $app;

        $this->setConfig();
    }

    public function extractReport()
    {
        $content = $this->getExtractReportContent();

        $response = $this->sendRequestAndProcessResponse(self::PATH_EXTRACT_REPORT, Requests::POST, $content);

        $cacheData = array_merge($content, ['report_id' => $response['id']]);

        $this->app['cache']->put(self::CACHE_KEY_REPORT_METADATA, $cacheData, self::REPORT_RETRIEVAL_ID_CACHE_TTL);

        return $response;
    }

    public function retrieveReport()
    {
        $metadata = $this->app['cache']->get(self::CACHE_KEY_REPORT_METADATA);

        $path = sprintf(self::PATH_RETRIEVE_REPORT, $metadata['report_id']);

        $response = $this->sendRequestAndProcessResponse($path, Requests::GET, []);

        $this->sendFreshchatEmailDump($response, $metadata);

        return ['success' => true];
    }

    protected function sendFreshchatEmailDump($response, $metadata)
    {
        $this->validateReportResponse($response);

        $linkObjects = $response['links'];

        $mailData = [
            'attachments' => [],
            'metadata'    => $metadata,
        ];


        foreach ($linkObjects as $linkObject)
        {
            $content = $this->getLinkContent($linkObject);

            $tmpfile = tempnam(sys_get_temp_dir(), 'freshchat_report');

            $handle = fopen($tmpfile, 'w+');

            fwrite($handle, $content);

            $mailData['attachments'][] = [
                'file_path' => $tmpfile,
                'name'      => sprintf('%s to %s.zip', $linkObject['from'], $linkObject['to']),
            ];
        }

        Mail::send(new FreshchatReport($mailData));

        foreach ($mailData['attachments'] as $attachment)
        {
            unlink($attachment['file_path']); // nosemgrep : php.lang.security.unlink-use.unlink-use
        }
    }

    protected function sendRequestAndProcessResponse($path, $method, $content)
    {
        $this->app['trace']->info(TraceCode::FRESHCHAT_REQUEST, [
            'path'       => $path,
            'method'     => $method,
        ]);

        $response = $this->sendRequest($path, $method, $content);

        $this->app['trace']->info(TraceCode::FRESHCHAT_RESPONSE, [
            'status_code'     => $response->status_code,
        ]);

        return $this->processResponse($response);
    }

    public function sendRequest($path, $method, $content = [], $headers = [], $options = [])
    {
        $url = $this->getBaseUrl() . $path;

        $headers = array_merge($headers, $this->getHeaders());

        if ($content !== [])
        {
            $content = json_encode($content);
        }

        return Requests::request($url, $headers, $content, $method, $options);
    }

    protected function processResponse($response): array
    {
        if ($response->status_code >= 500)
        {
            throw new IntegrationException('freshchat integration exception',
            ErrorCode::SERVER_ERROR_FRESHCHAT_INTEGRATION_ERROR);
        }

        $parsedResponse = $this->parseResponse($response);

        if ($response->status_code >= 400)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_FRESHCHAT_ERROR,
            null,
            $parsedResponse);
        }

        return $parsedResponse;
    }

    protected function setConfig()
    {
        $configPath = 'applications.freshchat';

        $this->config = $this->app['config']->get($configPath);
    }

    protected function getBaseUrl()
    {
        return $this->config['base_url'];
    }

    protected function getHeaders()
    {
        return [
            self::CONTENT_TYPE      => 'application/json',
            self::AUTHORIZATION     => $this->getAuthorizationHeader(),
        ];
    }

    protected function getAuthorizationHeader()
    {
        return 'Bearer ' . $this->config['token'];
    }

    protected function parseResponse($response)
    {
        $responseArray = json_decode($response->body, true);

        if ($responseArray === null)
        {
            return [];
        }

        return $responseArray;
    }

    protected function getExtractReportContent() : array
    {
        $end = Carbon::now()->format(self::FRESHCHAT_TIME_FORMAT_STRING);

        $start = Carbon::now()->subDay()->format(self::FRESHCHAT_TIME_FORMAT_STRING);

        return [
            'start'     => $start,
            'end'       => $end,
            'event'     => self::REPORT_EVENT_CONVERSATION_AGENT_ASSIGNED,
            'format'    => self::REPORT_FORMAT_CSV,
        ];
    }

    protected function getLinkContent($linkObject)
    {
        try
        {
            $content = stream_get_contents(fopen($linkObject['link']['href'], 'r'));

            return $content;
        }
        catch (\Throwable $throwable)
        {
            // rethrowing a new exception because the stack trace for `$throwable` contains s3 link
            throw new IntegrationException('failed to get freshchat report link content',
            ErrorCode::SERVER_ERROR_FRESHCHAT_INTEGRATION_ERROR);
        }
    }

    protected function validateReportResponse($response)
    {
        if ($response['status'] === 'PENDING')
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_FRESHCHAT_ERROR, 'status');
        }
    }
}
