<?php

namespace RZP\Models\Customer\Truecaller;

use App;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Http\Request\Requests;
use RZP\Models\Customer\Truecaller\AuthRequest\Constants;
use RZP\Models\Customer\Truecaller\AuthRequest\Metric;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

/**
 * handles all communication with Truecaller APIs
 */
class Client
{
    protected $app;
    protected $trace;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();
        $this->trace = $this->app['trace'];
    }

    /**
     * This method will hit truecaller's endpoint to fetch user profile. endpoint is passed in method argument,
     * Users' accessToken will be set in header to uniquely identify the user whose profile is being fetched.
     *
     * @throws Exception\BadRequestException|Exception\IntegrationException
     */
    public function fetchUserProfile(string $accessToken, string $endpoint, string $requestId): array
    {
        $userProfile = [];

        $response = $this->sendRequest($endpoint, 'GET', $accessToken);

        if (isset($response['error']) === true)
        {
            $userProfile['error'] = $response['error'];

            return $userProfile;
        }

        $contact = $response['body']['phoneNumbers'][0] ?? null;

        $email = $response['body']['onlineIdentities']['email'] ?? null;

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false)
        {
            $email = null;
        }

        $userProfile['contact'] = $contact;
        $userProfile['email']   = $email;

        return $userProfile;
    }

    /**
     * Makes a HTTP request and returns its response
     *
     * @param string $endpoint
     * @param string $method
     * @param string|null $accessToken
     * @return array
     * @throws Exception\BadRequestException
     * @throws Exception\IntegrationException
     */
    protected function sendRequest(string $endpoint, string $method, string $accessToken = null): array
    {
        $headers = [];

        if($accessToken !== null)
        {
            $headers = $this->getHeaders($accessToken);
        }

        $options = $this->getOptions();

        try
        {
            $response = Requests::request($endpoint, $headers,null, $method, $options);

            $statusCode = $response->status_code;

            if ($statusCode !== 200)
            {
                $this->trace->error(TraceCode::TRUECALLER_NON_TWO_HUNDERED_ERROR, [
                    'status_code' => $statusCode,
                    'response' => $response,
                ]);
                $this->trace->count(METRIC::TRUECALLER_SERVICE_ERROR, [
                    METRIC::LABEL_STATUS_CODE => $statusCode
                ]);
            }

            if ($statusCode === 401)
            {
                $this->trace->count(METRIC::TRUECALLER_SERVICE_ERROR, [
                    METRIC::LABEL_ERROR_MESSAGE => TraceCode::TRUECALLER_PROFILE_ACCESS_DENIED,
                ]);

                $responseArr['error'] = Constants::ACCESS_DENIED;

                return $responseArr;
            }

            if ($statusCode !== 200)
            {
                throw new Exception\IntegrationException(ErrorCode::SERVER_ERROR);
            }

            return $this->parseResponse($response);
        }
        catch (\Exception $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::TRUECALLER_REQUEST_ERROR, [
                    'exception' => $exception->getMessage(),
                ]
            );

            throw $exception;
        }
    }

    /**
     * This method is used to set user's accessToken in request header.
     *
     * @param string $accessToken
     * @return array
     */
    private function getHeaders(string $accessToken): array
    {
        $token = 'Bearer ' . $accessToken;

        return [
            'Authorization' => $token,
            'Cache-Control' => 'no-cache',
        ];
    }

    /**
     * This method is used to parse the response we get from truecaller
     *
     * @param $response
     * @return array
     */
    protected function parseResponse($response): array
    {
        if ($response === null || $response->body === null)
        {
            return [];
        }
        return [
            'body'         => json_decode($response->body, true),
        ];
    }

    private function getOptions(): array
    {
        return [
            'timeout' => Constants::TRUECALLER_REQUEST_TIMEOUT,
        ];
    }
}
