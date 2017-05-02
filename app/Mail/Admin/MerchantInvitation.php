<?php

namespace RZP\Mail\Admin;

use Carbon\Carbon;
use RZP\Constants\MailTags;
use RZP\Mail\Base;
use RZP\Models\Admin\Admin;
use RZP\Models\Admin\AdminLead;

class MerchantInvitation extends Base\Mailable
{
    protected $admin;

    protected $invitation;

    public function __construct(Admin\Entity $admin, AdminLead\Entity $invitation)
    {
        parent::__construct();

        $this->admin = $admin;

        $this->invitation = $invitation;
    }

    protected function addRecipients()
    {
        $email = $this->invitation->getEmail();

        // TODO have a fallover when contact name is not given
        $name = $this->invitation->getFormData()['contact_name'] ?? '';

        $this->to($email, $name);

        return $this;
    }

    protected function addSender()
    {
        $this->from(Base\Constants::MAIL_ADDRESSES[Base\Constants::ADMIN]);

        return $this;
    }

    protected function addCc()
    {
        $this->cc(Base\Constants::MAIL_ADDRESSES[Base\Constants::NOTIFICATIONS]);

        return $this;
    }

    protected function addSubject()
    {
        $orgName = $this->admin->org->getDisplayName();

        // date format = 6th July 2015
        $date = Carbon::today('Asia/Kolkata')->format('jS F Y');

        $subject = sprintf("%s | Invitation for %s", $orgName, $date);

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $data = [
            'invitation' => $this->invitation->toArrayPublic(),
            'adminName'  => $this->admin->getName(),
        ];

        $data['invitation']['token'] = $this->invitation->getToken();

        $this->with($data);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(
                MailTags::HEADER, MailTags::ADMIN_INVITE_MERCHANT);
        });

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.admin.invite_merchant');

        return $this;
    }
}
