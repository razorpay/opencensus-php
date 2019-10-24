<?php

namespace RZP\Mail\Invitation\RazorpayX;

use App;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class Invite extends Mailable
{
    const SUPPORT_URL   = '';

    const SUBJECT       = 'Invitation to join %s | RazorpayX';

    const TEMPLATE_PATH = 'emails.invitation.razorpayx.invite';

    const INVITE_LINK_FORMAT = '%s/auth?invitation=%s';

    protected $invitation;

    protected $senderName;

    protected $config;

    public function __construct($invitationId, $senderName = null)
    {
        parent::__construct();

        $app = App::getFacadeRoot();

        $this->invitation = $app['repo']->invitation->find($invitationId);

        $this->config = $app['config'];

        $this->senderName = (is_null($senderName) === true) ? $this->invitation->merchant->getName() : $senderName;
    }

    protected function addSender()
    {
        $this->from(Constants::MAIL_ADDRESSES[Constants::X_SUPPORT],
                    Constants::HEADERS[Constants::X_SUPPORT]);

        return $this;
    }

    protected function addReplyTo()
    {
        $this->from(Constants::MAIL_ADDRESSES[Constants::X_SUPPORT],
                    Constants::HEADERS[Constants::X_SUPPORT]);

        return $this;
    }

    protected function addRecipients()
    {

        $this->to($this->invitation->getEmail());

        return $this;
    }

    protected function addSubject()
    {
        $businessName = $this->invitation->merchant->merchantDetail->getBusinessName();

        $this->subject(sprintf(self::SUBJECT, $businessName));

        return $this;
    }

    protected function addMailData()
    {
        $businessName = $this->invitation->merchant->merchantDetail->getBusinessName();

        $bankingUrl = $this->config['application.banking_service_url'];

        $inviteLink = sprintf(self::INVITE_LINK_FORMAT, $bankingUrl, $this->invitation->getToken());

        $this->with(
            [
                'business_name' => $businessName,
                'sender_name'   => $this->senderName,
                'role'          => $this->invitation->getRole(),
                'invite_link'   => $inviteLink,
                'support_url'   => self::SUPPORT_URL,
            ]
        );

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view(self::TEMPLATE_PATH);

        return $this;
    }
}
