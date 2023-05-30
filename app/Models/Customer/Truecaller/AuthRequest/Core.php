<?php

namespace RZP\Models\Customer\Truecaller\AuthRequest;

use RZP\Constants\Environment;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Base\Core as BaseCore;
use RZP\Models\Customer\Truecaller\Client;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class Core extends BaseCore
{
    public function __construct()
    {
        parent::__construct();

        $this->cache = $this->app['cache'];

        $this->trace = $this->app['trace'];

        $this->encrypter = $this->app['encrypter'];
    }

    public function create(array $input = []): Entity
    {
        $truecallerAuthRequest = new Entity();

        $truecallerAuthRequest->build($input);

        $this->cacheEntityInRedis($truecallerAuthRequest);

        return $truecallerAuthRequest;
    }

    /**
     * Validates & checks the request_id status in redis and returns appropriate response
     *
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\BadRequestException
     */
    public function getTrueCallerAuthRequest(array $input): array
    {
        $response = [];

        $requestIdWithSuffix = $input['request_id'];

        $truecallerEntityJsonString = $this->getTruecallerEntityFromRedisForRequestId($requestIdWithSuffix);

        if (empty($truecallerEntityJsonString) === true)
        {
            $requestIdPrefix = $this->getRequestIdPrefix($requestIdWithSuffix);

            if ($requestIdPrefix === $requestIdWithSuffix)
            {
                $this->trace->error(TraceCode::INVALID_TRUECALLER_VERIFY_REQUEST_FORMAT, [
                    'input' => $this->getTracableCallbackData($input),
                ]);

                $this->trace->count(Metric::TRUECALLER_VERIFY_REQUEST_ERROR, [
                    Metric::LABEL_ERROR_MESSAGE => TraceCode::INVALID_TRUECALLER_VERIFY_REQUEST_FORMAT,
                ]);

                throw new BadRequestValidationFailureException('Not a valid id: ' . $requestIdWithSuffix);
            }

            $truecallerEntityJsonStringForPrefix = $this->getTruecallerEntityFromRedisForRequestId($requestIdPrefix);

            if (empty($truecallerEntityJsonStringForPrefix) === true)
            {
                $this->trace->error(TraceCode::TRUECALLER_VERIFY_REQUEST_NOT_FOUND, [
                    'input' => $this->getTracableCallbackData($input),
                ]);

                $this->trace->count(Metric::TRUECALLER_VERIFY_REQUEST_ERROR, [
                    Metric::LABEL_ERROR_MESSAGE => TraceCode::TRUECALLER_VERIFY_REQUEST_NOT_FOUND,
                ]);

                throw new BadRequestValidationFailureException('Not a valid id: ' . $requestIdWithSuffix);
            }

            $response['status'] = 'pending';

            return $response;
        }

        $truecallerEntity = json_decode($truecallerEntityJsonString, true);

        switch ($truecallerEntity[Entity::TRUECALLER_STATUS])
        {
            case Constants::ACCESS_DENIED:
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ACCESS_DENIED);

            case Constants::USE_ANOTHER_NUMBER:
            case Constants::USER_REJECTED:
                return $truecallerEntity;

            case Constants::SUCCESSFUL:
                $this->decryptUserprofile($truecallerEntity);
                return $truecallerEntity;

            default:
                $response['status'] = 'pending';
        }

        return $response;
    }

    /**
     * Validates and sets the response received from truecaller into redis for corresponding request_id
     *
     * @throws Exception\BadRequestException|BadRequestValidationFailureException
     * @throws Exception\IntegrationException
     */
    public function handleTruecallerCallback(array $input): void
    {
        if (isset($input['requestId']) === false)
        {
            $this->trace->error(TraceCode::TRUECALLER_CALLBACK_MISSING_REQUEST_ID, [
                'callback' => $this->getTracableCallbackData($input),
            ]);

            $this->trace->count(Metric::TRUECALLER_CALLBACK_ERROR, [
                Metric::LABEL_ERROR_MESSAGE => TraceCode::TRUECALLER_CALLBACK_MISSING_REQUEST_ID,
            ]);

            throw new BadRequestValidationFailureException('requestId field is required');
        }

        $requestIdPrefix = $this->getRequestIdPrefix($input['requestId']);

        $truecallerEntityJsonString = $this->getTruecallerEntityFromRedisForRequestId($requestIdPrefix);

        if(empty($truecallerEntityJsonString) === true)
        {
            $this->trace->error(TraceCode::TRUECALLER_CALLBACK_INVALID_REQUEST_ID, [
                'callback' => $this->getTracableCallbackData($input),
            ]);

            $this->trace->count(Metric::TRUECALLER_CALLBACK_ERROR, [
                Metric::LABEL_ERROR_MESSAGE => TraceCode::TRUECALLER_CALLBACK_INVALID_REQUEST_ID,
            ]);

            return;
        }

        $truecallerEntity = json_decode($truecallerEntityJsonString, true);

        $redisKeyForSuffixId = $this->getRedisKey($input['requestId']);

        if (isset($input['status']) === true)
        {
            // status is only sent for rejected cases. if a status is not part of rejected statues array,
            // we will log the data but not throw exception and continue.
            if (array_key_exists($input['status'],Constants::REJECTED_STATUES) === false)
            {
                $this->trace->info(TraceCode::TRUECALLER_CALLBACK_INVALID_STATUS, [
                    'callback' => $this->getTracableCallbackData($input),
                ]);

                $this->trace->count(Metric::TRUECALLER_CALLBACK_ERROR, [
                    Metric::LABEL_ERROR_MESSAGE => TraceCode::TRUECALLER_CALLBACK_INVALID_STATUS,
                ]);
            }

            else
            {
                $truecallerEntity[Entity::STATUS] = Constants::RESOLVED;

                $truecallerEntity[Entity::TRUECALLER_STATUS] = $input['status'];

                $this->cache->put($redisKeyForSuffixId, json_encode($truecallerEntity), Constants::TRUECALLER_USER_PROFILE_TTL);

                $this->trace->info(TraceCode::RECIEVED_TRUECALLER_CALLBACK, [
                    'callback' => $this->getTracableCallbackData($input),
                ]);

                $this->trace->count(Metric::TRUECALLER_CALLBACK_SUCCESS, [
                    Metric::LABEL_SUCCESS_MESSAGE => $input['status'],
                ]);

                return;
            }
        }

        if (isset($input['accessToken']) === false)
        {
            $this->trace->error(TraceCode::TRUECALLER_CALLBACK_MISSING_ACCESS_TOKEN, [
                'callback' => $this->getTracableCallbackData($input),
            ]);

            $this->trace->count(Metric::TRUECALLER_CALLBACK_ERROR, [
                Metric::LABEL_ERROR_MESSAGE => TraceCode::TRUECALLER_CALLBACK_MISSING_ACCESS_TOKEN,
            ]);

            throw new BadRequestValidationFailureException('accessToken field is required');
        }
        if (isset($input['endpoint']) === false)
        {
            $this->trace->error(TraceCode::TRUECALLER_CALLBACK_MISSING_ENDPOINT, [
                'callback' => $this->getTracableCallbackData($input),
            ]);

            $this->trace->count(Metric::TRUECALLER_CALLBACK_ERROR, [
                Metric::LABEL_ERROR_MESSAGE => TraceCode::TRUECALLER_CALLBACK_MISSING_ENDPOINT,
            ]);

            throw new BadRequestValidationFailureException('endpoint field is required');
        }

        try {
            $endpoint =  $this->getTruecallerEndpointFromEnv($input['endpoint']);

            $response = (new Client())->fetchUserProfile($input['accessToken'], $endpoint, $input['requestId']);

            if (isset($response['error']) === true)
            {
                $truecallerEntity[Entity::TRUECALLER_STATUS] = $response['error'];
            }
            else
            {
                $this->trace->count(Metric::TRUECALLER_CALLBACK_SUCCESS, [
                    Metric::LABEL_SUCCESS_MESSAGE => TraceCode::TRUECALLER_PROFILE_FETCHED,
                ]);

                $this->encryptUserProfile($truecallerEntity, $response);

                $truecallerEntity[Entity::TRUECALLER_STATUS] = Constants::SUCCESSFUL;
            }
        }
        catch (\Exception $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::TRUECALLER_CALLBACK_ERROR,
                []
            );

            throw $exception;
        }

        $truecallerEntity[Entity::STATUS] = Constants::RESOLVED;

        $this->cache->put($redisKeyForSuffixId, json_encode($truecallerEntity), Constants::TRUECALLER_USER_PROFILE_TTL);
    }

    protected function getRedisKey(string $id, string $service = Constants::DEFAULT_SERVICE): string
    {
        return $service . Constants::CACHE_VALUE_SEPARATOR . Constants::CACHE_PREFIX . $id;
    }

    protected function cacheEntityInRedis(Entity $truecallerAuthRequest): void
    {
        $redisKey = $this->getRedisKey($truecallerAuthRequest->getId(), $truecallerAuthRequest->getService());

        $this->cache->put($redisKey, json_encode($truecallerAuthRequest->toArray()), Constants::TRUECALLER_REQUEST_ID_TTL);
    }

    protected function getRequestIdPrefix(string $id): ?string
    {
        if (empty($id) === true)
        {
            return null;
        }

        return explode('-', $id)[0];
    }

    protected function encryptUserProfile(array &$truecallerEntity, $response): void
    {
        $truecallerEntity[Entity::USER_PROFILE][Entity::CONTACT] = $this->encrypter->encrypt($response['contact']);

        if (isset($response['email']) === true && empty($response['email']) === false)
        {
            $truecallerEntity[Entity::USER_PROFILE][Entity::EMAIL] = $this->encrypter->encrypt($response['email']);
        }
    }

    protected function decryptUserprofile(array &$truecallerEntity): void
    {
        $decryptedContact = $this->encrypter->decrypt($truecallerEntity[Entity::USER_PROFILE][Entity::CONTACT]);

        $truecallerEntity[Entity::USER_PROFILE][Entity::CONTACT] = $decryptedContact;

        if (empty($truecallerEntity[Entity::USER_PROFILE][Entity::EMAIL]) === false)
        {
            $decryptedEmail = $this->encrypter->decrypt($truecallerEntity[Entity::USER_PROFILE][Entity::EMAIL]);

            $truecallerEntity[Entity::USER_PROFILE][Entity::EMAIL] = $decryptedEmail;
        }
    }

    protected function getTruecallerEntityFromRedisForRequestId(string $requestId): ?string
    {
        $redisKey = $this->getRedisKey($requestId);

        return $this->cache->get($redisKey);
    }

    protected function getTracableCallbackData(array $input): array
    {
        $traceInput = $input;

        if (isset($traceInput['accessToken']) === true)
        {
            unset($traceInput['accessToken']);
        }

        return $traceInput;
    }

    /**
     * Mock app is an internal mock application used to mock the truecaller servers. if the callback request is from this
     * mock app, we will send the next request to mock-gateway instead of truecaller and return a hardcoded mobile number
     * as the response. refer more details at:
     * https://docs.google.com/document/d/1S412Hzy-maQPg62I2qklnHA6PLxHYtSdVeSjt7f323Q/edit?usp=sharing
     *
     * @param string $endpointFromCallback
     * @return string
     */
    protected function getTruecallerEndpointFromEnv(string $endpointFromCallback): string
    {
        if (getenv('APP_ENV') === Environment::AUTOMATION)
        {
            return env('EXTERNAL_MOCK_GO_GATEWAY_DOMAIN') . Constants::TRUECALLER_MOCK_ENDPOINT;
        }

        return $endpointFromCallback;
    }
}
