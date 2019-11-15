<?php

namespace RZP\Services;

use Requests;
use GuzzleHttp\Client;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\PaperMandate;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;

class HyperVerge
{
    protected $config;

    protected $app;

    protected $baseUrl;

    protected $appId;

    protected $appKey;

    protected $client;

    protected $trace;

    const REQUEST_TIMEOUT = 10;

    const GENERATE_NACH = 'populateNACH';
    const EXTRACT_NACH  = 'extractNach';

    const RESULT = 'result';

    const URLS = [
        self::GENERATE_NACH => 'populateNACH',
        self::EXTRACT_NACH  => 'readNACH',
    ];

    public function __construct($app)
    {
        $this->app = $app;

        $this->config = $this->app['config']->get('applications.hyper_verge');
        $this->trace  = $this->app['trace'];

        $this->baseUrl = $this->config['url'];
        $this->appId   = $this->config['app_id'];
        $this->appKey  = $this->config['app_key'];

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'connect_timeout' => self::REQUEST_TIMEOUT
        ]);
    }

    public function generateNACH(array $input, PaperMandate\Entity $paperMandate)
    {
        $this->trace->info(
            TraceCode::PAPER_MANDATE_CREATE_FORM_REQUEST_TO_HYPERVERGE,
            [
                'paper_mandate_id' => $paperMandate->getPublicId(),
            ]);

        $headers = $this->getHeaders($paperMandate);

        $headers['Content-Type'] = 'application/json';

        $timeStarted = microtime(true);

        $response = $this->client->request(
            Requests::POST,
            self::URLS[self::GENERATE_NACH],
            [
                'body' => json_encode($input, JSON_UNESCAPED_SLASHES),
                'headers'   => $headers,
            ]
        );

        $timeTaken = microtime(true) - $timeStarted;

        $this->trace->info(
            TraceCode::TIME_TAKEN_BY_HYPERVERGE_TO_GENERATE_FORM,
            [
                'paper_mandate_id' => $paperMandate->getPublicId(),
                'time_taken'       => $timeTaken,
            ]);

        $data = json_decode($response->getBody()->getContents(), true);

        return $data[self::RESULT];
    }

    public function extractNACHWithOutputImage(array $input, PaperMandate\Entity $paperMandate)
    {
        $this->trace->info(
            TraceCode::PAPER_MANDATE_EXTRACT_FORM_REQUEST_TO_HYPERVERGE,
            [
                'paper_mandate_id' => $paperMandate->getPublicId(),
            ]);

        $headers = $this->getHeaders($paperMandate);

        $response = null;

        $timeStarted = microtime(true);

        try
        {
            $response = $this->client->request(Requests::POST, self::URLS[self::EXTRACT_NACH], [
                'multipart' => [
                    [
                        'name'     => 'image',
                        'contents' => fopen($input[PaperMandate\Entity::FORM_UPLOADED], 'r')
                    ],
                    [
                        'name'     => 'enableOutputJPEG',
                        'contents' => 'yes'
                    ]
                ],
                'headers'   => $headers,
            ]);
        }
        catch (\Exception $e)
        {
            if (($e->getCode() >= 400) and ($e->getCode() < 500))
            {
                throw (new BadRequestException(
                    ErrorCode::BAD_REQUEST_UNABLE_TO_READ_NACH_FORM,
                    null,
                    [$input, $paperMandate->toArrayPublic(), $e],
                    'unable to read NACH form'
                ));
            }
            else
            {
                throw new ServerErrorException(
                    'HyperVerge error',
                    ErrorCode::SERVER_ERROR_NACH_EXTRACTION_FAILED,
                    [$input, $paperMandate->toArrayPublic()],
                    $e
                );
            }
        }

        $timeTaken = microtime(true) - $timeStarted;

        $this->trace->info(
            TraceCode::TIME_TAKEN_BY_HYPERVERGE_TO_EXTRACT_FORM,
            [
                'paper_mandate_id' => $paperMandate->getPublicId(),
                'time_taken'       => $timeTaken,
            ]);

        $data = json_decode($response->getBody()->getContents(), true);

        return $data[self::RESULT];
    }

    private function getHeaders(PaperMandate\Entity $paperMandate)
    {
        return [
            'transactionId' => $paperMandate->getPublicId(),
            'appKey'        => $this->appKey,
            'appId'         => $this->appId,
        ];
    }
}
