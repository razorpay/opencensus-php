<?php

namespace RZP\Models\Admin\AdminLead;

use RZP\Models\Base;
use RZP\Error\ErrorCode;

class Service extends Base\Service
{
    public function sendInvitation($input)
    {
        $errors = [];
        $data = null;

        $admin = $this->app['basicauth']->getAdmin();

        $customCode = $admin->getOrgId();

        $entity = (new Entity)->getEntityName();

        (new Validator)->validateOrgSpecificInput(
            'sendInvitation', $input, $customCode, $entity);

        $admin = $this->app['basicauth']->getAdmin();

        if ((empty($input['contact_email']) === false) and
            ($admin->email === $input['contact_email']))
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

        (new Core)->createInviteAndSendEmail($admin, $input);

        return ['success' => true];
    }

    public function getInvitations()
    {
        $admin = $this->app['basicauth']->getAdmin();
        $orgId = $admin->getPublicOrgId();

        $invitations = $this->repo->admin_lead->fetchByOrgId($orgId);

        return $invitations->toArrayPublic();
    }
}
