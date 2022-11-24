<?php

namespace RZP\Services;

use App;

use \WpOrg\Requests\Response;
use \WpOrg\Requests\Exception as Requests_Exception;

use RZP\Error\ErrorCode;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;
use RZP\Base\RepositoryManager;
use RZP\Http\BasicAuth\BasicAuth;

class UfhClient implements ExternalService
{
    // Entity Fetch related
    const REQUEST_TIMEOUT   = 30;                // In secs
    const FILES_PATH        = '/v1/filesAdmin';

    // Headers
    const MERCHANT_HEADER       = 'X-Merchant-Id';
    const CONTENT_TYPE_HEADER   = 'Content-Type';

    protected $config;

    protected $trace;

    /** @var  BasicAuth */
    protected $ba;

    /**
     * Repository manager instance
     * @var RepositoryManager
     */
    protected $repo;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->trace     = $app['trace'];

        $this->ba        = $app['basicauth'];

        $this->repo      = $app['repo'];

        $this->config    = $app['config']['applications.ufh'];
    }

    protected function fetchHeadersFromInput(array $input)
    {
        $merchantId = $this->ba->getMerchantId();

        if (($this->ba->isAdminAuth() === true) && (empty($merchantId) === true))
        {
            $merchantId = $this->repo->merchant->getSharedAccount()->getId();
        }

        return [
            self::MERCHANT_HEADER => $input['merchant_id'] ?? $merchantId,
        ];
    }

    public function fetchMultiple(string $entity, array $input)
    {
        // Here entity is always 'files' as we don't have any other
        // entity in UFH as of now.
        $headers = $this->fetchHeadersFromInput($input);

        return $this->createAndSendRequest(Requests::GET, self::FILES_PATH, $input, $headers);
    }

    public function fetch(string $entity, string $id, array $input)
    {
        $path = self::FILES_PATH . '/' . $id;

        $headers = $this->fetchHeadersFromInput($input);

        return $this->createAndSendRequest(Requests::GET, $path, [], $headers, $id);
    }

    public function createAndSendRequest(
        string $method,
        string $path,
        array $input = [],
        array $headers = [],
        string $id = null): array
    {
        //
        // In case UFH is to be mocked, don't make
        // any external call and just return dummy
        // files data array.
        //
        if ($this->config['mock'] === true)
        {
            if (empty($id) === false)
            {
                return (new Mock\UfhClient)->getFileById($id);
            }

            return (new Mock\UfhClient)->getFiles($id);
        }

        $options = [
            'timeout' => self::REQUEST_TIMEOUT,
            'auth'    => $this->getAuthHeaders(),
        ];

        $request = [
            'url'     => $this->config['url'] . $path,
            'method'  => $method,
            'content' => $input,
            'options' => $options,
            'headers' => $headers
        ];

        $this->trace->info(
            TraceCode::UFH_SERVICE_API_REQUEST,
            $this->getTraceableRequest($request));

        $requestTime = microtime(true);

        $response = $this->sendRequest($request);

        $timeTaken = get_diff_in_millisecond($requestTime);

        $this->traceUfhServiceResponse($response, $timeTaken);

        return json_decode($response->body, true);
    }

    protected function traceUfhServiceResponse($response, $timeTaken)
    {
        $payload = ['status_code' => $response->status_code, 'body' => null];

        // Trace body only if response is non 200
        if ($response->status_code !== 200)
        {
            $payload['body'] = $response->body;
        }

        $this->trace->info(
            TraceCode::UFH_SERVICE_API_RESPONSE,
            [
                'response'      => $payload,
                'time_taken'    => $timeTaken
            ]);
    }

    /**
     * Filters request array and returns only traceable data
     *
     * @param  array  $request
     *
     * @return array
     */
    protected function getTraceableRequest(array $request): array
    {
        return array_only($request, ['url', 'method', 'content', 'headers']);
    }

    /**
     * Returns auth headers to be used to make requests to external UFH
     * service.
     *
     * @return array
     */
    protected function getAuthHeaders(): array
    {
        return [
            $this->config['admin_auth']['username'],
            $this->config['admin_auth']['password'],
        ];
    }

    protected function sendRequest(array $request)
    {
        try
        {
            $request['headers'][self::CONTENT_TYPE_HEADER] = 'application/json';

            $response = Requests::request(
                $request['url'],
                $request['headers'],
                $request['content'],
                $request['method'],
                $request['options']);

            $this->validateResponse($response);

            return $response;

        }
        catch (Requests_Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::UFH_INTEGRATION_ERROR,
                $this->getTraceableRequest($request));

            throw new Exception\IntegrationException('
                Could not receive proper response from UFH service');
        }
    }

    protected function validateResponse($response)
    {
        if ($response->status_code !== 200)
        {
            $payload['body'] = $response->body;

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_UFH_INTEGRATION, null, $payload);
        }
    }
}
