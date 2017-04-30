<?php

namespace RZP\Models\Admin\AdminLead;

use Carbon\Carbon;
use Mail;

use RZP\Constants\MailTags;
use RZP\Exception;
use RZP\Mail\Admin\MerchantInvitation as MerchantInvitationMail;
use RZP\Models\Admin\Admin;
use RZP\Models\Base;

class Core extends Base\Core
{
    public function create(Admin\Entity $admin, array $inviteData)
    {
        $lead = (new Entity)->generateId();

        $entityData = [
            Entity::ADMIN_ID   => $admin->getId(),
            Entity::ORG_ID     => $admin->getOrgId(),
            Entity::EMAIL      => $inviteData['contact_email'] ?? null,
            Entity::TOKEN      => str_random(40),
            Entity::FORM_DATA  => $inviteData,
        ];

        $lead->build($entityData);

        $this->repo->saveOrFail($lead);

        return $lead;
    }

    public function sendInvitationEmail(Admin\Entity $admin, Entity $invitation)
    {
        $merchantInvitationMail = new MerchantInvitationMail($admin, $invitation);

        Mail::queue($merchantInvitationMail);
    }

    public function edit(Entity $adminLead, array $input)
    {
        $adminLead->edit($input);

        $this->repo->saveOrFail($adminLead);

        return $adminLead;
    }
}
