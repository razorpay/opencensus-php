<?php

namespace RZP\Models\OAuthToken;

use Request;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\Tracer;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Product;
use RZP\Models\User as User;
use RZP\Constants\HyperTrace;
use RZP\Exception\BadRequestException;
use RZP\Models\User\Constants as UserConstants;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\OAuthApplication\Constants as OAuthApplicationConstants;

use Razorpay\Trace\Logger as Trace;

class Service extends Base\Service
{

    /**
     * @var \RZP\Services\AuthService
     */
    protected $authservice;

    protected $oAuthAppService;

    public function __construct()
    {
        parent::__construct();

        $this->authservice = $this->app['authservice'];
        $this->oAuthAppService = new \RZP\Models\OAuthApplication\Service();
    }

    public function create()
    {
        $input = Request::all();

        $entity = Tracer::inspan(['name' => HyperTrace::CREATE_OAUTH_TOKEN_CORE], function () use($input) {

            $this->core()->create($input);
        });

        return $entity;
    }

    /**
     * @throws Exception\ServerErrorException
     * @throws Exception\BadRequestException
     */
    public function createForAppleWatch(array $input, User\Entity $user, MerchantEntity $merchant, string $mode): array
    {

        (new Validator())->validateCreateForAppleWatch($input, $user->getId(),$merchant->getId(),
            $merchant->isActivated() === true, $mode);

        (new User\Core)->verifyOtp($input + ['action' => 'apple_watch_token'],
                                    $merchant,
                                    $user,
                                    $this->app->environment('production') === false);


        $oAuthApp = $this->oAuthAppService->createOrGetApplication($merchant,
            OAuthApplicationConstants::APPLE_WATCH_APP
        );

        $this->throwExceptionIfOauthAppNotFound($oAuthApp);

        $request = [
            'client_id'     => $oAuthApp['client_details']['prod']['id'],
            'client_secret' => $oAuthApp['client_details']['prod']['secret'],
            'grant_type'    => Constants::APPLE_WATCH_TOKEN['GRANT_TYPE'],
            'scope'         => Constants::APPLE_WATCH_TOKEN['SCOPE'],
            'mode'          => Constants::APPLE_WATCH_TOKEN['MODE'],
            'user_id'       => $user->getId()
        ];

        return $this->authservice->createToken($request);
    }

    /**
     * This function first checks if an application of respective type exists or not
     * then proceeds to create an application on AuthService. Once Application is created it
     * proceeds to generate the access token for the same.
     * @param string $userId
     * @param MerchantEntity $merchant
     * @param array $input
     * @param int $maxRetryCount
     * @return array
     * @throws BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\ServerErrorException|\Throwable
     */
    public function createOauthAppAndTokenForMobileApp(string         $userId,
                                                       MerchantEntity $merchant,
                                                       array          $input,
                                                       int            $maxRetryCount = 1): array
    {

        $oAuthApp = $this->oAuthAppService->createOrGetApplication($merchant,
            OAuthApplicationConstants::MOBILE_APP
        );

        $this->throwExceptionIfOauthAppNotFound($oAuthApp);

        $clientId = $oAuthApp[Constants::CLIENT_DETAILS][Constants::PROD][Constants::ID];

        $clientSecret = $oAuthApp[Constants::CLIENT_DETAILS][Constants::PROD]['secret'];

        $request = [
            'client_id'              => $clientId,
            'client_secret'          => $clientSecret,
            'grant_type'             => $input[OAuthApplicationConstants::OAUTH_TOKEN_GRANT_TYPE],
            'scope'                  => $input[OAuthApplicationConstants::OAUTH_TOKEN_SCOPE],
            'mode'                   => $input[OAuthApplicationConstants::OAUTH_TOKEN_MODE],
            'user_id'                => $userId
        ];

        $retryCount = 0;

        while (true)
        {
            try
            {
                $responseTokens = $this->authservice->createToken($request);

                if (array_key_exists(OAuthApplicationConstants::ACCESS_TOKEN, $responseTokens) === true)
                {
                    $responseTokens[Constants::CLIENT_ID] = $clientId;

                    return $responseTokens;
                }

                break;
            }
            catch(\Throwable $e)
            {
                if ($retryCount < $maxRetryCount)
                {
                    $retryCount++;
                }
                else
                {
                    throw $e;
                }
            }
        }

        return [];
    }

    /**
     * @description This function revokes the access token for the given merchant.
     * @param array $responseData
     * @param MerchantEntity $merchant
     * @param string $clientId
     * @param string $tokenType
     * @param string $accessToken
     * @param int $maxRetryCount
     * @return array
     * @throws BadRequestException
     * @throws Exception\ServerErrorException|\Throwable
     */
    public function revokeAccessTokenForMerchant(array          $responseData,
                                                 MerchantEntity $merchant,
                                                 string         $clientId,
                                                 string         $tokenType,
                                                 string         $accessToken,
                                                 int            $maxRetryCount = 1): array
    {
        $oAuthApp = $this->oAuthAppService->getApplication($merchant, $tokenType);

        $this->throwExceptionIfOauthAppNotFound($oAuthApp);

        if ($oAuthApp[Constants::CLIENT_DETAILS][Constants::PROD][Constants::ID] === $clientId)
        {
            $clientSecret = $oAuthApp[Constants::CLIENT_DETAILS][Constants::PROD]['secret'];

            $request = [
                'client_id'             => $clientId,
                'client_secret'         => $clientSecret,
                'token_type_hint'       => Constants::ACCESS_TOKEN,
                "token"                 => $accessToken,
            ];

            $retryCount = 0;

            while (true)
            {
                try {
                    $response = $this->authservice->revokeAccessToken($request);

                    break;
                }
                catch(\Throwable $e)
                {
                    if ($retryCount < $maxRetryCount)
                    {
                        $retryCount++;
                    }
                    else
                    {
                        throw $e;
                    }
                }
            }

            $responseData[Constants::PREVIOUS_TOKEN_REVOKED] = false;

            if ($response['message'] === Constants::TOKEN_REVOKED)
            {
                $responseData[Constants::PREVIOUS_TOKEN_REVOKED] = true;
            }
        }
        else
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                'client_id',
                $clientId,
                'Incorrect Client Id sent in request. Revoke Token Failed'
            );
        }

        return $responseData;
    }

    /**
     * This function generates new access token for the given merchant using refresh token.
     * @param MerchantEntity $merchant
     * @param string $clientId
     * @param string $tokenType
     * @param string $refreshTokenGrantType
     * @param string $refreshToken
     * @param int $maxRetryCount
     * @return array
     * @throws BadRequestException
     * @throws Exception\ServerErrorException|\Throwable
     */
    public function refreshAccessTokenForMerchant(MerchantEntity $merchant,
                                                  string         $clientId,
                                                  string         $tokenType,
                                                  string         $refreshTokenGrantType,
                                                  string         $refreshToken,
                                                  int            $maxRetryCount = 1): array
    {
        $oAuthApp = $this->oAuthAppService->getApplication($merchant, $tokenType);

        $this->throwExceptionIfOauthAppNotFound($oAuthApp);

        if ($oAuthApp[Constants::CLIENT_DETAILS][Constants::PROD][Constants::ID] === $clientId)
        {
            $clientSecret = $oAuthApp[Constants::CLIENT_DETAILS][Constants::PROD]['secret'];

            $request = [
                'client_id'          => $clientId,
                'client_secret'      => $clientSecret,
                "grant_type"         => $refreshTokenGrantType,
                "refresh_token"      => $refreshToken,
            ];

            $retryCount = 0;

            while (true)
            {
                try
                {
                    $response = $this->authservice->refreshAccessToken($request);

                    break;
                }
                catch (\Throwable $e)
                {
                    if ($retryCount < $maxRetryCount)
                    {
                        $retryCount++;
                    }
                    else
                    {
                        throw $e;
                    }
                }
            }
        }
        else
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                'client_id',
                $clientId,
                'Incorrect Client Id sent in request'
            );
        }

        $response[Constants::CLIENT_ID] = $clientId;

        return $response;
    }

    /**
     * @param MerchantEntity $merchant
     * @param array $input
     * @return array
     * @throws BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\ServerErrorException
     */
    public function revokeTokenOnLogout(MerchantEntity $merchant,
                                        array          $input = []): array
    {

        $oAuthApp = $this->oAuthAppService->createOrGetApplication($merchant,
            OAuthApplicationConstants::MOBILE_APP
        );

        $this->throwExceptionIfOauthAppNotFound($oAuthApp);

        $clientSecret = $oAuthApp[Constants::CLIENT_DETAILS][Constants::PROD]['secret'];

        $input = [
            'client_id'          => $input[OAuthApplicationConstants::CLIENT_ID],
            'client_secret'      => $clientSecret,
            'token_type_hint'    => OAuthApplicationConstants::ACCESS_TOKEN,
            'token'              => $input['token'],
        ];

        $response = $this->authservice->revokeAccessToken($input);

        return $response;
    }

    /**
     * @param MerchantEntity $merchant
     * @param string $tokenType
     * @param string $userId
     * @return void
     * @throws Exception\ServerErrorException
     */
    public function revokeTokenForMerchantUserPair(MerchantEntity $merchant, string $tokenType, string $userId)
    {
        try
        {
            $oAuthApp =  $this->oAuthAppService->getApplication($merchant, $tokenType);

            if (is_null($oAuthApp))
            {
                return;
            }

            $input = [
                'client_id'   => $oAuthApp[Constants::CLIENT_DETAILS][Constants::PROD]['id'],
                'merchant_id' => $merchant->getId(),
                'user_id'     => $userId,
            ];

            $this->authservice->revokeTokenForMerchantUser($input);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::CRITICAL,
                TraceCode::REVOKE_TOKEN_FOR_MERCHANT_USER_FAILED);

            throw $ex;
        }
    }

    /**
     * @throws Exception\ServerErrorException
     */
    protected function throwExceptionIfOauthAppNotFound(array $oAuthApp)
    {
        if (empty($oAuthApp) === true)
        {
            throw new Exception\ServerErrorException(
                'Error completing the request',
                ErrorCode::SERVER_ERROR_AUTH_SERVICE_FAILURE,
                [
                    'message' => 'Auth service did not return OAuth app',
                ]
            );
        }
    }
}
