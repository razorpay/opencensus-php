<?php

namespace RZP\Models\Admin\AdminLead;

use Mail;

use RZP\Models\Base;
use RZP\Models\Admin\Admin;
use RZP\Mail\Admin\PartnerInvitation as PartnerInvitationMail;
use RZP\Mail\Admin\MerchantInvitation as MerchantInvitationMail;

class Core extends Base\Core
{
    public function create(Admin\Entity $admin, array $inviteData)
    {
        $lead = (new Entity)->generateId();

        $entityData = [
            Entity::EMAIL      => $inviteData['contact_email'] ?? null,
            Entity::TOKEN      => str_random(40),
            Entity::FORM_DATA  => $inviteData,
        ];

        $lead->build($entityData);

        $lead->admin()->associate($admin);

        $lead->org()->associate($admin->org);

        $this->repo->saveOrFail($lead);

        return $lead;
    }

    public function sendInvitationEmail(Admin\Entity $admin, Entity $invitation, $merchantType)
    {
        $org = $admin->org->toArrayPublic();
        $org['host_name'] = $admin->org->getPrimaryHostName();

        $admin = $admin->toArrayPublic();

        $token = $invitation->getToken();
        $invitation = $invitation->toArrayPublic();
        $invitation['token'] = $token;

        $merchantInvitationMailClazz = MerchantInvitationMail::class;

        if(empty($merchantType) === false and
           array_key_exists($merchantType, Constants::MERCHANT_TYPE_INVITATION_MAPPING))
        {
            $merchantInvitationMailClazz = Constants::MERCHANT_TYPE_INVITATION_MAPPING[$merchantType];
        }

        $merchantInvitationMail = new $merchantInvitationMailClazz($admin, $org, $invitation);

        Mail::queue($merchantInvitationMail);
    }

    public function edit(Entity $adminLead, array $input)
    {
        $adminLead->edit($input);

        $this->repo->saveOrFail($adminLead);

        return $adminLead;
    }

    public function getByAdminId(string $adminId)
    {
        return $this->repo
                    ->admin_lead
                    ->findByAdminId($adminId);
    }
}
