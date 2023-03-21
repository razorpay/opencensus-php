<?php

namespace RZP\Mail\Invitation\Razorpayx;

use App;
use RZP\Constants\Product;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class IntegrationInvite extends Mailable
{
    const SUPPORT_URL        = 'https://razorpay.com/support/#request/merchant';

    const SUBJECT            = 'Invitation to Integrate RazorpayX with Zoho Books';

    const NEW_INVITATION_TEMPLATE_PATH      = 'emails.invitation.razorpayx.integration_invite_new';

    const REMINDER_INVITATION_TEMPLATE_PATH      = 'emails.invitation.razorpayx.integration_invite_reminder';

    const GET_STARTED_LINK = '%s/settings/integrations';

    protected $senderName;

    protected $isReminder;

    protected $toEmailId;

    public function __construct($senderName, $toEmailId, bool $isReminder = false)
    {
        parent::__construct();

        $this->senderName = $senderName;

        $this->toEmailId = $toEmailId;

        $this->isReminder = $isReminder;

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
        $this->to($this->toEmailId);

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

        $bankingUrl = $config['applications.banking_service_url'];

        $getStartedLink = sprintf(self::GET_STARTED_LINK, $bankingUrl);

        $this->with(
            [
                'sender_name'   => $this->senderName,
                'get_started_link'   => $getStartedLink,
                'support_url'   => self::SUPPORT_URL,
            ]
        );

        return $this;
    }

    protected function addHtmlView()
    {
        /*
         * Case where mail is sent to remind user for doing integration
         */
        if ($this->isReminder)
        {
            $this->view(self::REMINDER_INVITATION_TEMPLATE_PATH);
        }
        /*
         * Case where mail is sent first time for doing integration
         */
        else
        {
            $this->view(self::NEW_INVITATION_TEMPLATE_PATH);
        }

        return $this;
    }

}
