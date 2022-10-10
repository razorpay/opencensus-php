<?php

namespace RZP\Mail\Invitation\Razorpayx;

use App;
use RZP\Constants\Product;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Models\User\Role;

class BankLmsInvite extends Mailable
{

    const SUPPORT_URL        = 'https://razorpay.com/support/#request/merchant';

    const SUBJECT            = 'Invitation to join RBL - Partner LMS | RazorpayX';

    const NEW_USER_TEMPLATE_PATH      = 'emails.invitation.partner_lms.new';

    const EXISTING_USER_TEMPLATE_PATH      = 'emails.invitation.razorpayx.invite_existing_user';

    const INVITE_LINK_FORMAT = '%s/auth/invitation?token=%s';

    protected $invitation;

    protected $invitationId;

    protected $senderName;

    protected $invitedUserExists;

    protected $isAnExistingUserOnX;

    protected $role;

    public function __construct($invitationId, $senderName, bool $invitedUserExists, bool $isAnExistingUserOnX, $role = null)
    {
        parent::__construct();

        $app = App::getFacadeRoot();

        $this->invitationId = $invitationId;

        $this->invitation = $app['repo']->invitation->find($invitationId);

        $this->senderName = $senderName;

        $this->invitedUserExists = $invitedUserExists;

        $this->isAnExistingUserOnX = $isAnExistingUserOnX;

        $this->role = $role;
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
        $this->subject(self::SUBJECT);

        return $this;
    }

    protected function addMailData()
    {
        $config = App::getFacadeRoot()['config'];

        $invitation = $this->getInvitation();

        $bankingUrl = $config['applications.bank_lms_banking_service_url'];

        $inviteLink = sprintf(self::INVITE_LINK_FORMAT, $bankingUrl, $invitation->getToken());

        $this->with(
            [
                'business_name' => $this->getBusinessName(),
                'sender_name'   => $this->senderName,
                'role'          => $this->getLabel($invitation->getRole() != null ? $invitation->getRole() : ''),
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

    public static function getLabel(string $role)
    {
        return ucwords(str_replace('_', ' ', $role));
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
        /*
         * Case where invited user is not registered on X but registered on PG
         * The invited user has a record in user table
         */
        if ($this->invitedUserExists)
        {
            $this->view(self::EXISTING_USER_TEMPLATE_PATH);
        }
        /*
         * Case where user hasn't registered on any product
         * The invited user doesn't have a record in user table
         */
        else
        {
            $this->view(self::NEW_USER_TEMPLATE_PATH);
        }

        return $this;
    }
}
