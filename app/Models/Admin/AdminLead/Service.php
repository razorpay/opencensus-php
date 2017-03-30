<?php

namespace RZP\Models\Admin\AdminLead;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base;

class Service extends Base\Service
{
    public function sendInvitation($input)
    {
        $errors = [];
        $data = null;

        $admin = $this->app['basicauth']->getAdmin();

        $orgId = $admin->getOrgId();

        $entity = (new Entity)->getEntityName();

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
                $data);
        }

        $invitation = $this->core()->create($admin, $input);

        $this->core()->sendInvitationEmail($admin, $invitation);

        return $invitation->toArrayPublic();
    }

    public function getInvitations()
    {
        $admin = $this->app['basicauth']->getAdmin();
        $orgId = $admin->getPublicOrgId();

        $invitations = $this->repo->admin_lead->fetchByOrgId($orgId);

        return $invitations->toArrayPublic();
    }
}
