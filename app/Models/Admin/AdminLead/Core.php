<?php

namespace RZP\Models\Admin\AdminLead;

use RZP\Exception;
use RZP\Models\Admin\Admin;
use RZP\Models\Base;

class Core extends Base\Core
{
    public function saveLead(Admin\Entity $admin, array $inviteData)
    {
        $formData = json_encode($inviteData);

        $lead = (new Entity)->generateId();

        $entityData = [
            'admin_id'   => $admin->getId(),
            'org_id'     => $admin->org_id,
            'email'      => $inviteData['contact_email'] ?? null,
            'token'      => str_random(40),
            'form_data'  => $formData,
        ];

        $lead->build($entityData);

        $this->repo->saveOrFail($lead);

        return $lead;
    }

    public function sendInvitationEmail(Admin\Entity $admin, $invitation)
    {
        // TODO use queue mailers
        // $mailer = new MiscMailer();
        //
        // $mailer
        //     ->sendMerchantInvitationEmail($invitation, $admin->toArray())
        //     ->queueAndDeliver();
    }
}
