<?php

namespace RZP\Http\Edge\PassportAuth;

use ApiResponse;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Feature\Service;
use RZP\Http\Edge\PassportUtil;
use RZP\Exception\LogicException;
use RZP\Models\Feature\Constants;
use Illuminate\Support\Facades\App;
use Razorpay\Edge\Passport\Passport;
use RZP\Exception\BadRequestException;
use Razorpay\OAuth\Token\Entity as OAuthToken;

class OAuth
{
    protected $requestContext;

    protected $trace;

    protected $request;

    /** @var PassportUtil */
    protected $passportUtil;

    /** @var Passport */
    protected $passport;

    protected $app;

    protected $ba;

    protected $oauthCommon;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->ba = $this->app['basicauth'];

        $this->requestContext = $app['request.ctx.v2'];

        $this->request = $app['request'];

        $this->trace = $app['trace'];

        $this->passport = $this->requestContext->passport;

        $this->passportUtil = new PassportUtil($this->passport);

        //TODO not require oauthCommon for any function calls.
        $this->oauthCommon = new \RZP\Http\OAuth();
    }

    /**
     * Authenticate the request with a OAuth token
     *
     *
     * @param string $auth -- could be public or private
     *
     * @return mixed|null ErrorResponse if error, else null
     * @throws BadRequestException
     */
    public function authenticate(string $auth)
    {
        $unauthorizedForBankingRoutesError = $this->checkForBankingRoutes();

        if (empty($unauthorizedForBankingRoutesError) === false){
            return $unauthorizedForBankingRoutesError;
        }

        return $this->setBaValues($auth);
    }

    /**
     *
     * @throws BadRequestException
     */
    public function checkForBankingRoutes()
    {
        $isRazorpayXExclusiveRoute = $this->oauthCommon->isBankingRoute();

        $isApplicationAllowedForBankingRoutes =
            (new Service())->checkFeatureEnabled(Constants::APPLICATION,
                                                 $this->passport->oauth->appId,
                                                 Constants::RAZORPAYX_FLOWS_VIA_OAUTH)['status'];

        if ($isRazorpayXExclusiveRoute === true and $isApplicationAllowedForBankingRoutes === false)
        {
            return ApiResponse::unauthorizedOauthAccessToRazorpayX();
        }

        return null;
    }

    /**
     * fetches from passport and set values for ba to be used
     *
     *
     * @param string $auth -- could be public or private
     *
     * @return mixed|null ErrorResponse if error, else null
     */
    private function setBaValues(string $auth)
    {
        $mode = $this->passport->mode;

        //
        // Public key is used to generate the callback URL parameter that is
        // being sent with the payment create request to the gateway.
        //
        $publicKey = $this->passport->credential->publicKey;;

        $this->ba->oauthPublicTokenAuth($publicKey, $auth);

        // Sets the mode for the request, and database connection
        $this->ba->authCreds->setModeAndDbConnection($mode);

        $merchantId = $this->passport->oauth->ownerId;;

        //
        // Set merchant for the current request
        // TODO: Move this to a common auth class
        //
        $this->ba->setMerchantById($merchantId);
        $userId = null;

        try
        {
            $userId = $this->passport->oauth->userId;
            //
            // Set user for the current request
            // TODO: Move this to a common auth class
            //
            $this->ba->setUserById($userId);
        }
        catch (\Throwable $ex)
        {
            $this->trace->info(TraceCode::USER_CONTEXT_NOT_PRESENT_FOR_OAUTH_REQUEST,
                               [OAuthToken::MERCHANT_ID => $merchantId, 'error' => $ex]);
        }

        try
        {
            $this->ba->authCreds->checkMerchantActivatedForLive();
        }
        catch (LogicException $e)
        {
            return ApiResponse::generateErrorResponse(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED_OAUTH_MERCHANT_NOT_ACTIVATED);
        }

        // this flow is required for non pure platform partners
        // who use oauth (Client Credentials mostly).
        $error = $this->oauthCommon->handleAccountAuthIfApplicable();

        if ($error !== null)
        {
            return $error;
        }

        // Sets the identifiers that are sent in trace logs
        $this->ba->setAccessTokenId($this->passport->oauth->accessTokenId);
        $this->ba->setOAuthClientId($this->passport->oauth->clientId);
        $this->ba->setOAuthApplicationId($this->passport->oauth->appId);
        $this->ba->setUserRoleWithUserIdAndMerchantId($merchantId, $userId);

        $tokenScopes = $this->passportUtil->fetchOauthScopes();
        $this->ba->setTokenScopes($tokenScopes);

        $this->ba->setPartnerMerchantId($this->passport->consumer->id);

        return null;
    }


}
