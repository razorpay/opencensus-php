<?php

namespace RZP\Models\Admin\AdminLead;

use Carbon\Carbon;

use RZP\Trace\TraceCode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Models\Feature;


class Service extends Base\Service
{
    public function sendInvitation($orgId, $input)
    {
        $data = null;

        $admin = $this->app['basicauth']->getAdmin();

        $entity = (new Entity)->getEntityName();


        $org = OrgEntity::find($orgId);

        $ds = false;

        if ($org->isFeatureEnabled(Feature\Constants::ORG_PROGRAM_DS_CHECK) === true)
        {
            if ((isset($input["is_ds_merchant"]) === false) or ($input["is_ds_merchant"] === '0'))
            {
                $this->trace->info(TraceCode::BLOCK_NON_DS_MERCHANT_REGISTRATION, ["input" => $input]);

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_ACCESS_DENIED,
                    null,
                    $data);
            }
            $ds = true;
            unset($input['is_ds_merchant']);
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

        if ($ds === true) {
            $input["ds"] = true;
        }

        $invitation = $this->core()->create($admin, $input);

        $this->core()->sendInvitationEmail($admin, $invitation);

        return $invitation->toArrayPublic();
    }

    public function getInvitations(string $orgId)
    {
        $invitations = $this->repo->admin_lead->fetchByOrgId($orgId);

        return $invitations->toArrayPublic();
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
