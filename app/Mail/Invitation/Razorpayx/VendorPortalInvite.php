<?php

namespace RZP\Mail\Invitation\Razorpayx;

use App;
use RZP\Models\User\Role;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Models\Settings\Module;
use RZP\Models\Invitation\Entity;
use RZP\Models\Settings\Service as SettingsService;


class VendorPortalInvite extends Mailable
{
    const SUBJECT                     = '%s has invited you to join Vendor Portal';

    const NEW_VENDOR_PORTAL_INVITE    = 'emails.vendor-portal.vendor_portal_invite_email';

    const REPEAT_VENDOR_PORTAL_INVITE = 'emails.vendor-portal.vendor_portal_repeat_email_invite';

    const SIGNUP_LINK_FORMAT          = '%s/vendor-portal/signup?invitation=%s';

    const LOGIN_LINK_FORMAT           = '%s/vendor-portal/login?invitation=%s';

    protected $invitationId;

    protected $invitation;

    protected $contactId;

    protected $contact;

    protected $invitedUserExists;

    protected $merchantSettings;

    protected $originalMerchant;

    public function __construct($invitationId, $contactId, $invitedUserExists, $originalMerchant)
    {
        parent::__construct();

        $app = App::getFacadeRoot();

        $this->invitationId = $invitationId;

        $this->invitation = $app['repo']->invitation->find($invitationId);

        $this->contactId = $contactId;

        $this->contact = $app['repo']->contact->findByPublicId($contactId);

        $this->invitedUserExists = $invitedUserExists;

        $this->originalMerchant = $originalMerchant;

        $this->merchantSettings = (new SettingsService())->getAll(Module::X_APPS);
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
        $inviteLink = $this->getInviteLink();

        $this->with(
            [
                'business_name'   => $this->getBusinessName(),
                'sender_name'     => $this->originalMerchant->getName(),
                'recipient_name'  => $this->contact->getName(),
                'invite_link'     => $inviteLink,
                'support_url'     => $this->merchantSettings['settings']['support_url'] ?? '',
                'support_contact' => $this->merchantSettings['settings']['support_contact'] ?? '',
                'support_email'   => $this->merchantSettings['settings']['support_email'] ?? '',
            ]
        );

        return $this;
    }

    protected function addHtmlView()
    {
        /*
         * Case where invited user is already registered on VendorPortal
         * Invited user has a record in merchant_user table with product as banking and role as VENDOR
         */
        if ($this->isAnExistingUserOnVendorPortal())
        {
            $this->view(self::REPEAT_VENDOR_PORTAL_INVITE);
        }

        /*
         * Case where invited user is not registered on VendorPortal
         */
        else
        {
            $this->view(self::NEW_VENDOR_PORTAL_INVITE);
        }

        return $this;
    }

    protected function getBusinessName()
    {
        return $this->originalMerchant
            ->getBillingLabel();
    }

    protected function getInviteLink()
    {
        $config = App::getFacadeRoot()['config'];

        $bankingUrl = $config['applications.banking_service_url'];

        if ($this->invitedUserExists)
        {
            $inviteLink = sprintf(self::LOGIN_LINK_FORMAT, $bankingUrl, $this->invitation->getToken());
        }
        else
        {
            $inviteLink = sprintf(self::SIGNUP_LINK_FORMAT, $bankingUrl, $this->invitation->getToken());
        }

        return $inviteLink;
    }

    protected function isAnExistingUserOnVendorPortal()
    {
        return $this->invitedUserExists && ($this->invitation->user->bankingMerchants()
                ->where(Entity::ROLE, Role::VENDOR)
                ->get()
                ->isEmpty()) === false;
    }
}

