<?php

namespace RZP\Models\Admin\Admin;

use App;
use Cache;
use Carbon\Carbon;
use Event;
use Hash;
use Mail;
use Str;

use RZP\Exception;
use RZP\Error;
use RZP\Error\ErrorCode;
use RZP\Events\AuditLogEntry;
use RZP\Models\Admin\Action;
use RZP\Models\Admin\Group;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Org\AuthPolicy;
use RZP\Models\Base;
use RZP\Models\Base\EsDao;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;


class Service extends Base\Service
{
    const ADMIN_PASSWORD_RESET_TOKEN_KEY = 'password_reset_token_org_%s_admin_%s';

    const TOKEN = 'token';

    public function authenticate(string $orgId, array $input)
    {
        \Database\DefaultConnection::set('live');

        $orgId = Org\Entity::verifyIdAndStripSign($orgId);

        return $this->login($orgId, $input);
    }

    public function login(string $orgId, array $input)
    {
        $email = $input['username'];

        // Get the admin record
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
            $authPolicy->validateLogin($admin, $input['password']);
        }
        catch (Exception\BadRequestValidationFailureException $e)
        {
            $admin->incrementFailedAttempts();
            $this->repo->saveOrFail($admin);

            throw $e;
        }

        // Valid password ?
        $isAuthenticated = true;

        $errorCode = null;

        if (Hash::check($input['password'], $admin->getPassword()))
        {
            $data = $this->generateLoginToken($admin);

            $validate = $authPolicy->validateLogin($admin, $input['password'], 'after');

            // Send admin, description, entity object

            if ($validate !== null)
            {
                $isAuthenticated = false;

                $errorCode = Error\ErrorCode::BAD_REQUEST_AUTH_VALIDATION_FAILED;
            }
            else
            {
                $this->fireAdminAction($admin, Action::LOGIN);
            }

            return $data;
        }
        else
        {
            $isAuthenticated = false;

            $errorCode = Error\ErrorCode::BAD_REQUEST_AUTHENTICATION_FAILED;
        }

        return $this->handleAuthFailure($isAuthenticated, $errorCode, $admin);
    }

    protected function handleAuthFailure(bool $isAuthenticated, string $errorCode, $admin)
    {
        if ($isAuthenticated === false)
        {
            $admin->incrementFailedAttempts();

            $this->fireAdminAction(
                $admin,
                Action::LOGIN_FAIL,
                ['failed_attempts' => $admin->getFailedAttempts()]);

            $this->repo->saveOrFail($admin);

            throw new Exception\BadRequestException($errorCode);

        }
    }

    protected function fireAdminAction(Entity $admin, array $action, array $customProperties = null)
    {
        $this->trace->info(TraceCode::HEIMDALL_AUDIT_LOG, ["admin" => $admin, "action" => $action]);

        if (!is_array($admin))
        {
            $admin = $admin->toArrayPublic();
        }

        event(new AuditLogEntry($admin, $action, $customProperties));
    }

    public function forgotPassword(string $orgId, array $input)
    {
        $validator = new Validator();

        $validator->validateInput('forgot', $input);

        $admin = $this->getAdminFromEmail($orgId, $input['email']);

        // Validate if admin's org allows password reset
        $admin->getValidator()->validateOrgSupportsPasswordReset(
            $admin->org->getAuthType());

        $this->setPasswordResetToken($admin, $input);

        $this->sendAdminForgotPasswordEmail($admin, $input);

        return ['success' => true];
    }

    protected function sendAdminForgotPasswordEmail(Entity $admin, $input)
    {
        $org = $admin->org;

        $from       = 'support@razorpay.com';
        $replyTo    = 'support@razorpay.com';
        $fromHeader = 'Team Razorpay';
        $to         = $admin->getEmail();
        $subject    = 'Reset your password for' . $org->getDisplayName() . ' dashboard';

        $view = 'emails.auth.admin_password_reset';

        $template = [
            'user' => [
                'email'    => $admin->getEmail(),
                'org'      => $org->getDisplayName(),
                'resetUrl' => $input['reset_password_url'] . '?' . http_build_query([$input[self::TOKEN]])
            ]
        ];

        Mail::queue(
            $view,
            $template,
            function ($message) use ($subject, $to, $from, $fromHeader, $replyTo)
            {
                $message->to($to);
                $message->from($from, $fromHeader);
                $message->subject($subject);
                $message->replyTo($replyTo);
            }
        );
    }

    protected function generateToken()
    {
        $app = App::getFacadeRoot();

        $secret = $app->config->get('app.key');

        $token =  hash_hmac('sha256', Str::random(40), $secret);

        return $token;
    }

    protected function setPasswordResetToken(Entity $admin, array & $input)
    {
        $key = sprintf(self::ADMIN_PASSWORD_RESET_TOKEN_KEY,
            $admin->org->getId(),
            $admin->getId());

        $expiresAt = Carbon::now()->addHours(1);

        $token = $this->generateToken($input);

        Cache::put($key, $token, $expiresAt);

        $input[self::TOKEN] = $token;
    }

    public function resetPassword(string $orgId, array $input)
    {
        $validator = new Validator();

        $validator->validateInput('reset', $input);

        // Get admin
        $admin = $this->getAdminFromEmail($orgId, $input['email']);

        $org = $admin->org;

        // Validate if admin's org allows password reset
        $admin->getValidator()->validateOrgSupportsPasswordReset($org->getAuthType());

        $key = sprintf(self::ADMIN_PASSWORD_RESET_TOKEN_KEY, $org->getId(), $admin->getId());

        $resetToken = Cache::get($key);

        if (($resetToken === null) or
            ($resetToken !== $input['token']))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_PASSWORD_RESET_TOKEN);
        }

        $this->core()->updatePassword($admin, $input, true);

        return ['success' => true];
    }

    protected function getAdminFromEmail($orgId, $email)
    {
        $admin = $this->repo->admin->findByOrgIdAndEmail($orgId, $email, ['org']);

        if ($admin === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ADMIN_EMAIL_IS_NOT_VALID);
        }

        return $admin;
    }

    public function loginWithOAuth($input)
    {
        // TODO: error validation

        // Get the admin record
        $admin = $this->repo->admin->findByEmail($input['email']);

        // Valid token ?
        if (($admin->oauth_access_token === $input['oauth_access_token']) and
            ($admin->oauth_provider_id === $input['oauth_provider_id']))
        {
            $data = $this->generateLoginToken($admin);

            $this->fireAdminAction($admin, Action::LOGIN_OAUTH);

            return $data;
        }
        else
        {
            $admin->incrementFailedAttempts();

            $this->fireAdminAction(
                $admin,
                Action::LOGIN_FAIL_OAUTH,
                ['failed_attempts' => $admin->getFailedAttempts()]);

            $this->repo->saveOrFail($admin);

            throw new Exception\BadRequestException(
                Error\ErrorCode::BAD_REQUEST_AUTHENTICATION_FAILED);
        }

        return null;
    }

    private function generateLoginToken($admin)
    {
        $this->fireAdminAction($admin, Action::GENERATE_LOGIN_TOKEN);

        $admin->resetFailedAttempts();

        $admin->updateLastLoginAt();

        $this->repo->saveOrFail($admin);

        $tokenAttributes = [
            'token'      => str_random(40),
            'expires_at' => Carbon::now()->addHours(1)->timestamp
        ];

        // Create a token for the user
        $token = $this->core()->createAuthToken($admin, $tokenAttributes);

        $admin = $admin->toArrayPublic();

        $admin['token'] = $token->getToken();

        return $admin;
    }

    public function createAdmin(string $orgId, array $input)
    {
        $org = $this->repo->org->findByPublicId($orgId);

        $admin = $this->core()->create($org, $input);

        $this->sendAdminCreateEmail($admin, $input);

        return $admin->toArrayPublic();
    }

    public function sendAdminCreateEmail($admin, $input)
    {
        $org = $admin->org;

        if ($org['auth_type'] !== 'password')
        {
            return;
        }

        $from       = 'support@razorpay.com';
        $replyTo    = 'support@razorpay.com';
        $fromHeader = 'Team Razorpay';
        $to         = $admin->getEmail();
        $subject    = 'Your admin account details for ' . $org->getDisplayName() . ' dashboard';

        $view = [
            'html' => 'emails.admin.user',
            'text' => 'emails.admin.user_text'
        ];

        $template = [
            'user' => [
                'email' => $admin->getEmail(),
                // Hack for now. Remove it
                'password' => $input['password'],
                'org' => $org->getDisplayName(),
                'url' => $this->app['config']->get('applications.dashboard.url'),
            ]
        ];

        Mail::queue(
            $view,
            $template,
            function ($message) use ($subject, $to, $from, $fromHeader, $replyTo)
            {
                $message->to($to);
                $message->from($from, $fromHeader);
                $message->subject($subject);
                $message->replyTo($replyTo);
            }
        );
    }

    public function getAdmin(string $orgId, string $adminId)
    {
        // Fetch admin with relations
        $admin = $this->repo->admin->findByPublicIdAndOrgIdWithRelations(
            $adminId, $orgId, ['groups', 'roles']);

        return $admin->toArrayPublic();
    }

    public function getAdminByAppAuth(string $orgId, array $input)
    {
        $token = $input['token'];

        $adminToken = $this->repo->admin_token->findOrFailToken($token);

        $adminId = $adminToken->getAdminId();

        $admin = $this->repo->admin->findByIdAndOrgIdWithRelations(
            $adminId, $orgId, ['groups', 'roles', 'roles.permissions']);

        $roles = $admin->roles;
        $permissions = [];
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

        $admin['permissions'] = $permissions->all();

        $admin['roles'] = $roleNames;

        $admin['groups'] = $groupRules;

        return $admin;
    }

    public function deleteAdmin(string $orgId, string $adminId)
    {
        $authAdmin = $this->app['basicauth']->getAdmin();

        $admin = $this->repo->admin->findByPublicIdAndOrgId($adminId, $orgId);

        $admin->getValidator()->validateSelfEditForbidden($authAdmin, $admin);

        $admin->setAuditAction(Action::DELETE_ADMIN);

        return $this->core()->delete($admin);
    }

    public function fetchMultiple(string $orgId, array $input)
    {
        $orgId = Org\Entity::verifyIdAndStripSign($orgId);

        $admins = $this->repo->admin->fetchByOrgId($orgId);

        return $admins->toArrayPublic();
    }

    public function editAdmin(string $orgId, string $adminId, array $input)
    {
        $authAdmin = $this->app['basicauth']->getAdmin();

        $admin = $this->repo->admin->findByPublicIdAndOrgId($adminId, $orgId);

        $admin->getValidator()->validateSelfEditForbidden($authAdmin, $admin);

        $admin = $this->core()->edit($admin, $input);

        return $admin->toArrayPublic();
    }

    public function getMerchantIds($orgId, $adminId)
    {
        $admin = $this->repo->admin->findByPublicIdAndOrgId($adminId, $orgId);

        $adminGroups = $admin->groups->toArray();

        $adminMerchants = $admin->merchants->toArray();

        // Get entire children hierarchy for each group
        // that the admin belongs to

        $childrenGroups = [];

        $childrenAdmins = [];

        foreach ($adminGroups as $group)
        {
            $groupChildren = (new Group\Service)->getChildrenHierarchy($orgId, $group['id']);

            $childrenGroups = array_merge($childrenGroups, $groupChildren);

            foreach ($groupChildren as $group)
            {
                $group = new Group\Entity($group);

                $childrenAdmins = array_merge($childrenAdmins, $group->admins->toArray());
            }
        }

        // An admin could belong to multiple groups
        // so we need to select unique admins from $childrenAdmins

        $adminIds = array_unique( array_column($childrenAdmins, 'id') );

        $childrenAdmins = array_filter($childrenAdmins, function ($value, $key) use ($adminIds)
        {
            return in_array($key, array_keys($adminIds));
        }, ARRAY_FILTER_USE_BOTH);

        // Loop over all the groups and get their merchants
        // TODO: this can be placed in the previous inner foreach as well

        $merchants = [];

        foreach ($childrenGroups as $group)
        {
            $groupId = Group\Entity::getSignedId($group['id']);

            $group = $this->repo->group->findByPublicIdAndOrgId($groupId, $orgId);

            $merchants = array_merge($merchants, $group->merchants->toArray());
        }

        // Loop over all the admins and get their merchants
        //
        // Note: currently a merchant can belong to only 1 admin
        // not by DB design but by code constraints so we don't
        // need to run the list of merchants through a uniqueness check

        foreach ($childrenAdmins as $admin)
        {
            $adminId = Entity::getSignedId($admin['id']);

            $admin = $this->repo->admin->findByPublicIdAndOrgId($adminId, $orgId);

            $merchants = array_merge($merchants, $admin->merchants->toArray());
        }

        // Finally merging the admin's merchants with the list
        // of merchants resolved from his hierarchy
        $merchants = array_merge($merchants, $adminMerchants);

        // ... and we'll return all the merchant IDs to the dashboard client
        $merchantIds = array_column($merchants, 'id');

        // Get all the admins of the merchant IDs
        // TODO: This can be moved in one of the foreach blocks above
        // for better performance (less computation)

        $responseHash = [];

        $merchants = $this->repo->merchant->findManyByIdsWithRelations($merchantIds);

        foreach ($merchants as $merchant)
        {
            $responseHash[$merchant->id] = $merchant->admins->first()->name;
        }

        return $responseHash;
    }

    public function lockUnusedAccounts()
    {
        $timestamp = Carbon::now()->subDays(30)->timestamp;

        $unactivatedAccounts = $this->repo->admin->lockUnactivatedAccounts($timestamp);

        $timestamp = Carbon::now()->subDays(90)->timestamp;

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
        $admin = $this->app['basicauth']->getAdmin();

        $this->repo->admin_token->deleteTokensForAdmin($admin->getId());

        return ['success' => true];
    }
}
