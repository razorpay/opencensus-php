<?php

namespace RZP\Services;

use App;
use Config;
use Request;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Trace\TraceCode;
use RZP\Http\RequestHeader;
use RZP\Http\Request\Requests;
use RZP\Models\Merchant\Repository as MerchantRepo;
use RZP\Models\Merchant\Balance\Type as ProductType;

/**
 * Class MasterOnboardingService
 * @package RZP\Services
 *
 * No validations will happen here.
 * This will just call the right endpoints and return the responses as is
 * If there is an error thrown from the MicroService, that same error with
 * the right error code will be sent back to the caller
 *
 */
class MasterOnboardingService
{
    const CONTENT_TYPE                      = 'content-type';

    const X_RAZORPAY_TASKID_HEADER          = 'X-Razorpay-TaskId';

    const X_REQUEST_ID                      = 'X-Request-ID';

    protected $baseUrl;

    protected $config;

    protected $ba;

    protected $timeOut;

    protected $trace;

    protected $app;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app     = $app;

        $this->ba      = $app['basicauth'];

        $this->config  = $app['config']->get('applications.master_onboarding');

        $this->baseUrl = $this->config['url'];

        $this->timeOut = $this->config['timeout'];

        $this->trace   = $app['trace'];
    }

    private function getAuthHeaders() : array
    {
        return [
            $this->config['key'],
            $this->config['upstream_secret'],
        ];
    }

    private function getAdminRequestHeaders(array $data = []) : array
    {
        $headers = [
            'Grpc-metadata-X-Admin-Id'        => $this->ba->getAdmin()->getId() ?? '',
            'Grpc-metadata-X-Admin-Email'     => $this->ba->getAdmin()->getEmail() ?? '',
            'Grpc-metadata-X-Admin-Name'      => $this->ba->getAdmin()->getName() ?? '',
            'Grpc-metadata-X-Razorpay-TaskId' => $this->app['request']->getTaskId(),
            'X-Auth-Type'                     => 'admin'
        ];

        return array_merge($headers, $this->getProxyHeadersForAdminRequest($data));
    }

    /**
     * @throws BadRequestException
     */
    private function getProxyHeadersForAdminRequest(array $data = []) : array
    {
        // Merchant context is set via X-Razorpay-Account header
        $merchantId = $data['merchant_id'] ?? $this->ba->getMerchant()->getId();

        if (empty($merchantId))
        {
            return [];
        }

        $repo = new MerchantRepo();

        $merchant = $repo->findOrFail($merchantId);

        $user = $merchant->owners(ProductType::BANKING)->first();

        $userId = optional($user)->getId();

        if (empty($userId) === true)
        {
            // Adding this since ops team can start onboarding for PG merchants as well
            $user = $merchant->owners(ProductType::PRIMARY)->first();

            $userId = optional($user)->getId();
        }

        // if userId is still not resolved, throw error
        if (empty($userId) === true) {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_USER_ID_COULD_NOT_BE_RESOLVED);
        }

        return [
            'Grpc-metadata-X-Merchant-Id'       => $merchantId,
            'Grpc-metadata-X-Dashboard-User-Id' => $userId,
            'Grpc-metadata-X-Service'           => ProductType::BANKING,
        ];
    }

    private function getProxyRequestHeaders() : array
    {
        return [
            'Grpc-metadata-X-Merchant-Id'       => $this->ba->getMerchant()->getId() ?? '',
            'Grpc-metadata-X-Merchant-Email'    => $this->ba->getMerchant()->getEmail() ?? '',
            'Grpc-metadata-X-Dashboard-User-Id' => $this->ba->getUser()->getId() ?? '',
            'Grpc-metadata-X-Service'           => $this->ba->getRequestOriginProduct(),
            'Grpc-metadata-X-Razorpay-TaskId'   => $this->app['request']->getTaskId(),
            'Grpc-metadata-X-User-Role'         => $this->ba->getUserRole() ?? '',
            'Grpc-metadata-X-Auth-Type'         => 'proxy',
        ];
    }

    private function getMOBHeaders() : array
    {
        return [
            self::CONTENT_TYPE             => 'application/json',
            self::X_RAZORPAY_TASKID_HEADER => $this->app['request']->getTaskId(),
            self::X_REQUEST_ID             => $this->app['request']->getId(),
        ];
    }

    public function getPathWithQueryString(string $method, string $path, array $data = [])
    {
        if (($method === 'GET') or
            ($method === 'DELETE'))
        {
            if (empty($data) === false)
            {
                $queryStringFromData = http_build_query($data);

                $queryString = parse_url($path, PHP_URL_QUERY);

                if (empty($queryString) === true)
                {
                    $path = $path . '?' . $queryStringFromData;
                }
                else
                {
                    $path = $path . '&' . $queryStringFromData;
                }
            }
        }
        return $path;
    }

    public function sendRequestAndParseResponse(string $path, string $method, array $data = [], bool $isAdmin = true, array $headers = [])
    {
        $path = $this->getPathWithQueryString($method, $path, $data);

        $url = $this->baseUrl . '/v1/' . $path;

        if (count($headers) == 0)
        {
            $headers = ($isAdmin === true) ? $this->getAdminRequestHeaders($data) : $this->getProxyRequestHeaders();
        }

        $headers = array_merge($headers, $this->getMOBHeaders());

        $devServeHeader = Request::header(RequestHeader::DEV_SERVE_USER);

        if (empty($devServeHeader) === false)
        {
            $headers["Grpc-metadata-" . RequestHeader::DEV_SERVE_USER] = $devServeHeader;

            $headers[RequestHeader::DEV_SERVE_USER] = $devServeHeader;
        }

        $options = [
            'auth'    => $this->getAuthHeaders(),
            'timeout' => $this->timeOut,
        ];

        try
        {
            $content = null;

            if (in_array($method, [Requests::POST, Requests::PATCH]) === true)
            {
                $content = '';
                if (empty($data) === false)
                {
                    $content = json_encode($data, JSON_UNESCAPED_SLASHES);
                }
            }

            $this->trace->info(TraceCode::MASTER_ONBOARDING_SERVICE_REQUEST, [
                'url'           => $url,
                'method'        => $method,
                'headers'       => $headers,
                'input'         => $data,
            ]);

            $response = Requests::request(
                $url,
                $headers,
                $content,
                $method,
                $options
            );

            return $this->formatResponse($response);
        }
        catch (\WpOrg\Requests\Exception $e)
        {
            $data = [
                'exception'     => $e->getMessage(),
                'url'           => $url,
                'method'        => $method,
                'input'         => $data,
            ];

            $this->trace->error(TraceCode::MASTER_ONBOARDING_SERVICE_ERROR, $data);

            $this->trace->count(Metric::MASTER_ONBOARDING_SERVICE_ERROR_COUNT, [
                'url'           => $url,
                'method'        => $method,
            ]);

            throw $e;
        }
    }

    public function mobMigration(array $input): array
    {
        foreach ($input as $merchantId)
        {
            $repo = new MerchantRepo();

            $merchant = $repo->findOrFail($merchantId);

            $users = $merchant->owners(ProductType::BANKING)->get();

            if (count($users) <= 0)
            {
                $users = $merchant->owners(ProductType::PRIMARY)->get();
            }

            $headers = [
                'Grpc-metadata-X-Merchant-Id'       => $merchant->getId(),
                'Grpc-metadata-X-Dashboard-User-Id' => $user->getId(),
                'Grpc-metadata-X-Service'           => 'banking',
                'Grpc-metadata-X-Razorpay-TaskId'   => $this->app['request']->getTaskId(),
            ];

            foreach ($users as $user)
            {
                $result = $this->sendRequestAndParseResponse("intents","POST", [
                    'source'              => 'signup',
                    'product_bundle_name' => 'ca_v1',
                    "apply_application"   => true,
                ], $headers);

                $workflowId = $result['intent']['application']['obs_workflow_id'];

                $this->sendRequestAndParseResponse("save_workflow","POST", [
                    "id"            => $workflowId,
                    "field_data"    => [
                        [
                            "field_name"    => "request",
                            "field_value"   => [
                                "flow"      => "eligibility_check_and_application_creation",
                                "payload"   => [
                                    "" => "",
                                ]
                            ]
                        ]
                    ]
                ], false, $headers);
            }
        }

        return $input;
    }

    protected function formatResponse($response)
    {
        $responseArray = [];

        $responseBody = $response->body;

        if (empty($responseBody) === false)
        {
            $responseArray = json_decode($responseBody, true);
        }

        // TODO: Remove this once MOB experiment is ramped upto 100%
        $this->trace->info(TraceCode::MASTER_ONBOARDING_SERVICE_RESPONSE, [
            'response' => $responseArray,
        ]);

        return $responseArray;
    }
}
