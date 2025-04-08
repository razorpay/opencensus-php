<?php

namespace RZP\Models\Admin\AdminLead;

use Carbon\Carbon;

use RZP\Trace\TraceCode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Permission;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Models\Feature;
use RZP\Models\Merchant\Core as MerchantCore;


class Service extends Base\Service
{
    public function sendInvitation($orgId, $input)
    {
        $data = null;

        $admin = $this->app['basicauth']->getAdmin();

        $entity = (new Entity)->getEntityName();

        $merchantType = $this->getMerchantType($input);

        $properties = [
            'id'            => $orgId,
            'experiment_id' => $this->app['config']->get('app.banking_redirection_enabled'),
        ];

        $isExperimentEnabled =  (new MerchantCore())->isSplitzExperimentEnable($properties, 'enable');

        if($isExperimentEnabled === false){
            $this->validateInvitation($orgId, $input);
        }

        (new Validator)->validateOrgSpecificInput(
            'sendInvitation', $input, $orgId, $entity);

        if ((empty($input['contact_email']) === false) and
            ($admin->getEmail() === $input['contact_email']))
        {
            $data = [
                'email'       => $input['contact_email'],
                'admin_email' => $admin->getEmail(),
                'org_id'      => $admin->getOrgId(),
            ];

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ADMIN_SELF_INVITE_PROHIBITED,
                null,
                $data);
        }

        if (empty($merchantType) === false)
        {
            $input[Constants::MERCHANT_TYPE] = $merchantType;
        }

        $invitation = $this->core()->create($admin, $input);

        $this->core()->sendInvitationEmail($admin, $invitation, $merchantType);

        return $invitation->toArrayPublic();
    }

    private function getMerchantType($input)
    {
        if ((isset($input["is_ds_merchant"]) === true) and ($input["is_ds_merchant"] == 1))
        {
            return Constants::DS_ONLY_MERCHANT;
        }

        return $input[Constants::MERCHANT_TYPE] ?? null;
    }

    public function validateInvitation($orgId, &$input)
    {
        $user = $this->repo->user->getUserFromEmail(strtolower($input['contact_email']));

        if (empty($user) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_EMAIL_ALREADY_EXISTS,
                null,
                $input);
        }

        $org = OrgEntity::find($orgId);

        $this->trace->info(TraceCode::VALIDATE_ADMIN_INVITATION, ["input" => $input]);

        if ($org->isFeatureEnabled(Feature\Constants::ORG_PROGRAM_DS_CHECK) === true)
        {
            if ((isset($input["is_ds_merchant"]) === true) and ($input["is_ds_merchant"] == 1))
            {
                $this->trace->info(TraceCode::UNBLOCK_DS_MERCHANT_REGISTRATION, ["input" => $input]);

                unset($input['is_ds_merchant']);

                return;
            }

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ACCESS_DENIED,
                null,
                $input);
        }

        if (OrgEntity::isOrgRazorpay($orgId) === true)
        {
            if ((isset($input[Constants::MERCHANT_TYPE]) === true) and
                (array_key_exists($input[Constants::MERCHANT_TYPE], Constants::ALLOWED_MERCHANT_TYPE_FEATURE_MAPPING)) === true)
            {
                $this->trace->info(TraceCode::UNBLOCK_MERCHANT_REGISTRATION, ["input" => $input]);

                unset($input[Constants::MERCHANT_TYPE]);

                return;
            }

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ACCESS_DENIED,
                null,
                $input);
        }
    }

    public function getInvitations(string $orgId)
    {
        $invitations = $this->repo->admin_lead->fetchByOrgId($orgId);

        if ($this->customInvitationFlowEnabled($orgId) === true) 
        {
            return $this->getCustomInvitations($invitations, $orgId);
        }
        

        return $invitations->toArrayPublic();
    }

    protected function customInvitationFlowEnabled($orgId)
    {   

        $orgId = Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        $permissionEnabled = (new Org\Service)->isRequiredPermissionEnabledforOrg($orgId, Permission\Name::CUSTOM_INVITE_MERCHANT_FLOW);
        
        return $permissionEnabled === true;
    }

    protected function getCustomInvitations($invitations, $orgId)
    {   

        if (empty($invitations) === true) 
        {
            return [];
        }

        $org = $this->repo->org->find($orgId);

        if (empty($org) === true) 
        {
            return $invitations->toArrayPublic();
        }

        $host_name = $org->getPrimaryHostName();
       
        $userEmails = $invitations->pluck('email')->toArray();
        $users = $this->repo->user->getMultipleUsersByEmails($userEmails);
        
        $usersMap = [];
        foreach ($users as $user) 
        {
            $usersMap[$user['email']] = $user;
        }
        
        $result = $invitations->toArray();

        foreach ($result as $key => $invitation) 
        {

            // check if host name is present
            if (empty($host_name) === false && empty($invitation['token']) === false) {
                $result[$key]['form_data']['invite_url'] = 'https://' . $host_name .'/#/access/signup?merchant_invitation=' . $invitation['token'];
            }
                
            $email = $invitation['email'] ?? "";
            
            $user = $usersMap[$email];
            if (empty($user) === false && empty($user['contact_mobile']) === false) {
                $result[$key]['form_data']['contact_mobile'] = $user['contact_mobile'];
            }
        }

        return $result;
    }

    public function verify(string $token)
    {
        $adminLead = $this->repo->admin_lead->findByTokenOrFail($token);

        return $adminLead->toArrayPublic();
    }

    public function editInvitation(string $orgId, string $id, array $input)
    {
        $adminLead = $this->repo->admin_lead->findByPublicIdAndOrgId($id, $orgId);

        if (empty($input[Entity::SIGNED_UP]) === false)
        {
            $input[Entity::SIGNED_UP_AT] = Carbon::now()->getTimestamp();

            unset($input[Entity::SIGNED_UP]);
        }

        $adminLead = $this->core()->edit($adminLead, $input);

        return $adminLead->toArrayPublic();
    }
}
