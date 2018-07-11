<?php

namespace RZP\Models\Admin\Admin;

use App;
use Cache;
use Carbon\Carbon;
use Hash;
use Mail;
use Event;
use Str;
use Request;

use RZP\Constants\HashAlgo;
use RZP\Error;
use RZP\Error\ErrorCode;
use RZP\Events\AuditLogEntry;
use RZP\Exception;
use RZP\Mail\Admin\Account as AdminMail;
use RZP\Models\Admin\Action;
use RZP\Models\Admin\Group;
use RZP\Models\Admin\Role;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Org\AuthPolicy;
use RZP\Models\Base;
use RZP\Models\Base\EsDao;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\MailTags;

class Service extends Base\Service
{
    const ADMIN_PASSWORD_RESET_TOKEN_KEY = 'password_reset_token_org_%s_admin_%s';

    const TOKEN = 'token';

    public function __construct()
    {
        parent::__construct();

        $this->adminOrgId = $this->app['basicauth']->getAdminOrgId();
    }

    public function authenticate(array $input)
    {
        $orgId = $this->app['basicauth']->getOrgId();

        return $this->login($orgId, $input);
    }

    public function login(string $orgId, array $input)
    {
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
            $data = $this->generateLoginToken($admin);

            $authPolicy->validateAfterLogin($admin);

            $this->fireAdminAction($admin, Action::LOGIN);

            return $data;
        }

        $this->handleAuthFailure($admin);
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

    public function forgotPassword(string $orgId, array $input)
    {
        $validator = new Validator();

        $org = $this->repo->org->findByPublicId($orgId);

        $input[Org\Entity::AUTH_TYPE] = $org->getAuthType();

        $validator->validateInput('forgot', $input);

        $admin = $this->getAdminFromEmail($orgId, $input['email']);

        $this->setPasswordResetToken($admin, $input);

        $this->sendAdminForgotPasswordEmail($admin, $input);

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
        $key = $this->getCacheKeyForResetToken($admin->org->getId(), $admin->getId());

        $expiresAt = Carbon::now()->addHours(1);

        $token = $this->generateToken($input);

        Cache::put($key, $token, $expiresAt);

        $input[self::TOKEN] = $token;
    }

    public function resetPassword(string $orgId, array $input)
    {
        $org = $this->repo->org->findByPublicId($orgId);

        $input[Org\Entity::AUTH_TYPE] = $org->getAuthType();

        // Get admin
        $admin = $this->getAdminFromEmail($orgId, $input['email']);

        $validator = new Validator($admin);

        $validator->validateInput('reset', $input);

        $key = $this->getCacheKeyForResetToken($org->getId(), $admin->getId());

        $resetToken = Cache::get($key);

        if (($resetToken === null) or
            ($resetToken !== $input['token']))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_PASSWORD_RESET_TOKEN);
        }

        $this->core()->updatePassword($admin, $input, true);

        if ($admin->isLocked())
        {
            $admin->unlock();
        }

        $this->repo->admin->saveOrFail($admin);

        // Flush the key so that the link cannot be used again.
        Cache::forget($key);

        return ['success' => true];
    }

    protected function getAdminFromEmail($orgId, $email)
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

        // Get the admin record
        $admin = $this->repo->admin->findByEmail($input['email']);

        if (($admin->getOAuthAccessToken() === $input['oauth_access_token']) and
            ($admin->getOAuthProviderID() === $input['oauth_provider_id']))
        {
            $data = $this->generateLoginToken($admin);

            $this->fireAdminAction($admin, Action::LOGIN_OAUTH);

            return $data;
        }

        $this->handleAuthFailure($admin, Action::LOGIN_FAIL_OAUTH);
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
        $authAdmin = $this->app['basicauth']->getAdmin();

        $admin = $this->repo->admin->findByPublicIdAndOrgId($adminId, $this->adminOrgId);

        $admin->getValidator()->validateSelfEditForbidden($authAdmin, $admin);

        // Trigger workflow
        $this->app['workflow']
             ->handle($admin, (new \StdClass()));

        $admin->setAuditAction(Action::DELETE_ADMIN);

        return $this->core()->delete($admin);
    }

    public function fetchMultiple()
    {
        $admins = $this->repo->admin->fetchByOrgId($this->adminOrgId, [Entity::GROUPS, Entity::ROLES]);

        return $admins->toArrayPublic();
    }

    public function editAdmin(string $adminId, array $input)
    {
        if (empty($this->adminOrgId))
        {
            $orgId = $this->app['basicauth']->getOrgId();
        }
        else
        {
            $orgId = $this->adminOrgId;
        }

        $admin = $this->repo->admin->findByPublicIdAndOrgId($adminId, $orgId);

        if (empty($input[Entity::ROLES]) === false)
        {
            Role\Entity::verifyIdAndStripSignMultiple($input[Entity::ROLES]);
        }

        if (empty($input[Entity::GROUPS]) === false)
        {
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

        $merchants = $this->repo->merchant->fetch($input);

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
}
