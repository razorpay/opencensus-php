<?php

namespace RZP\Mail\Invitation\RazorpayX;

use App;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class Invite extends Mailable
{
    const SUPPORT_URL        = '';

    const SUBJECT            = 'Invitation to join %s | RazorpayX';

    const TEMPLATE_PATH      = 'emails.invitation.razorpayx.invite';

    const INVITE_LINK_FORMAT = '%s/auth?invitation=%s';

    protected $invitation;

    protected $invitationId;

    protected $senderName;

    public function __construct($invitationId, $senderName = null)
    {
        parent::__construct();

        $app = App::getFacadeRoot();

        $this->invitationId = $invitationId;

        $this->invitation = $app['repo']->invitation->find($invitationId);

        $this->senderName = $senderName;
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
        $this->subject(sprintf(self::SUBJECT, $this->getBusinessName()));

        return $this;
    }

    protected function addMailData()
    {
        $config = App::getFacadeRoot()['config'];

        $invitation = $this->getInvitation();

        $bankingUrl = $config['application.banking_service_url'];

        $inviteLink = sprintf(self::INVITE_LINK_FORMAT, $bankingUrl, $invitation->getToken());

        $senderName = ($this->senderName === null) ? $invitation->merchant->getName() : $this->senderName;

        $this->with(
            [
                'business_name' => $this->getBusinessName(),
                'sender_name'   => $senderName,
                'role'          => $invitation->getRole(),
                'invite_link'   => $inviteLink,
                'support_url'   => self::SUPPORT_URL,
            ]
        );

        return $this;
    }

    protected function getBusinessName()
    {
        $invitation = $this->getInvitation();

        return $invitation->merchant
                          ->merchantDetail
                          ->getBusinessName();
    }

    protected function getInvitation()
    {
        if ($this->invitation === null)
        {
            $app = App::getFacadeRoot();

            $this->invitation = $app['repo']->invitation->find($this->invitationId);
        }

        return $this->invitation;
    }

    protected function addHtmlView()
    {
        $this->view(self::TEMPLATE_PATH);

        return $this;
    }
}
