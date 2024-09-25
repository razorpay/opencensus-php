<?php

namespace RZP\Mail\Admin;

use Carbon\Carbon;
use Symfony\Component\Mime\Email;

use RZP\Mail\Base;
use RZP\Constants\Timezone;
use RZP\Constants\MailTags;
use RZP\Mail\Base\EmailHelper;

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

    public function shouldSendEmailViaStork(): bool
    {
        return  (new EmailHelper)->isStorkSupportedCheckViaSplitz($this->admin['id'], $this->org['id'], 'invite_merchant') ?? false;
    }

    public function getParamsForStork(): array
    {
        $storkParams = 
        [
            'template_name' => 'banking_mail_invite_merchant',
            'template_namespace' => 'payments_banking',
            'params' => $this->data,
        ];
        
        $storkParams['params']['sign_up_url'] = 'https://' . $this->data['hostname'] .'/#/access/signup?merchant_invitation=' . $this->data['invitation']['token'];
        if($this->org['custom_code'] === 'rzp')
        {
            $storkParams['params']['login_url'] = 'https://razorpay.com';
            $storkParams['params']['login_logo_url'] = public_path().'/img/logo_black.png';
        }else
        {
            $storkParams['params']['login_url'] = 'https://' . $this->data['hostname'];
            $storkParams['params']['login_logo_url'] = $this->org['login_logo_url'];
        }

        return $storkParams;
    }
}
