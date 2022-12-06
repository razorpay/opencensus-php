<?php

namespace RZP\Mail\Admin;

use Carbon\Carbon;
use Symfony\Component\Mime\Email;

use RZP\Mail\Base;
use RZP\Constants\Timezone;
use RZP\Constants\MailTags;

class MerchantInvitation extends Base\Mailable
{
    protected $admin;

    protected $org;

    protected $invitation;

    public function __construct(array $admin, array $org, array $invitation)
    {
        parent::__construct();

        $this->admin = $admin;

        $this->org = $org;

        $this->invitation = $invitation;
    }

    protected function addRecipients()
    {
        $email = $this->invitation['email'];

        // TODO have a fallover when contact name is not given
        $name = $this->invitation['format_data']['contact_name'] ?? '';

        $this->to($email, $name);

        return $this;
    }

    protected function addSender()
    {
        // $this->from appends the sender emails into an array and uses the first entry while sending email
        // Currently all emails are being sent as ADMIN, moving the default value in else block
        if ($this->org['custom_code'] !== 'rzp')
        {
            $this->from($this->org['from_email'], $this->org['display_name']);
        }
        else {
            $this->from(Base\Constants::MAIL_ADDRESSES[Base\Constants::ADMIN]);
        }

        return $this;
    }

    protected function addCc()
    {
        if ($this->org['custom_code'] === 'rzp')
        {
            $this->cc([]);
        }

        return $this;
    }

    protected function addSubject()
    {
        $orgName = $this->org['display_name'];

        // date format = 6th July 2015
        $date = Carbon::today(Timezone::IST)->format('jS F Y');

        $subject = sprintf("%s | Invitation for %s", $orgName, $date);

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $data = [
            'invitation' => $this->invitation,
            'adminName'  => $this->admin['name'],
            'org'        => $this->org,
            'hostname'   => $this->org['host_name'],
        ];

        $data['invitation']['token'] = $this->invitation['token'];

        $this->with($data);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message)
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
