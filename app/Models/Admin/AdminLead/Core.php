<?php

namespace RZP\Models\Admin\AdminLead;

use Carbon\Carbon;
use Mail;

use RZP\Constants\MailTags;
use RZP\Exception;
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
        $orgName = $admin->org->getDisplayName();

        // date format = 6th July 2015
        $date = Carbon::today('Asia/Kolkata')->format('jS F Y');

        $subject = sprintf("%s | Invitation for %s", $orgName, $date);

        $email = $invitation->getEmail();

        // TODO have a fallover when contact name is not given
        $contactName = $invitation->getFormData()['contact_name'] ?? '';

        $data = [
            'invitation' => $invitation->toArrayPublic(),
            'adminName'  => $admin->getName(),
        ];

        $data['invitation']['token'] = $invitation->getToken();

        Mail::queue(
            'emails.admin.invite_merchant',
            $data,
            function ($message) use ($subject, $email, $contactName)
            {
                $message->to($email, $contactName);

                $message->from('admin@razorpay.com');
                $message->cc('notifications@razorpay.com');

                $message->subject($subject);

                $headers = $message->getHeaders();

                $headers->addTextHeader(
                    MailTags::HEADER, MailTags::ADMIN_INVITE_MERCHANT);
            });
    }

    public function edit(Entity $adminLead, array $input)
    {
        $adminLead->edit($input);

        $this->repo->saveOrFail($adminLead);

        return $adminLead;
    }
}
