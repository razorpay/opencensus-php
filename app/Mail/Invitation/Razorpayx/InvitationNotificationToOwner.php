<?php

namespace RZP\Mail\Invitation\Razorpayx;

use App;
use RZP\Mail\Base\Constants;
use RZP\Mail\Base\Mailable;
use RZP\Models\User\Entity as UserEntity;

class InvitationNotificationToOwner extends Mailable
{
    const SUBJECT         = '[IMP] New user added to %s on RazorpayX';
    const TEMPLATE        = 'emails.invitation.razorpayx.invitation_notification_to_owner';
    const MANAGE_TEAM_URL = '%s/settings/team';

    protected $data;
    /**
     * @var UserEntity
     */
    protected $owner;

    public function __construct($data, $owner)
    {
        parent::__construct();

        $this->data = $data;
        $this->owner = $owner;
    }

    protected function addRecipients()
    {
        $this->to($this->owner->getEmail(),
                  $this->owner->getName());

        return $this;
    }

    protected function addSender()
    {
        $this->from(Constants::MAIL_ADDRESSES[Constants::X_SUPPORT],
                    Constants::HEADERS[Constants::X_SUPPORT]);

        return $this;
    }

    protected function addSubject()
    {
        $this->subject(sprintf(self::SUBJECT, $this->data['merchant_name']));

        return $this;
    }

    protected function addMailData()
    {
        $config = App::getFacadeRoot()['config'];

        $bankingUrl = $config['applications.banking_service_url'];

        $manageTeamUrl = sprintf(self::MANAGE_TEAM_URL, $bankingUrl);

        $data = $this->data;

        $data['manage_team_url'] = $manageTeamUrl;
        $data['inviter_role'] = $this->getRoleName($data['inviter_role']);
        $data['invitee_role'] = $this->getRoleName($data['invitee_role']);

        $this->with($data);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view(self::TEMPLATE);

        return $this;
    }

    private function getRoleName($role)
    {
        $app = App::getFacadeRoot();

        return $app['repo']->roles->fetchRoleName($role) ?? $role;
    }
}

