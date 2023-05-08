<?php

namespace RZP\Services;

use Request;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Http\OAuthCache;
use RZP\Http\RequestHeader;
use RZP\Http\Request\Requests;

use Razorpay\OAuth\Token;
use Razorpay\OAuth\Client;
use Razorpay\OAuth\Application;
use RZP\Models\Merchant\Account;
use RZP\Models\Merchant\AccessMap;
use RZP\Models\Merchant\MerchantApplications;

class AuthService
{
    use OAuthCache;
    const REQUEST_TIMEOUT = 30; // In seconds
    const ID = 'id';

    const APPLICATIONS   = 'applications';
    const CLIENTS        = 'clients';
    const TOKENS         = 'tokens';
    const REFRESH_TOKENS = 'refresh_tokens';

    protected $baseUrl;

    //
    // API talks to Authentication Service's APIs using HTTP Basic authentication.
    // Following are those user name and pass.
    //
    protected $key;

    protected $secret;

    protected $config;

    protected $trace;

    public function __construct($app = null)
    {
        if (empty($app) === true)
        {
            $app = app();
        }

        $this->key     = 'rzp';
        $this->trace   = $app['trace'];
        $this->config  = $app['config']->get('applications.auth_service');
        $this->baseUrl = $this->config['url'];
        $this->secret  = $this->config['secret'];
        $cacheStore    = $app['config']->get('cache.throttle');
        $this->cache   = $app['cache']->store($cacheStore);
    }

    public function createApplication(array $input, string $merchantId, string $type = null) : array
    {
        $input[Application\Entity::MERCHANT_ID] = $merchantId;

        if ($type !== null)
        {
            $input[Application\Entity::TYPE] = $type;
        }

        return $this->sendRequest('applications', Requests::POST, $input);
    }

    public function refreshClients(string $appId, string $merchantId) : array
    {
        $input[Application\Entity::MERCHANT_ID] = $merchantId;

        $input[Client\Entity::APPLICATION_ID] = $appId;

        $this->trace->info(
            TraceCode::PARTNER_REFRESH_CLIENTS,
            [
                'application_id' => $appId,
                'merchant_id'    => $merchantId,
            ]
        );

        return $this->sendRequest('clients', Requests::PUT, $input);
    }

    public function getApplication(string $id, string $merchantId) : array
    {
        $input = [Application\Entity::MERCHANT_ID => $merchantId];

        return $this->sendRequest('applications/' . $id, Requests::GET, $input);
    }

    public function fetchMultiple(string $entity, array $input)
    {
        switch ($entity)
        {
            case self::APPLICATIONS:
            case self::CLIENTS:
            case self::TOKENS:
            case self::REFRESH_TOKENS:
                return $this->sendRequest('admin/entities/'. $entity, Requests::GET, $input);
        }

        return null;
    }

    public function fetch(string $entity, string $id, array $input)
    {
        switch ($entity)
        {
            case self::APPLICATIONS:
            case self::CLIENTS:
            case self::TOKENS:
            case self::REFRESH_TOKENS:
                return $this->sendRequest('admin/entities/'. $entity. '/'. $id, Requests::GET);
        }

        return null;
    }

    public function getMultipleApplications(array $input, string $merchantId) : array
    {
        $input[Application\Entity::MERCHANT_ID] = $merchantId;

        return $this->sendRequest('applications', Requests::GET, $input);
    }

    public function deleteApplication(string $id, string $merchantId, bool $deletePartnerMappings = true) : array
    {
        $input = [Application\Entity::MERCHANT_ID => $merchantId];

        $this->trace->info(
            TraceCode::PARTNER_DELETE_APPLICATION,
            [
                'application_id' => $id,
                'merchant_id'    => $merchantId,
            ]
        );
        $result = $this->sendRequest('applications/' . $id, Requests::PUT, $input);
        if ($deletePartnerMappings === true)
        {
            // deletes the access mapping for the application
            (new AccessMap\Core)->deleteAccessMapByApplicationId($id);

            // delete merchant and application mapping
            (new MerchantApplications\Core)->deleteByApplication($id);
        }
        return $result;
    }

    /**
     * migrates a single reseller partner to aggregator partner
     *
     * @param   string       $merchantId         The Merchant's ID to whom the application belongs
     * @param   array        $appIdsToRestore    The app IDs to restore
     * @param   array|null   $appIdsToDelete     The app IDs to delete
     *
     * @return  mixed
     * @throws  Exception\ServerErrorException
     */
    public function restoreApplication(string $merchantId, array $appIdsToRestore, array $appIdsToDelete = null) : array
    {
        $input = [
            Application\Entity::MERCHANT_ID => $merchantId,
            'app_ids_to_restore'            => $appIdsToRestore,
            'app_ids_to_delete'             => $appIdsToDelete
        ];

        $this->trace->info(TraceCode::PARTNER_RESTORE_APPLICATION, $input);

        return $this->sendRequest('applications/restore', Requests::PUT, $input);
    }

    public function updateApplication(string $id, array $input, string $merchantId) : array
    {
        $input[Application\Entity::MERCHANT_ID] = $merchantId;

        return $this->sendRequest('applications/' . $id, Requests::PATCH, $input);
    }

    public function getMerchantAuthorizedApplications(string $merchantId) : array
    {
        $input[Token\Entity::MERCHANT_ID] = $merchantId;

        return $this->sendRequest('applications/submerchant', Requests::GET, $input);
    }

    public function revokeApplicationAccess(string $merchantId, string $appId) : array
    {
        $input[Token\Entity::MERCHANT_ID] = $merchantId;

        return $this->sendRequest('tokens/submerchant/revoke_for_application/' . $appId, Requests::PUT, $input);
    }

    public function getTokens(array $input, string $merchantId) : array
    {
        $input[Token\Entity::MERCHANT_ID] = $merchantId;

        $response =  $this->sendRequest('tokens', Requests::GET, $input);

        if (array_key_exists('items', $response) === true)
        {
            foreach ($response['items'] as &$item)
            {
                unset($item['application']['client_details']);
            }
        }

        return $response;
    }

    public function getToken(string $id, array $input, string $merchantId) : array
    {
        $input[Token\Entity::MERCHANT_ID] = $merchantId;

        return $this->sendRequest('tokens/' . $id, Requests::GET, $input);
    }

    public function revokeToken(string $id, array $input, string $merchantId) : array
    {
        $input[Token\Entity::MERCHANT_ID] = $merchantId;

        $tokenTag = $this->getCacheTagsForTokenId($id);
        $cacheTag = $this->cache->tags($tokenTag)->get($id);

        $this->cache->tags($cacheTag)->flush();
        $this->cache->tags($tokenTag)->flush();

        return $this->sendRequest('tokens/' . $id, Requests::PUT, $input);
    }

    public function revokeTokenForMerchantUser(array $input) : array
    {
        return $this->sendRequest('revokeTokensForMobileApp', Requests::POST, $input);
    }

    public function revokeAccessToken(array $input) : array
    {
        return $this->sendRequest('revoke', Requests::POST, $input);
    }

    public function refreshAccessToken(array $input) : array
    {
        return $this->sendRequest('token', Requests::POST, $input);
    }

    public function createPartnerToken(string $appId, string $partnerMerchantId, string $subMerchantId) : array
    {
        $input = [
            Client\Entity::APPLICATION_ID => $appId,
            'partner_merchant_id'         => $partnerMerchantId,
            'sub_merchant_id'             => $subMerchantId,
        ];

        return $this->sendRequest('tokens/partner', Requests::POST, $input);
    }

    public function createOAuthMigrationToken(array $input) : array
    {
        return $this->sendRequest('tokens/internal', Requests::POST, $input);
    }

    public function createToken(array $input) : array
    {
        return $this->sendRequest('token', Requests::POST, $input);
    }

    protected function sendRequest(
        string $url,
        string $method,
        array $data = null)
    {
        $request = $this->getRequestParams($url, $method, $data);

        $this->traceRequest($request);

        try
        {
            $response = Requests::request(
                $request['url'],
                $request['headers'],
                $request['content'],
                $request['method'],
                $request['options']);
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException($e);

            throw new Exception\ServerErrorException(
                'Error completing the request',
                ErrorCode::SERVER_ERROR_AUTH_SERVICE_FAILURE,
                [
                    'message' => $e->getMessage(),
                ]
            );
        }
        return $this->parseAndReturnResponse($response);
    }

    protected function parseAndReturnResponse($res)
    {
        $code = $res->status_code;
        //
        // If returned status code is 2XX, everything is fine
        // and just return the decoded JSON body.
        //
        if (in_array($code, [200, 201], true))
        {
            return json_decode($res->body, true);
        }

        //
        // If returned status code is 400 we parse the JSON body and
        // raise validation exception in API format.
        //
        elseif ($code === 400)
        {
            $error = json_decode($res->body, true)['error']['description'];

            throw new Exception\BadRequestValidationFailureException($error);
        }

        //
        // Else we return a generic API's bad request error with a message
        // and log response body in trace.
        //
        else
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_AUTH_SERVICE_ERROR,
                null,
                json_decode($res->body, true)
            );
        }
    }

    protected function getRequestParams(
        string $url,
        string $method,
        array $data = null) : array
    {
        $url = $this->baseUrl . $url;

        if ($data === null)
        {
            $data = '';
        }

        $headers['Accept'] = 'application/json';
        $headers[RequestHeader::DEV_SERVE_USER] = Request::header(RequestHeader::DEV_SERVE_USER);

        $options = [
            'timeout' => self::REQUEST_TIMEOUT,
            'auth'    => [$this->key, $this->secret],
        ];

        return [
            'url'     => $url,
            'method'  => $method,
            'headers' => $headers,
            'options' => $options,
            'content' => $data,
        ];
    }

    protected function traceRequest(array $request)
    {
        unset($request['options']['auth']);

        $this->trace->info(TraceCode::AUTH_SERVICE_REQUEST, $request);
    }
}
