<?php

namespace RZP\Models\Admin\Admin;

use App;
use RZP\Diag\EventCode;
use RZP\Exception\BaseException;
use RZP\Models\Feature\Constants;
use RZP\Models\Merchant\RazorxTreatment;
use Str;
use Cache;
use Hash;
use Mail;
use Event;
use Request;
use Carbon\Carbon;

use Google_Client;
use RZP\Error;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Org;
use RZP\Models\Base\EsDao;
use RZP\Models\Admin\Role;
use RZP\Models\Admin\Group;
use RZP\Constants\HashAlgo;
use RZP\Models\Admin\Action;
use RZP\Events\AuditLogEntry;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Admin\Org\AuthPolicy;
use RZP\Exception\BadRequestException;
use RZP\Mail\Admin\Account as AdminMail;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use RZP\Models\Feature\Service as FeatureService;

class Service extends Base\Service
{
    const ADMIN_PASSWORD_RESET_TOKEN_KEY = 'password_reset_token_org_%s_admin_%s';

    const ADMIN_EMAIL_NOT_FOUND  = 'ADMIN_EMAIL_NOT_FOUND';

    const PASSWORD_TOKEN_EXPIRY_TIME = 3.6e+6; // 60 minutes

    const TOKEN = 'token';

    public function __construct()
    {
        parent::__construct();

        $this->adminOrgId = $this->app['basicauth']->getAdminOrgId();

        $this->authAdmin = $this->app['basicauth']->getAdmin();
    }

    public function authenticate(array $input)
    {
        $orgId = $this->app['basicauth']->getOrgId();

        return $this->login($orgId, $input);
    }

    public function login(string $orgId, array $input)
    {
        if ((isset($input[Entity::USERNAME]) === false) or
            (isset($input[Entity::PASSWORD]) === false))
        {
            throw new Exception\BadRequestException(
                Error\ErrorCode::BAD_REQUEST_AUTHENTICATION_FAILED);
        }

        $email = $input['username'];

        $admin = $this->repo->admin->findByOrgIdAndEmail($orgId, $email);

        if ($admin === null)
        {
            throw new Exception\BadRequestException(
                Error\ErrorCode::BAD_REQUEST_AUTHENTICATION_FAILED);
        }

        $admin->getValidator()->validateCredentials($input);

        try
        {
            $authPolicy = new AuthPolicy\Service;

            $authPolicy->validateBeforeLogin($admin);
        }
        catch (Exception\RecoverableException $ex)
        {
            $this->handleAuthFailure($admin, Action::LOGIN_FAIL, $ex);
        }

        if (Hash::check($input['password'], $admin->getPassword()))
        {
            if($this->featureEnabledForOrg($orgId)) {
                $this->core()->checkSecondFactorAuthAndSendOtp($admin);
            }

            $data = $this->generateLoginToken($admin);

            $authPolicy->validateAfterLogin($admin);

            $this->fireAdminAction($admin, Action::LOGIN);

            return $data;
        }

        $this->handleAuthFailure($admin);
    }

    public function featureEnabledForOrg($orgId){

        $org = $this->repo->org->findByPublicId($orgId);

        return $org->isFeatureEnabled(Constants::ORG_SECOND_FACTOR_AUTH);
    }

    public function isAdminPasswordResetAllowed(string $orgId): bool
    {
        $features = (new \RZP\Models\Feature\Service)->getOrgFeatures('org',$orgId);

        $assignedFeatures =  $features['assigned_features']->pluck('name')->toArray();

        return (in_array(Feature\Constants::ORG_ADMIN_PASSWORD_RESET, $assignedFeatures) === true);
    }

    public function verifyAdminSecondFactorAuth(array $input): array
    {
        $validator = new Validator();

        $validator->validateInput('verify_admin_second_factor', $input);

        $email = $input['username'];
        $orgId = $this->app['basicauth']->getOrgId();

        $admin = $this->repo->admin->findByOrgIdAndEmail($orgId, $email);

        if ($admin === null)
        {
            throw new Exception\BadRequestException(
                Error\ErrorCode::BAD_REQUEST_AUTHENTICATION_FAILED);
        }

        return $this->verifyAdmin2FA($admin, $input);
    }

    public function verifyAdmin2FA($admin, $input){
        $admin = $this->core()->verifyAdminSecondFactorAuth($admin, $input);
        try
        {
            $authPolicy = new AuthPolicy\Service;

            $authPolicy->validateBeforeLogin($admin);
        }
        catch (Exception\RecoverableException $ex)
        {
            $this->handleAuthFailure($admin, Action::LOGIN_FAIL, $ex);
        }
        if (Hash::check($input['password'], $admin->getPassword()) === false)
        {
            $this->handleAuthFailure($admin);
        }
        $data = $this->generateLoginToken($admin);

        $authPolicy->validateAfterLogin($admin);

        $this->fireAdminAction($admin, Action::LOGIN);

        return $data;
    }

    public function change2faSetting(array $input)
    {
        $admin = $this->app['basicauth']->getAdmin();

        $admin->getValidator()->validateInput('change2faSetting', $input);

        return $this->core()->change2faSetting($admin, $input);
    }

    protected function handleAuthFailure($admin, $action = Action::LOGIN_FAIL, $exception = null)
    {
        $admin->incrementFailedAttempts();

        $this->fireAdminAction(
            $admin,
            $action,
            ['failed_attempts' => $admin->getFailedAttempts()]);

        $this->repo->saveOrFail($admin);

        if ($exception === null)
        {
            $exception = new Exception\BadRequestException(
                    Error\ErrorCode::BAD_REQUEST_AUTHENTICATION_FAILED);
        }

        throw $exception;
    }

    protected function fireAdminAction(Entity $admin, array $action, array $customProperties = null)
    {
        $this->trace->info(TraceCode::HEIMDALL_AUDIT_LOG, ['admin' => $admin, 'action' => $action]);

        if ($admin instanceof Entity)
        {
            $admin = $admin->toArrayPublic();
        }

        event(new AuditLogEntry($admin, $action, $customProperties));
    }

    /**
     * @throws Exception\BadRequestException
     */
    public function forgotPassword(string $orgId, array $input) {

        $signedOrgId = $orgId;
        if (!$this->isAdminPasswordResetAllowed(Org\Entity::silentlyStripSign($signedOrgId)))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_WHITELISTED_MERCHANT_ADMIN_FORGOT_PASSWORD_FEATURE_NOT_ENABLED);
        }

        $validator = new Validator();

        $this->trace->info(TraceCode::WHITELISTED_MERCHANT_ADMIN_FORGOT_PASSWORD_REQUEST, $input);

        $org = $this->repo->org->findByPublicId($orgId);

        $input[Org\Entity::AUTH_TYPE] = $org->getAuthType();

        $validator->validateInput('forgot', $input);

        $admin = $this->repo->admin->findByOrgIdAndEmail($orgId, $input['email']);

        if (empty($admin) === true)
        {
            $this->trace->info(TraceCode::WHITELISTED_MERCHANT_ADMIN_NOT_FOUND,
                [
                    'email' => mask_email($input['email'])
                ]);

            $this->app['diag']->trackOnboardingEvent(EventCode::WHITELISTED_ORG_ADMIN_ONBOARDING_FORGOT_PASSWORD_FAILURE,
            null,new BaseException(self::ADMIN_EMAIL_NOT_FOUND), [$input['email']]);
        }
        else
        {
            $this->setPasswordResetToken($admin, $input);

            $this->sendAdminForgotPasswordEmail($admin, $input);

            $this->app['diag']->trackOnboardingEvent(EventCode::WHITELISTED_ORG_ADMIN_ONBOARDING_FORGOT_PASSWORD_SUCCESS,
                null,null,[$input['email']]);
        }
        return ['success' => true];
    }

    protected function sendAdminForgotPasswordEmail(Entity $admin, $input)
    {
        $org = $admin->org->toArray();

        $admin = $admin->toArray();

        $forgotPasswordMail = new AdminMail\ForgotPassword($admin, $org, $input);

        Mail::queue($forgotPasswordMail);
    }

    protected function generateToken()
    {
        $app = App::getFacadeRoot();

        $secret = $app->config->get('app.key');

        $token = hash_hmac(HashAlgo::SHA256, Str::random(40), $secret);

        return $token;
    }

    protected function setPasswordResetToken(Entity $admin, array & $input)
    {
        $this->trace->info(
            TraceCode::WHITELISTED_MERCHANT_ADMIN_PASSWORD_RESET_TOKEN_GENERATE,
            [
                'admin_id' => $admin['id'],
                'expiry'  => self::PASSWORD_TOKEN_EXPIRY_TIME,
            ]);

        $expiresAt = Carbon::now()->timestamp + self::PASSWORD_TOKEN_EXPIRY_TIME;

        $token = $this->generateToken();

        $this->savePasswordResetTokenAndExpiry($admin, $token, $expiresAt);

        $input[self::TOKEN] = $token;
    }

    public function savePasswordResetTokenAndExpiry(Entity $admin, string $token, int $expiry)
    {
        $admin->setPasswordResetToken($token);

        $admin->setPasswordResetExpiry($expiry);

        $this->repo->saveOrFail($admin);
    }

    /**
     * @throws Exception\BadRequestException
     */
    public function resetPassword(string $orgId, array $input)
    {
        $org = $this->repo->org->findByPublicId($orgId);
        if ($org->isFeatureEnabled(Feature\Constants::BANK_ADMIN_ADFS_LOGIN) === false)
        {
            $signedOrgId = $orgId;
            if (!$this->isAdminPasswordResetAllowed(Org\Entity::verifyIdAndSilentlyStripSign($signedOrgId)))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_WHITELISTED_MERCHANT_ADMIN_RESET_PASSWORD_FEATURE_NOT_ENABLED);
            }

            $this->trace->info(TraceCode::WHITELISTED_MERCHANT_ADMIN_RESET_PASSWORD_REQUEST, $input);

            $org = $this->repo->org->findByPublicId($orgId);

            $input[Org\Entity::AUTH_TYPE] = $org->getAuthType();

            // Get admin
            $admin = $this->repo->admin->findByOrgIdAndEmail($orgId, $input['email']);
            if ($admin === null)
            {
                $this->trace->info(TraceCode::WHITELISTED_MERCHANT_ADMIN_NOT_FOUND,
                    [
                        'email' => mask_email($input['email'])
                    ]);

                $this->app['diag']->trackOnboardingEvent(EventCode::WHITELISTED_ORG_ADMIN_ONBOARDING_RESET_PASSWORD_FAILURE,
                    null,new BaseException(ErrorCode::BAD_REQUEST_INVALID_ADMIN_EMAIL), [$input['email']]);

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_ERROR);
            }

            $validator = new Validator($admin);

            $validator->validateInput('reset', $input);

            $expiry = $admin->getPasswordResetExpiry();

            $now = Carbon::now()->getTimestamp();

            if ($expiry < $now)
            {
                $this->trace->info(TraceCode::WHITELISTED_MERCHANT_ADMIN_TOKEN_EXPIRED,
                    [
                        'email' => mask_email($input['email'])
                    ]);

                $this->app['diag']->trackOnboardingEvent(EventCode::WHITELISTED_ORG_ADMIN_ONBOARDING_RESET_PASSWORD_FAILURE,
                    null,new BaseException(ErrorCode::BAD_REQUEST_TOKEN_EXPIRED_NOT_VALID), [$input['email']]);

                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_TOKEN_EXPIRED_NOT_VALID);
            }

            $resetToken  = $admin->getPasswordResetToken();

            if ((empty($resetToken) === true) or (hash_equals($resetToken, $input['token']) === false))
            {
                $this->trace->info(TraceCode::WHITELISTED_MERCHANT_ADMIN_INVALID_TOKEN,
                    [
                        'email' => mask_email($input['email'])
                    ]);

                $this->app['diag']->trackOnboardingEvent(EventCode::WHITELISTED_ORG_ADMIN_ONBOARDING_RESET_PASSWORD_FAILURE,
                    null,new BaseException(ErrorCode::BAD_REQUEST_INVALID_PASSWORD_RESET_TOKEN), [$input['email']]);

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_PASSWORD_RESET_TOKEN);
            }
            else
            {
                $this->core()->updatePassword($admin, $input, true);

                //Expiry the reset token on password successful update
                $admin->setPasswordResetExpiry($now);

                if ($admin->isLocked())
                {
                    $admin->unlock();
                }


                $this->repo->admin->saveOrFail($admin);
                $this->app['diag']->trackOnboardingEvent(EventCode::WHITELISTED_ORG_ADMIN_ONBOARDING_RESET_PASSWORD_SUCCESS,
                    null,null,[$input['email']]);
            }

            return ['success' => true];
            }
            else
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BANK_ADMIN_ADFS_LOGIN_FEATURE_ENABLED, null, "bank admin adfs login feature is enable.");
            }
    }

    public function getAdminFromEmail($orgId, $email)
    {
        $admin = $this->repo->admin->findByOrgIdAndEmail($orgId, $email);

        if ($admin === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_ADMIN_EMAIL);
        }

        return $admin;
    }

    public function loginWithOAuth($input)
    {
        $validator = new Validator();

        $validator->validateInput('o_auth_login', $input);

        $orgId = $this->auth->getOrgId();
        // Get the admin record
        $admin = $this->getAdminFromEmail($orgId, $input['email']);

        // store access token and provider id.
        $oauthData = [
            'oauth_access_token' => $input['oauth_access_token'],
            'oauth_provider_id'  => $input['oauth_provider_id'],
        ];

        $verifyAccessToken = $this->verifyAccessTokenWithGoogle($input);

        if ($verifyAccessToken === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ACCESS_TOKEN_INVALID);
        }

        $this->core()->edit($admin, $oauthData);

        $data = $this->generateLoginToken($admin);

        $this->fireAdminAction($admin, Action::LOGIN_OAUTH);

        return $data;
    }

    public function verifyAccessTokenWithGoogle($input)
    {
        try
        {
            $app = App::getFacadeRoot();

            $client_id = $app->config->get('oauth.admin_google_oauth_client_id');

            $mock = $app->config->get('oauth.admin_google_oauth_client_mock');

            if ($mock === true)
            {
                return true;
            }

            $client = new Google_Client([Constant::CLIENT_ID => $client_id]);

            $client->setAccessToken($input['oauth_access_token']);

            $request = new GuzzleRequest('GET', Constant::GOOGLE_FETCH_USER_INFO_URL);

            $response = $client->execute($request);

            $responseJson = json_decode($response->getBody(), true);

            if ($this->verifyResponseAttributesFromGoogle($responseJson, $input) === true)
            {
                return true;
            }

            return false;
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex, Trace::ERROR, TraceCode::ADMIN_GOOGLE_OAUTH_ERROR);

            return false;
        }
    }

    protected function verifyResponseAttributesFromGoogle($responseJson, $input)
    {
        $responseUserId        = $responseJson['user_id'] ?? null;
        $responseEmail         = $responseJson['email'] ?? null;
        $responseVerifiedEmail = $responseJson['verified_email'] ?? null;

        $data = [
            'user_id'       => $responseUserId,
            'verifiedEmail' => $responseVerifiedEmail,
            'email'         => $responseEmail,
            'input_email'   => $input['email'],
        ];

        if (($responseUserId === $input['oauth_provider_id']) and
            ($responseVerifiedEmail) and
            ((strcmp(strtolower($responseEmail), strtolower($input['email'])) === 0) === true))
        {
            // verify Successfully.
            $this->trace->info(TraceCode::ADMIN_GOOGLE_OAUTH_VERIFY_SUCCESS, $data);

            return true;
        }

        $this->trace->info(TraceCode::ADMIN_GOOGLE_OAUTH_VERIFY_FAIL, $data);

        return false;
    }

    /**
     * Here we are generating a bearer token
     * and savnig bcrypted token and considering token Id as principal.
     * Ref https://security.stackexchange.com/a/94792
     * concat bearer token and principal and sending to client as admin token.
     * last 14 characters of the token will be extracted and will be matched bycrypting the token.
     *
     * @param $admin
     * @return mixed
     */
    private function generateLoginToken($admin)
    {
        $this->fireAdminAction($admin, Action::GENERATE_LOGIN_TOKEN);

        $admin->resetFailedAttempts();

        $admin->updateLastLoginAt();

        $this->repo->saveOrFail($admin);

        $bearerToken = str_random(20);

        $tokenAttributes = [
            'token'      => Hash::make($bearerToken),
            'expires_at' => Carbon::now()->addDays(30)->getTimestamp()
        ];

        $token = $this->core()->createAuthToken($admin, $tokenAttributes);

        $admin = $admin->toArrayPublic();

        $admin['token'] = $bearerToken . $token->getId();

        return $admin;
    }

    public function samlAuthLogin($input)
    {
        $validator = new Validator();

        $validator->validateInput('saml_login', $input);

        $email=$input['email'];
        $token=null;
        $loginToken=null;

        $orgId = $this->auth->getOrgId();

        $admin = $this->getAdminFromEmail($orgId, $email);

        if ($admin !== null)
        {

            $expdate = $input['expiry_date'] ?? $admin->expired_at;

            // Validate the user's identity in DB
            if ($expdate >= Carbon::today()->getTimestamp())
            {
                try
                {
                    $adminMeta = (new \RZP\Models\Admin\AdminsMeta\Repository())->fetchByAdminIdAndUniqueIdentifier($admin->getId(), $input['ad_id']);

                    if ($adminMeta !== null )
                    {
                        $generatedToken = $this->generateLoginToken($admin);
                    }
                }
                catch (\Throwable $e)
                {
                    $this->trace->info(TraceCode::USER_SAML_AUTHENTICATION_FAIL);

                    throw new Exception\BadRequestException(
                        Error\ErrorCode::BAD_REQUEST_AUTHENTICATION_FAILED);
                }

            }
            else
            {
                $this->trace->info(TraceCode::INVALID_USER_DATA);

                throw new Exception\BadRequestException(
                    Error\ErrorCode::BAD_REQUEST_ADMIN_EXPIRED);
            }
        }
        else
        {
            $this->trace->info(TraceCode::ORG_ADMIN_NOT_FOUND);

            throw new Exception\BadRequestException(
                Error\ErrorCode::BAD_REQUEST_ADMIN_NOT_FOUND);
        }

         return $generatedToken;
    }

    public function createAdmin(array $input)
    {
        $org = $this->repo->org->find($this->adminOrgId);

        if (empty($input[Entity::ROLES]) === false)
        {
            Role\Entity::verifyIdAndStripSignMultiple($input[Entity::ROLES]);
        }

        if (empty($input[Entity::GROUPS]) === false)
        {
            Group\Entity::verifyIdAndStripSignMultiple(
                $input[Entity::GROUPS]);
        }

        $admin = $this->core()->create($org, $input);

        $this->sendAdminCreateEmail($admin, $input);

        return $admin->toArrayPublic();
    }

    public function sendAdminCreateEmail($admin, $input)
    {
        $org = $admin->org->toArray();

        $admin = $admin->toArray();

        $createAdminMail = new AdminMail\Create($admin, $org, $input);

        Mail::queue($createAdminMail);
    }

    public function getAdmin(string $adminId)
    {
        // Fetch admin with relations
        $admin = $this->repo->admin->findByPublicIdAndOrgIdWithRelations(
            $adminId, $this->adminOrgId, [Entity::GROUPS, Entity::ROLES]);

        return $admin->toArrayPublic();
    }

    public function getAdminByAppAuth(array $input)
    {
        $token = $input['token'];

        $adminToken = $this->repo->admin_token->findOrFailToken($token);

        $adminId = $adminToken->getAdminId();

        $orgId = $adminToken->admin->getOrgId();

        $admin = $this->repo->admin->findByIdAndOrgIdWithRelations(
            $adminId, $orgId, ['groups', 'roles', 'roles.permissions']);

        $roles = $admin->roles;
        $roleNames = [];
        $groupRules = [];

        foreach ($admin->groups as $group)
        {
            $groupRules[] = [
                'name' => $group['name'],
                'description' => $group['description'],
            ];
        }

        $permissions = null;

        foreach ($roles as $role)
        {
            $roleNames[] = $role['name'];

            if ($permissions === null)
            {
                $permissions = $role->permissions->pluck('name');
            }
            else
            {
                $permissions = $permissions->merge($role->permissions->pluck('name'));
            }
        }

        $admin = $admin->toArrayPublic();

        if (empty($permissions) === false)
        {
            $admin[Entity::PERMISSIONS] = $permissions->all();
        }

        $admin[Entity::ROLES] = $roleNames;

        $admin[Entity::GROUPS] = $groupRules;

        return $admin;
    }

    public function deleteAdmin(string $adminId)
    {
        $admin = $this->repo->admin->findByPublicIdAndOrgId($adminId, $this->adminOrgId);

        $admin->getValidator()->validateSelfEditForbidden($this->authAdmin, $admin);

        // Trigger workflow
        $this->app['workflow']->handle($admin, (new \StdClass()));

        $admin->setAuditAction(Action::DELETE_ADMIN);

        return $this->core()->delete($admin);
    }

    /**
     * wraps delete admin api and returns array of response with id and deleted status
     * @param array $input
     * @return array
     */
    public function deleteAdmins(array $input)
    {
        $adminEmails = $input['emails'];
        $adminEmailList = explode(',', $adminEmails);
        $adminEmailList = array_map('trim', $adminEmailList);

        // TODO: add a bulk workflow wrapper
        $responses = array();
        foreach ($adminEmailList as $adminEmail) {
            $admin = $this->repo->admin->findByOrgIdAndEmail($this->adminOrgId, $adminEmail);
            if ($admin === null)
            {
                $responses[] = ['id' => '', 'email' => $adminEmail, 'deleted' => false, 'msg' => 'entity not found'];
                continue;
            }

            try {
                $admin->getValidator()->validateSelfEditForbidden($this->authAdmin, $admin);
                $admin->setAuditAction(Action::DELETE_ADMIN);
                $this->core()->delete($admin);
            } catch (\Throwable $e) {
                $responses[] = ['id' => $admin->id, 'email' => $adminEmail, 'deleted' => false, 'msg' => $e->getMessage()];
            }
         }

        return $responses;
    }

    public function fetchMultiple()
    {
        $admins = $this->repo->admin->fetchByOrgId($this->adminOrgId, [Entity::GROUPS, Entity::ROLES]);

        return $admins->toArrayPublic();
    }

    /**
     * In case of Batch we are using batchID to update the admin,
     * as Batch process do not have orgId and it's using internal auth
     * so it's picking up orgID from the updating admin itself
     *
     * @param string $adminId
     * @param array  $input
     * @param string $batchId
     *
     * @return mixed
     */
    public function validateAndEditAdmin(string $adminId, array $input, string $batchId = '')
    {
        if (empty($this->adminOrgId) === true)
        {
            $orgId = $this->app['basicauth']->getOrgId();
        }
        else
        {
            $orgId = $this->adminOrgId;
        }

        if ((empty($orgId) === true) and (empty($batchId) === false))
        {
            $admin = $this->repo->admin->findByPublicId($adminId);

            $orgId = $admin[Entity::ORG_ID];
        }

        $admin = $this->repo->admin->findByPublicIdAndOrgId($adminId, $orgId);

        if (empty($input[Entity::ROLES]) === false)
        {
            $input[Entity::ROLES] = array_unique($input[Entity::ROLES]);

            Role\Entity::verifyIdAndStripSignMultiple($input[Entity::ROLES]);
        }

        if (empty($input[Entity::GROUPS]) === false)
        {
            $input[Entity::GROUPS] = array_unique($input[Entity::GROUPS]);

            Group\Entity::verifyIdAndStripSignMultiple(
                $input[Entity::GROUPS]);
        }

        // making impromptu changes to make editAdmin work on appAuth
        if ($this->app['basicauth']->isAdminAuth() === true)
        {
            $authAdmin = $this->app['basicauth']->getAdmin();

            $admin->getValidator()->validateSelfEditForbidden($authAdmin, $admin);
        }

        $admin = $this->core()->edit($admin, $input);

        return $admin;
    }

    /**
     * @param string $adminId
     * @param array  $input
     *
     * @return mixed
     */
    public function editAdmin(string $adminId, array $input)
    {
        $admin = $this->validateAndEditAdmin($adminId, $input);

        return $admin->toArrayPublic();
    }

    public function getMerchantsFromEs(array $input): array
    {
        $admin = $this->auth->getAdmin();

        // Appends more payload in $input for ES search:

        // Always add this ORG_ID filter.
        $input[Merchant\Entity::ORG_ID] = $this->auth->getAdminOrgId();

        // If admin not allowed to see all merchants, get all group
        // ids he belongs to and pass in $input. This gets used to
        // filter results.

        if ($admin->canSeeAllMerchants() === false)
        {
            $groupIds = $admin->groups()->get()->getIds();

            $input[Merchant\Entity::GROUPS] = $groupIds;

            // Adds following to $input so all merchant to which this admin
            // has direct access to can be filtered.

            $input[Merchant\Entity::ADMINS] = [$admin->getId()];
        }

        if ((isset($input[Merchant\Detail\Entity::REVIEWER_ID]) === true) and
            ($input[Merchant\Detail\Entity::REVIEWER_ID] !== 'none'))
        {
            Entity::verifyIdAndStripSign($input[Merchant\Detail\Entity::REVIEWER_ID]);
        }

        // We would want to receive the ES payload

        $input[Base\EsRepository::SEARCH_HITS] = 1;

        $startTime = millitime();

        $merchants = $this->repo->merchant->fetch($input);

        $this->trace->histogram(Merchant\Metric::FETCH_ALL_PARTNERS_LATENCY,millitime()-$startTime);

        return $merchants->toArrayAdmin();
    }

    public function getMerchantIdsFromEs(): array
    {
        $result = $this->getMerchantsFromEs([]);

        //
        // Existing consumer(dashboard) expect the result as following:
        // [
        //   "id" => "referrer",
        //   ...
        // ]
        //

        $items = $result['items'];

        return array_pluck($items, Merchant\Entity::REFERRER, Merchant\Entity::ID);
    }

    /**
     * @throws Exception\BadRequestException
     */
    public function getMerchantsUnifiedDashboard(array $input) : array
    {
        $admin = $this->auth->getAdmin();

        if($this->auth->isAdminAuth() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::SERVER_ERROR_INVALID_AUTH, null, $input);
        }

        // Filter so that the admin can fetch only the merchants of same Org
        $input[Merchant\Entity::ORG_ID] = $this->auth->getAdminOrgId();

        if ($admin->canSeeAllMerchants() === false)
        {

            $groupIds = $admin->groups()->get()->getIds();

            $input[Merchant\Entity::GROUPS] = $groupIds;

            // Adds following to $input so all merchant to which this admin
            // has direct access to can be filtered.

            $input[Merchant\Entity::ADMINS] = [$admin->getId()];
        }

        $input[Base\EsRepository::SEARCH_HITS] = 1;

        $startTime = millitime();

        $merchants = $this->repo->merchant->fetchMerchantsUnifiedDashboard($input);

        $this->trace->histogram(Merchant\Metric::FETCH_ALL_PARTNERS_LATENCY,millitime()-$startTime);

        return $merchants;
    }

    public function getPartnerActivationFromEs(array $input) : array
    {
        if ((isset($input[Merchant\Detail\Entity::REVIEWER_ID]) === true) and
            ($input[Merchant\Detail\Entity::REVIEWER_ID] !== 'none'))
        {
            Entity::verifyIdAndStripSign($input[Merchant\Detail\Entity::REVIEWER_ID]);
        }

        $input[Base\EsRepository::SEARCH_HITS] = 1;

        $partnerActivation = $this->repo->partner_activation->fetch($input);

        return $partnerActivation->toArrayAdmin();
    }

    public function lockUnusedAccounts()
    {
        $timestamp = Carbon::now()->subDays(30)->getTimestamp();

        $unactivatedAccounts = $this->repo->admin->lockUnactivatedAccounts($timestamp);

        $timestamp = Carbon::now()->subDays(90)->getTimestamp();

        $unusedAccounts = $this->repo->admin->lockUnusedAccounts($timestamp);

        return ['count' => $unactivatedAccounts + $unusedAccounts];
    }

    public function searchAuditLogs($orgId, $input)
    {
        try
        {
            $esDao = new EsDao();

            return $esDao->searchAuditLogs($orgId, $input);
        }
        catch(\Exception $e)
        {
            $this->trace->warning(TraceCode::HEIMDALL_AUDIT_LOG_SEARCH_FAIL, ['error' => $e]);

            throw $e;
        }
    }

    public function logout()
    {
        $adminToken = $this->app['basicauth']->getAdminToken();

        (new Token\Service)->deleteToken($adminToken);

        return ['success' => true];
    }

    protected function getCacheKeyForResetToken(string $orgId, string $adminId)
    {
        return sprintf(
            self::ADMIN_PASSWORD_RESET_TOKEN_KEY,
            $orgId, $adminId);
    }

    /**
     * Change password for admin.
     * @param  array $input input request params
     * @return array        response
     */
    public function changePassword($input)
    {
        $admin = $this->auth->getAdmin();

        $adminOrgAuthType = $admin->org->getAuthType();

        if ($adminOrgAuthType !== Org\AuthType::PASSWORD)
        {

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_CHANGE_PASSWORD_NOT_ALLOWED);
        }

        $this->core()->updatePassword($admin, $input, false, 'change');

        $this->repo->admin->saveOrFail($admin);

        return ['success' => true];
    }

    public function accountLockUnlock(string $adminId, string $action): array
    {
        $accountLockData = [
            Constant::ADMIN_ID => $adminId,
            Constant::ACTION  => $action,
        ];

        $admin = $this->repo->admin->findByPublicIdAndOrgId($adminId, $this->adminOrgId);

        return $this->core()->accountLockUnlock($admin, $action);
    }

    public function resendOtp($input)
    {
        $email = $input['username'];
        $orgId = $this->app['basicauth']->getOrgId();

        $admin = $this->repo->admin->findByOrgIdAndEmail($orgId, $email);

        if ($admin === null)
        {
            throw new Exception\BadRequestException(
                Error\ErrorCode::BAD_REQUEST_AUTHENTICATION_FAILED);
        }

        return $this->core()->resendOtp($admin);
    }

    /**
     * @throws Exception\BadRequestException
     */
    public function bulkAssignRole(array $input): array
    {
        $this->trace->info(TraceCode::ADMIN_BULK_ASSIGN_ROLE, $input);

        $emailIDs = $input['emails'];
        $roleIDs  = $input[Entity::ROLES];

        // get the org id from auth context
        $orgId = $this->app['basicauth']->getOrgId();

        if ($orgId !== Org\Entity::getSignedId(Org\Constants::RZP))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR, null, "Bulk assign role to admin is only allowed for rzp org");
        }

        if (empty($input[Entity::ROLES]) === false)
        {
            Role\Entity::verifyIdAndStripSignMultiple($roleIDs);
        }
        else
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR, null, "Roles list cant be empty");
        }

        // Fetch all the admins for the emails
        $admins = $this->repo->admin->fetchByOrgIDAndEmailIDs($orgId, $emailIDs);
        $failedEmails = [];
        // Store all the processed email IDs
        $processedEmails = [];

        foreach ($admins as $admin)
        {
            try
            {
                $processedEmails[] = $admin->getEmail();

                $this->core()->addRoles($admin, $roleIDs);

                $this->trace->info(TraceCode::ADMIN_BULK_ASSIGN_ROLE, [
                    'message'     => 'admin user role updated successfully',
                    'admin_email' => $admin->getEmail(),
                    'roles'       => $roleIDs,
                ]);
            }
            catch (\Throwable $t)
            {
                $this->trace->traceException(
                    $t,
                    Trace::ERROR,
                    TraceCode::ADMIN_BULK_ASSIGN_ROLE_EXCEPTION,
                    [
                        'admin_email' => $admin->getEmail(),
                        'roles'       => $roleIDs,
                    ]);

                $failedEmails[] = $admin->getEmail();
            }
        }

        $unprocessedEmails = array_diff($emailIDs, $processedEmails);

        return [
            'total_count'      => count($emailIDs),
            'valid_count'      => count($admins),
            'failed_count'     => count($failedEmails),
            'failed_emails'    => $failedEmails,
            'not_found_emails' => $unprocessedEmails,
        ];
    }

    /**
     * @throws BadRequestException
     */
    public function adminOrgReplications(array $input): array
    {
        // Log the initiation of org replication
        $this->trace->info(TraceCode::ADMIN_ORG_PERMISSIONS_INIT, [
            'input_data'  => $input,
            'method_name' => __FUNCTION__,
            'route_name'  => $this->app['api.route']->getCurrentRouteName(),
            'from_org'    => Org\Entity::silentlyStripSign($input[Constant::FROM_ORG_ID]),
        ]);

        // Validate the input data
        $validator = new Validator();
        $validator->validateInput('admin_org_replications', $input);

        // Extract and sanitize Org IDs
        $fromOrgId = Org\Entity::silentlyStripSign($input[Constant::FROM_ORG_ID]);
        $toOrgId   = Org\Entity::silentlyStripSign($input[Constant::TO_ORG_ID]);

        // Retrieve the source organization; throw an exception if not found
        $org = $this->repo->org->find($fromOrgId);
        if ($org === null) {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FROM_ORG_NOT_FOUND);
        }

        $replicationType = $input[Constant::REPLICATIONS_TYPE];
        $this->trace->info(TraceCode::ACS_ENTITY_FETCH, [$replicationType]);

        // Validate replication type; throw an exception for invalid types
        if ($replicationType !== 'feature') {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_REPLICATION_TYPE,
                null,
                "Invalid replication type. Specify a valid replication type."
            );
        }

        return $this->insertOrgReplications($replicationType, $fromOrgId, $toOrgId);
    }

    /**
     * @throws BadRequestException
     */
    private function insertOrgReplications($replicationType, $fromOrgId, $toOrgId): array {
        // Initialize the response structure
        $response = [];

        // Retrieve the target organization's name for success message
        $toOrgName = $this->repo->org->findOrFailPublic($toOrgId);

        $this->trace->info(TraceCode::ACS_ENTITY_FETCH, [$toOrgName]);

        if ($replicationType === 'permission') {
            // Retrieve permissions of the source organization
            $orgPermissions = $this->repo->admin->getOrgPermissionsList($fromOrgId);

            // Extract permission IDs for processing
            $permissionIds = array_column($orgPermissions, 'id');

            // Retrieve existing permission IDs of the target organization
            $existingPermissionIds = $this->repo->admin->existingPermissionIds($toOrgId);

            // Prepare data for batch insertion (only new permissions)
            $insertData = collect($permissionIds)
                ->diff($existingPermissionIds)
                ->map(function ($permissionId) use ($toOrgId) {
                    return [
                        'permission_id' => $permissionId,
                        'entity_type'   => 'org',
                        'entity_id'     => $toOrgId,
                    ];
                })
                ->toArray();

            // Throw exception if there are no new permissions to insert
            if (empty($insertData)) {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_NO_NEW_PERMISSIONS);
            }

            // Perform the permission replication process
            $replicatePermissions = $this->repo->admin->replicatePermissionsToOrg($insertData);

            // Log the completion of permission replication if successful
            if ($replicatePermissions) {
                $this->trace->info(TraceCode::ADMIN_ORG_PERMISSIONS_REPLICATIONS, [
                    'replicationData' => $insertData,
                    'status'          => $replicatePermissions,
                    'method_name'     => __FUNCTION__,
                    'route_name'      => $this->app['api.route']->getCurrentRouteName(),
                ]);
            }
        } else if ($replicationType === 'feature') {
            // Retrieve features of the source organization
            $orgFeatures = $this->repo->admin->getOrgFeaturesList($fromOrgId);
            $this->trace->info(TraceCode::ACS_ENTITY_FETCH, [$orgFeatures]);
            // Extract feature IDs and name for processing
            $featureNames = array_column($orgFeatures, 'feature_name');

            // Retrieve existing feature IDs of the target organization
            $existingFeatureNames = $this->repo->admin->existingFeatureNames($toOrgId);

            $insertFeaturesData = collect($featureNames)
                ->diff($existingFeatureNames)
                ->map(function ($featureName) use ($featureNames, $toOrgId) {
                    return [
                        'id' => Str::random(14),
                        'entity_type'   => 'org',
                        'entity_id'     => $toOrgId,
                        'name'          => $featureName,
                        "created_at" => Carbon::now()->getTimestamp(),
                    ];
                })
                ->toArray();

            // Throw exception if there are no new feature to insert
            if (empty($insertFeaturesData)) {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_NO_NEW_FEATURES);
            }

//            $colors = ["hide_rzpbrand_on_checkout"];
//            $colors = ["show_custom_dcc_disc"];
            $colors = ["custom_checkout_logo"];
//            $colors = ["bank_admin_adfs_login","vas_org_identifier","org_custom_branding","custom_onboarding_emails","white_labelled_pb"];

            $enableFeatures = [
                "names" => $colors,
                "entity_type" => "org",
                "should_sync" => "1",
                "entity_id" => $toOrgId,
            ];

            (new FeatureService())->addFeatures($enableFeatures);

        }

        // Set the success response
        $response = [
            'status'  => true,
            'message' => "{$replicationType} replicated successfully to the {$toOrgName->business_name} org.",
        ];
        return $response;
    }


}
