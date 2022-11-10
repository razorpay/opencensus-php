<?php

namespace RZP\Services;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use Razorpay\Trace\Logger;
use \Illuminate\Http\Request;
use RZP\Constants\Environment;
use GuzzleHttp\Client as Guzzle;
use Illuminate\Support\Facades\App;
use RZP\Exception\IntegrationException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

class CyberHelpdeskClient
{
    const CONTENT_TYPE      = 'content-type';
    const ADMIN_EMAIL_ID    = 'admin_email_id';
    const CONTENT_TYPE_JSON = 'application/json';

    protected $client;

    protected $options = [];

    /**
     * @var Logger
     */
    protected $trace;

    protected $config;


    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->config = $app['config']->get('applications.cyber_crime_helpdesk');

        $this->trace = $app['trace'];

        $this->client = new Guzzle([
                                       'base_uri' => $this->config['base_url'],
                                       'auth'     => [
                                           $this->config['auth']['username'],
                                           $this->config['auth']['secret'],
                                       ]]);
    }

    protected function formatResponse($response)
    {
        $responseArray = json_decode($response->getBody(), true);

        $this->trace->info(TraceCode::CYBER_HELPDESK_DOWNSTREAM_RESPONSE, ['response' => $responseArray]);

        return $responseArray['data'] ?? null;
    }

    /**
     * @throws GuzzleException
     * @throws IntegrationException
     * @throws Exception\BadRequestException
     */
    public function forwardRequestToCyberHelpdesk(Request $request, string $adminEmail)
    {
        // v1/cyber_helpdesk -> 17 chars
        $requestUri = substr($request->path(), 17);
        $url        = $this->config['base_url'] . $requestUri;

        $this->options['headers']['Content-Type']       = $request->header('Content-Type');
        $this->options['headers'][self::ADMIN_EMAIL_ID] = $adminEmail;

        $input = $request->all();

        $this->options = array_merge(
            $this->options,
            $this->getOptionsData($input, $request->header('Content-Type'), $adminEmail)
        );

        try
        {
            if ($this->app['env'] === Environment::TESTING)
            {
                return $this->options;
            }

            $response = $this->client->request($request->method(), $url, $this->options);
        }
        catch (RequestException $e)
        {
            $this->trace->error(TraceCode::CYBER_HELPDESK_INTEGRATION_ERROR, ["error" => $e->getMessage()]);

            // To catch exactly error 400 use
            if ($e->hasResponse())
            {
                if ($e->getResponse()->getStatusCode() == '400')
                {
                    $resp = $this->formatResponse($e->getResponse());

                    throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, null,
                                                            $resp['errors'][0]);
                }
            }
        }

        return $this->formatResponse($response);
    }

    protected function getOptionsData($input, $contentType, $adminEmail)
    {
        if ($contentType == self::CONTENT_TYPE_JSON or ($this->app['env'] === Environment::TESTING))
        {
            return $this->processJsonInput($input);
        }

        return $this->processFileInput($input, $adminEmail);
    }

    protected function processJsonInput($input): array
    {
        return [
            'json' => $input,
        ];
    }

    protected function processFileInput($input, $adminEmail): array
    {
        $file = $input['file'];

        $movedFile = $file->move(storage_path('files/filestore'), $file->getClientOriginalName());

        $filePath = $movedFile->getPathname();

        $data = [
            [
                'name'     => 'file',
                'contents' => fopen($filePath, 'r'),
            ],
            [
                'name'     => 'document_type',
                'contents' => 'fir',
            ]
        ];

        return [
            'multipart' => $data,
            'headers'   => [
                'admin_email_id' => $adminEmail,
            ],
        ];

    }
}
