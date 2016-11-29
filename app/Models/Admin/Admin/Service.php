<?php

namespace RZP\Models\Admin\Admin;

use Hash;
use RZP\Error;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Role;
use RZP\Models\Admin\Group;
use RZP\Models\Admin\Org\AuthPolicy;
use Mail;

class Service extends Base\Service
{
    public function login($input)
    {
        // Get the admin record
        $admin = $this->repo->admin->findByEmail($input['username']);

        if ($admin === null)
        {
            throw new Exception\BadRequestException(
                Error\ErrorCode::BAD_REQUEST_UNAUTHORIZED);
        }

        $admin->getValidator()->validateCredentials($input);

        $authPolicy = new AuthPolicy\Service;
        $authPolicy->validateLogin($admin, $input['password']);

        // Valid password ?
        if (Hash::check($input['password'], $admin->getPassword()))
        {
            $data = $this->generateLoginToken($admin);

            $validate = $authPolicy->validateLogin($admin, $input['password'], 'after');

            if ($validate !== null)
            {
                return $validate;
            }

            return $data;
        }
        else
        {
            $admin->incrementFailedAttempts();
            $this->repo->saveOrFail($admin);
        }

        return null;
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

            return $data;
        }
        else
        {
            $admin->incrementFailedAttempts();
            $this->repo->saveOrFail($admin);
        }

        return null;
    }

    private function generateLoginToken($admin)
    {
        $admin->resetFailedAttempts();
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

        $admin = $this->core()->create($input, $org);

        return $admin->toArrayPublic();
    }

    public function sendAdminCreateEmail($data, $input)
    {
        $org = (new Org\Service)->fetch($data['org_id']);

        $from       = 'support@razorpay.com';
        $replyTo    = 'support@razorpay.com';
        $fromHeader = 'Team Razorpay';
        $to         = $data['email'];
        $subject    = 'Your admin account details for '. $org['display_name'].' dashboard';

        $view = [
            'html' => 'emails.admin.user',
            'text' => 'emails.admin.user_text'
        ];

        $template = [
            'user' => [
                'email' => $data['email'],
                'password' => $input['password'],
                'org' => $org['display_name'],
                'url' => $_ENV['APP_DASHBOARD_URL'],
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
        $admin = $this->repo->admin->findByPublicIdAndOrgId($adminId, $orgId);

        return $admin->toArrayPublic();
    }

    public function getAdminByAppAuth(string $orgId, array $input)
    {
        $token = $input['token'];

        $adminToken = $this->repo->admin_token->retrieveByToken($token);

        $adminId = $adminToken->getAdminId();

        $admin = $this->repo->admin->findByPublicIdAndOrgId($adminId, $orgId);

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

        foreach ($roles as $role)
        {
            $roleNames[] = $role['name'];
            $rolePermissions = $role->permissions;

            foreach ($rolePermissions as $rolePermission)
            {
                $rolePermission = $rolePermission->toArrayPublic();
                $permissions[] = $rolePermission['name'];
            }
        }

        $admin = $admin->toArrayPublic();

        $admin['permissions'] = $permissions;

        $admin['roles'] = $roleNames;

        $admin['groups'] = $groupRules;

        return $admin;
    }

    public function getAdminByAttr($orgId, $attr, $attrVal)
    {
        $orgId = Org\Entity::verifyIdAndStripSign($orgId);

        $admin = $this->repo->admin->findOrFailByAttr($orgId, $attr, $attrVal);

        return $admin->toArrayPublic();
    }

    public function getAdminById(string &$adminId)
    {
        $admin = $this->repo->org->findByPublicId($adminId);

        return $admin->toArrayPublic();
    }

    public function deleteAdmin(string $orgId, string $adminId)
    {
        $admin = $this->repo->admin->findByPublicIdAndOrgId($adminId, $orgId);

        return $this->core()->delete($admin);
    }

    public function updateRolesForAdmin(
        string $orgId,
        string $adminId,
        array $input)
    {
        $admin = $this->repo->admin->findByPublicIdAndOrgId($adminId, $orgId);

        $roleIds = [];

        Role\Entity::verifyIdAndStripSignMultiple($input['roles']);

        $admin->roles()->sync($input['roles']);

        // Check if this is required here?
        $admin = $this->repo->admin->retrieveByOrgIdAndIdOrFail($orgId, $adminId);

        return $admin->toArrayPublic();
    }

    public function addMerchantToAdmin(
        string $orgId,
        string $adminId,
        string $merchantId)
    {
        $admin = $this->repo->admin->findByPublicIdAndOrgId($adminId, $orgId);

        $merchant = $this->repo->merchant->findByPublicId($merchantId);

        $this->repo->admin->addMerchantOrFail($admin, $merchant);
    }

    public function revokeRoleFromAdmin(
        string $orgId,
        string $adminId,
        string $roleId)
    {
        $admin = $this->repo->admin->findByPublicIdAndOrgId($adminId, $orgId);

        $role = $this->repo->role->findByPublicId($roleId);

        if ($role->getOrgId() != $orgId)
        {
            throw new Exception\LogicException(
                'The role does not belong to the organization');
        }

        $this->repo->admin->revokeRoleOrFail($admin, $role);
    }

    public function fetchMultiple(string $orgId, array $input)
    {
        $orgId = Org\Entity::verifyIdAndStripSign($orgId);

        $admins = $this->repo->admin->fetchAdminsForOrg($orgId, $input);

        return $admins->toArrayPublic();
    }

    public function editAdmin(string $orgId, string $adminId, array $input)
    {
        $admin = $this->repo->admin->findByPublicIdAndOrgId($adminId, $orgId);

        $admin = $this->core()->edit($orgId, $adminId, $input);

        return $admin->toArrayPublic();
    }

    public function getMerchantIds($orgId, $adminId)
    {
        // @todo: Add coments explaining the flow.

        $admin = $this->repo->admin->findByPublicIdAndOrgId($adminId, $orgId);
        $adminGroups = $admin->groups;
        $adminGroups = json_decode(json_encode($adminGroups), true);
        $adminMerchants = $admin->merchants;
        $adminMerchants = json_decode(json_encode($adminMerchants), true);
        $admin = json_decode(json_encode($admin), true);

        $merchants = [];
        $visibleGroups = $adminGroups;
        $admin = new Entity($admin);

        foreach ($adminGroups as $group)
        {
            (new Group\Service)->getRejectChildren($orgId, $group['id'], $visibleGroups);
        }

        $visibleGroups = array_unique($visibleGroups, SORT_REGULAR);

        foreach ($visibleGroups as $group)
        {
            $group = new Group\Entity($group);
            $merchants = array_merge($merchants, $group->merchants->all());
        }

        $merchants = array_merge($merchants, $adminMerchants);

        $merchantIds = array_column($merchants, 'id');

        return $merchantIds;
    }

    public function lockUnusedAccounts()
    {
        $timestamp = Carbon::now()->subDays(30)->timestamp;

        $unactivatedAccounts = $this->repo->admin->lockUnactivatedAccounts($timestamp);

        $timestamp = Carbon::now()->subDays(90)->timestamp;

        $unusedAccounts = $this->repo->admin->lockUnusedAccounts($timestamp);

        return ['count' => $unactivatedAccounts + $unusedAccounts];
    }
}
