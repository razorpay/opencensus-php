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

        $customCode = $admin->getCustomCode();

        $validation = (new Validator)->validateOrgSpecificInput(
            'sendInvitation', $input, $customCode);

        if ($validation->fails())
        {
            return array($validation->messages(), null);
        }

        $admin = $this->app['basicauth']->getAdmin();

        if ($admin->email === $input['contact_email'])
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

        $this->createInviteAndSendEmail($admin, $input);

        return ['success' => true];
    }
}
