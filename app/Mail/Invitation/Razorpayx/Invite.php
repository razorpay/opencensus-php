<?php

namespace RZP\Mail\Invitation\Razorpayx;

use App;
use RZP\Constants\Product;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Models\User\Role;

class Invite extends Mailable
{
    const SUPPORT_URL        = 'https://razorpay.com/support/#request/merchant';

    const SUBJECT            = 'Invitation to join %s | RazorpayX';

    const NEW_USER_TEMPLATE_PATH      = 'emails.invitation.razorpayx.invite_new_user';

    const EXISTING_X_USER_TEMPLATE_PATH      = 'emails.invitation.razorpayx.invite_existing_x_user';

    const EXISTING_USER_TEMPLATE_PATH      = 'emails.invitation.razorpayx.invite_existing_user';

    const CA_PORTAL_INVITE_TEMPLATE_PATH      = 'emails.invitation.razorpayx.ca-invitation';

    const CA_PORTAL_INVITE_EXISTING_X_USER_TEMPLATE_PATH      = 'emails.invitation.razorpayx/ca-invitation-existing-x-user';

    const CA_PORTAL_INVITE_EXISTING_USER_TEMPLATE_PATH      = 'emails.invitation.razorpayx/ca-invitation-existing-user';

    const INVITE_LINK_FORMAT = '%s/auth?invitation=%s';

    protected $invitation;

    protected $invitationId;

    protected $senderName;

    protected $invitedUserExists;

    protected $isAnExistingUserOnX;

    protected $isIntegrationInvite;

    protected $role;

    public function __construct($invitationId, $senderName, bool $invitedUserExists, bool $isAnExistingUserOnX, $role = null, bool $isIntegrationInvite = false)
    {
        parent::__construct();

        $app = App::getFacadeRoot();

        $this->invitationId = $invitationId;

        $this->invitation = $app['repo']->invitation->find($invitationId);

        $this->senderName = $senderName;

        $this->invitedUserExists = $invitedUserExists;

        $this->isAnExistingUserOnX = $isAnExistingUserOnX;

        $this->isIntegrationInvite = $isIntegrationInvite;

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
        $this->subject(sprintf(self::SUBJECT, $this->getBusinessName()));

        return $this;
    }

    protected function addMailData()
    {
        $config = App::getFacadeRoot()['config'];

        $invitation = $this->getInvitation();

        $bankingUrl = $config['applications.banking_service_url'];

        $inviteLink = sprintf(self::INVITE_LINK_FORMAT, $bankingUrl, $invitation->getToken());

        $roleName = $this->getRoleName($invitation);

        $this->with(
            [
                'business_name' => $this->getBusinessName(),
                'sender_name'   => $this->senderName,
                'role'          => $this->getLabel($roleName != null ? $roleName : ''),
                'invite_link'   => $inviteLink,
                'support_url'   => self::SUPPORT_URL,
                'integration_invite' => $this->isIntegrationInvite ? ' and integrate Zoho Books':'',
            ]
        );

        return $this;
    }

    private function getRoleName($invitation)
    {
        $app = App::getFacadeRoot();

        return $app['repo']->roles->fetchRoleName($invitation->getRole()) ?? $invitation->getRole();
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
         * If the invitation is for CA role, then send in new invite template
         */
        if (empty($this->role) === false && $this->role == Role::CHARTERED_ACCOUNTANT) {

            if ($this->isAnExistingUserOnX)
            {
                $this->view(self::CA_PORTAL_INVITE_EXISTING_X_USER_TEMPLATE_PATH);
            }

            else if ($this->invitedUserExists)
            {
                $this->view(self::CA_PORTAL_INVITE_EXISTING_USER_TEMPLATE_PATH);
            }

            else
            {
                $this->view(self::CA_PORTAL_INVITE_TEMPLATE_PATH);
            }
        }
        /*
         * Case where invited user is already registered on X
         * Invited user has a record in merchant_user table with product as banking
         */
        else if ($this->isAnExistingUserOnX)
        {
            $this->view(self::EXISTING_X_USER_TEMPLATE_PATH);
        }
        /*
         * Case where invited user is not registered on X but registered on PG
         * The invited user has a record in user table
         */
        elseif ($this->invitedUserExists)
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
