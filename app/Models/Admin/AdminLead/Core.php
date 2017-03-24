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
    public function saveLead(Admin\Entity $admin, array $inviteData)
    {
        $lead = (new Entity)->generateId();

        $entityData = [
            'admin_id'   => $admin->getId(),
            'org_id'     => $admin->org_id,
            'email'      => $inviteData['contact_email'] ?? null,
            'token'      => str_random(40),
            'form_data'  => $inviteData,
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

        Mail::queue(
            'emails.admin.invite_merchant',
            ['data' => $data],
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
}
