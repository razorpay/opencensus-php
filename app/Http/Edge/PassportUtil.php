<?php

namespace RZP\Http\Edge;

use ApiResponse;
use Razorpay\Edge\Passport\CredentialClaims;
use RZP\Base\RepositoryManager;
use RZP\Error\ErrorCode;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Http\RequestContextV2;
use RZP\Http\Route;
use RZP\Trace\TraceCode;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\App;
use Razorpay\Edge\Passport\Passport;
use Razorpay\OAuth\Application\Repository;

class PassportUtil
{
    const X_PASSPORT_USABLE = 'X-PASSPORT-USABLE';

    const PARTNER = "partner";
    const OAUTH   = "oauth";

    const WITHOUT_IMPERSONATION = "_without_impersonation";
    const WITH_IMPERSONATION    = "_with_impersonation";

    const KEYLESS_AUTH  = 'keyless_auth';
    const PUBLIC_PREFIX = 'public_';
    const AUTH_SUFFIX   = '_auth';

    const CUSTOMER      = 'customer';
    const MERCHANT_ID   = 'merchant_id';
    const ACCOUNT_ID    = 'account_id';
    const ROUTE         = 'route';

    /*
     * Edge Passport
     */
    protected Passport $passport;

    protected $app;

    protected $trace;

    /**
     * @var RequestContextV2
     */
    protected $reqCtx;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var RepositoryManager
     */
    protected $repo;

    /**
     * @var BasicAuth
     */
    protected $ba;

    /**
     * @var string|null
     */
    protected $route;

    const ALLOWED_ROUTE_TYPES = [Route::PRIVATE, Route::PUBLIC];

    public function __construct(Passport $passport)
    {
        $app = App::getFacadeRoot();

        $this->app      = $app;
        $this->trace    = $app['trace'];
        $this->reqCtx   = $app['request.ctx.v2'];
        $this->ba       = $this->app['basicauth'];
        $this->router   = $this->app['router'];
        $this->route    = $this->router->currentRouteName();
        $this->repo     = $this->app['repo'];
        $this->passport = $passport;
    }

    /**
     * Check that given value is empty and if they are then the value is put in error.
     *
     * @param mixed $value
     * @param string $key
     * @param array  $errors
     *
     * @return bool
     */
    function ensureNotEmpty(mixed $value, string $key = '', array &$errors = []): bool
    {
        if (empty($value))
        {
            $errors[$key] = '';
            return false;
        }
        return true;
    }

    /**
     * Verfies all required claims are present in passport
     *
     * @return bool
     */
    public function validatePassport(): bool
    {
        // passport should be used only for identified requests, identified will be true for both private and public auth
        // unidentified or invalid requests should be terminated at edge itself
        if (! $this->passport->identified) {
            return false;
        }

        $errors = [];
        try {
            // these checks can evolve once passport can be used for other auth schemes
            // verify scalar attribute
            $this->ensureNotEmpty($this->passport->mode, 'mode', $errors);
            $this->ensureNotEmpty($this->passport->domain, 'domain', $errors);

            // verify consumer claims
            if($this->ensureNotEmpty($this->passport->consumer, 'consumer', $errors)) {
                $this->ensureNotEmpty($this->passport->consumer->id, 'consumer_id', $errors);
                $this->ensureNotEmpty($this->passport->consumer->type, 'consumer_type', $errors);
            }

            // verify credential claims
            if ((!$this->isKeylessAuth()) && ($this->ensureNotEmpty($this->passport->credential, 'credential', $errors))) {
                $this->ensureNotEmpty($this->passport->credential->username, 'credential_username', $errors);
                $this->ensureNotEmpty($this->passport->credential->publicKey, 'credential_publicKey', $errors);
            }

            // verify impersonation claims
            if(! empty($this->passport->impersonation)) {
                $this->ensureNotEmpty($this->passport->impersonation->type, 'impersonation_type', $errors);
                if($this->ensureNotEmpty($this->passport->impersonation->consumer, 'impersonation_consumer', $errors)) {
                    $this->ensureNotEmpty($this->passport->impersonation->consumer->id, 'impersonation_consumer_id', $errors);
                    $this->ensureNotEmpty($this->passport->impersonation->consumer->type, 'impersonation_consumer_type', $errors);
                }
            }

            // verify oauth claims
            if(! empty($this->passport->oauth)) {
                $this->ensureNotEmpty($this->passport->oauth->accessTokenId, 'oauth_access_token_id', $errors);
                $this->ensureNotEmpty($this->passport->oauth->clientId, 'oauth_client_id', $errors);
                $this->ensureNotEmpty($this->passport->oauth->appId, 'oauth_app_id', $errors);
                $this->ensureNotEmpty($this->passport->oauth->ownerId, 'oauth_owner_id', $errors);
                $this->ensureNotEmpty($this->passport->oauth->env, 'oauth_env', $errors);
                $this->ensureNotEmpty($this->fetchOauthScopes(), 'oauth_scopes', $errors);
            }

            // if any of the values doesnt exist or not set then passport should not be used
            if (! empty($errors)) {
                $this->trace->info(TraceCode::PASSPORT_ATTRS_MISSING, [
                    'errors' => $errors,
                    'route'  => $this->route
                ]);
                return false;
            }
            // if errors is empty, return true. Hence passport can be used
            return true;

        } catch (\Throwable $e) {
            // above values should exist for passport to be used
            // if any of the values doesnt exist or not set then passport should not be used
            $this->trace->info(TraceCode::PASSPORT_ATTRS_MISSING, [
                'errors'    => $errors,
                'exception' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * This is to check if the auth should be done by Edge Passport only
     * or have a redundant auth at API also
     *
     * @param \Illuminate\Http\Request  $request
     * @return bool
     */
    public function shouldAuthenticateUsingPassport($request): bool
    {
        // will be false if header doesn't exist or set to false
        $passportUsable = filter_var($request->headers->get(self::X_PASSPORT_USABLE), FILTER_VALIDATE_BOOLEAN);

        // validate if passport should be used for the request
        return ( $passportUsable === true && $this->isEdgePassportUsable());
    }

    /**
     * checks if edge passport can be used on the current route.
     * this is an additional authorisation check until edge is able to classify requests properly
     *
     * @return bool
     */
    public function isEdgePassportUsableOnCurrentRoute(): bool
    {
        $routeType = $this->app['api.route']->getRouteType();
        return (in_array($routeType, self::ALLOWED_ROUTE_TYPES, true) === true);
    }

    /**
     * checks if edge passport can be used for auth purposes or not.
     *
     * @return bool
     */
    public function isEdgePassportUsable(): bool
    {
        return ($this->isEdgePassportUsableOnCurrentRoute() && $this->validatePassport() === true);
    }

    /**
     * gets account id from passport
     *
     * @return string
     */
    public function getAccountId(): string
    {
        return (empty($this->passport->impersonation) || empty($this->passport->impersonation->consumer)) ? '' : $this->passport->impersonation->consumer->id;
    }

    /**
     * Checks and sets partner merchant scope
     *
     * @return ApiResponse|null
     */
    public function checkAndSetPartnerMerchantScope()
    {
        if ($this->passport->consumer->type !== self::PARTNER)
        {
            return null;
        }

        // this checks is indirectly performed by edge currently, as only non pure_platform partners will have impersonation_grants at edge
        // TODO: remove this check if no requests encounter this check and trace log
        if (! $this->ba->isPartnerAuthAllowed())
        {
            $this->trace->info(TraceCode::PARTNER_AUTH_NOT_ALLOWED, [
                self::MERCHANT_ID => $this->passport->consumer->id,
                self::ROUTE       => $this->route
            ]);

            return ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_PARTNER_AUTH_NOT_ALLOWED);
        }

        $accountId = $this->getAccountId();

        // If accountId is empty in partner auth, then instead of submerchant's behalf, partner should be able to make
        // requests on his own behalf (for whitelisted routes), just like private auth,
        // so we are setting attributes just as they would be in case of private auth
        if (empty($accountId))
        {
            // unset partner auth
            $this->ba->setPartnerAuth(false);
            return null;
        }

        $account = $this->repo->merchant->find($accountId);

        // account should not be empty here, log and exit with 401 otherwise
        if (empty($account)) {
            $this->trace->info(TraceCode::PASSPORT_ACCOUNT_ID_INVALID, [
                self::MERCHANT_ID => $this->passport->consumer->id,
                self::ACCOUNT_ID  => $accountId,
                self::ROUTE       => $this->route
            ]);

            return ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_ACCOUNT_ID);
        }

        // update partner merchant and subMerchant
        $this->ba->setPartnerMerchantId($this->passport->consumer->id);
        $this->ba->authCreds->setMerchant($account);

        // perform merchant activation check
        $error = $this->doMissingChecksAtEdge();
        if ($error !== null) {
            return ApiResponse::generateErrorResponse(
                ErrorCode::BAD_REQUEST_PARTNER_SUBMERCHANT_NOT_ACTIVATED);
        }

        // application id will not be present in passport for partner auth
        // hence fetch partner client from auth service using clientAuthCreds::class
        // more context can be found in this thread https://razorpay.slack.com/archives/C012ZGQQFDJ/p1677703111425869
        $this->ba->authCreds->fetchPartnerClient($this->ba->authCreds->getKey());
        $applicationId = $this->ba->authCreds->getPartnerApplicationId();

        // $this->applicationId will be set to default value null if it is not set in clientAuthCreds.
        $this->ba->setOAuthApplicationId($applicationId);
        return null;
    }

    /**
     * handles account auth if applicable
     *
     * @return ApiResponse|null
     */
    public function handleAccountAuthIfApplicable()
    {
        $partnerMerchant = $this->ba->authCreds->getMerchant();

        // this checks is indirectly performed by edge as only non pure_platform partners will have impersonation_grants at edge
        // TODO: verify if this check is handled by edge and can be removed from here
        if ((empty($partnerMerchant) === true) or
            ($partnerMerchant->isPartner() === false) or
            ($partnerMerchant->isPurePlatformPartner() === true))
        {
            $this->trace->info(TraceCode::ACCOUNT_AUTH_NOT_ALLOWED, [
                self::MERCHANT_ID => $this->passport->consumer->id,
                self::ROUTE       => $this->route
            ]);
            return null;
        }

        $accountId = $this->getAccountId();
        return empty($accountId) ? null : $this->checkAndSetAccountScope($accountId);
    }

    /**
     * Checks and sets account scope
     *
     * @param string $accountId
     * @return ApiResponse|null
     */
    protected function checkAndSetAccountScope(string $accountId)
    {
        $account = $this->repo->merchant->find($accountId);
        // we do not need to perform null checks as edge will make sure the account exists
        // update partner merchant and subMerchant
        $this->ba->setPartnerMerchantId($this->passport->consumer->id);
        $this->ba->authCreds->setMerchant($account);

        // impersonation checks will be handled by edge so no need to perform here again
        $error = $this->doMissingChecksAtEdge();
        if ($error !== null) {
            return ApiResponse::generateErrorResponse(ErrorCode::BAD_REQUEST_PARTNER_SUBMERCHANT_NOT_ACTIVATED);
        }

        return null;
    }

    /**
     * Performs checks which will be supported by Edge eventually but missing currently
     * TODO: remove this function once Edge starts supporting all the checks defined in this function
     *
     * @return null|\Throwable
     */
    public function doMissingChecksAtEdge() {
        // currently merchant activated check only
        // keeping the merchant activation check in here as this will be supported by Edge in future
        // this can be removed once edge starts supporting it natively
        try
        {
            $this->ba->authCreds->checkMerchantActivatedForLive();
        }
        catch (\Throwable $e)
        {
            return $e;
        }

        return null;
    }

    /**
     * Resolve and process an OAuth scope from passoport roles
     *
     * @return array
     */
    public function fetchOauthScopes(): array
    {
        // roles look like ["finance", "support-l1", "oauth::scope::read_only"]
        /* @var string[] $roles */

        $roles = $this->passport->roles;

        $scopes = [];
        if (!empty($roles))
        {
            foreach ($roles as $role)
            {
                if ((starts_with($role, 'oauth::')) === true)
                {
                    $roleSplit = explode("::", $role);

                    if(empty($roleSplit[2]) === false)
                    {
                        array_push($scopes, $roleSplit[2]);
                    }
                }
            }
        }

        return $scopes;
    }

    /**
     * gets auth flow type from passport data.
     * later can be extended for other auth schemes as required
     *
     * @return string   ''
     *                  merchant_auth_without_impersonation
     *                  merchant_auth_with_impersonation
     *                  partner_auth_without_impersonation
     *                  partner_auth_with_impersonation
     *                  oauth_without_impersonation
     *                  oauth_with_impersonation
     *                  keyless_auth
     *                  public_merchant_auth_without_impersonation
     *                  public_partner_auth_without_impersonation
     *                  public_partner_auth_with_impersonation
     *                  public_oauth_without_impersonation
     *                  public_oauth_with_impersonation
     */
    public function getAuthTypeFromPassport()
    {
        if (empty($this->passport->consumer)) {
            return '';
        }

        $prefix = '';
        // consumer identification request
        if ($this->passport->authenticated === false && $this->passport->identified === true) {
            // keyless auth request
            if (empty($this->passport->credential)) {
                return self::KEYLESS_AUTH;
            }

            $prefix = self::PUBLIC_PREFIX;
        }

        $authType = empty($this->passport->oauth) ? ($this->passport->consumer->type . self::AUTH_SUFFIX) : self::OAUTH;
        $suffix = (empty($this->passport->impersonation) || empty($this->passport->impersonation->consumer)) ? self::WITHOUT_IMPERSONATION : self::WITH_IMPERSONATION;

        $authType = $prefix . $authType . $suffix;
        return $authType;
    }

    /**
     * Returns global customer id from additional identities claims registered on passport.
     *
     * @return string
     */
    public function getGlobalCustomerId(): string
    {
        return !empty($this->passport->additionalIdentities[self::CUSTOMER][0]->id) ?
            $this->passport->additionalIdentities[self::CUSTOMER][0]->id :
            '';
    }

    /**
     * Removes request key from query and request body params
     * Required by payment create validators, to remove certain keys from request params
     *
     * @param string $key
     * @return void
     */
    public function removeRequestKey(string $key)
    {
        // will not return any error if not found
        $this->app['request']->query->remove($key);
        $this->app['request']->request->remove($key);
    }

    //putting this check as there are several mismatches reported for consumer id.
    // consumer id is equal to partner merchant id
    //TODO remove this when passport data is synced.
    private function checkConsumerId(array &$errors): bool
    {
        $application = (new Repository())->findOrFail($this->passport->oauth->appId);
        if ($application->getMerchantId() !== $this->passport->consumer->id)
        {
            $errors[TraceCode::EDGE_PASSPORT_CONSUMER_ID_MISMATCH] = ['expected' => $application->getMerchantId(),
                                                                      'actual' => $this->passport->consumer->id];
        }
        return ($application->getMerchantId() === $this->passport->consumer->id);
    }

    /**
     * Determines if the current authentication is keyless.
     *
     * This method checks the following conditions in passport to determine if the authentication
     * is keyless:
     * - The `authenticated` field is `false`.
     * - The `identified` field is `true`.
     * - The `credential` field is not empty.
     * - The `username` field in `credential` is `null`.
     * - The `publicKey` field in `credential` is `null`.
     *
     * @return bool Returns `true` if the authentication is keyless; otherwise, `false`.
     */
    public function isKeylessAuth(): bool
    {
        return ($this->passport->authenticated === false && $this->passport->identified === true &&
            empty($this->passport->credential) === false && $this->passport->credential->username === null &&
            $this->passport->credential->publicKey === null);
    }
}
