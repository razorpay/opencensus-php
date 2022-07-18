<?php


namespace RZP\Mail\Merchant\RazorpayX;

use App;
use RZP\Mail\Base\Constants;
use RZP\Mail\Base\Mailable;
use RZP\Models\User\Role;

class RolePermissionChange extends Mailable
{

    const SUPPORT_URL        = 'https://razorpay.com/support/#request/merchant';

    const SUBJECT            = "There's an update in your role";

    const CAC_ROLE_PERMISSION_TEMPLATE_PATH      = 'emails.merchant.role_permission_change';

    protected $senderName;

    protected $user;

    protected $merchantUserRole;

    public function __construct($senderName, $user, $merchantUserRole)
    {
        parent::__construct();

        $app = App::getFacadeRoot();

        $this->senderName = $senderName;
        $this->user = $user;
        $this->merchantUserRole = $merchantUserRole;
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
        $this->to($this->user->getEmail());
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

        $bankingUrl = $config['applications.banking_service_url'];

        $this->with(
            [
                'receiver_name' => $this->user->getName(),
                'role_responsible_change' => $this->merchantUserRole,
                'business_name' => $this->getBusinessName(),
                'invoice_link'   => $bankingUrl,
                'support_url'   => self::SUPPORT_URL
            ]
        );

        return $this;
    }

    protected function getBusinessName()
    {
       return $this->senderName;
    }

    protected function addHtmlView()
    {
        $this->view(self::CAC_ROLE_PERMISSION_TEMPLATE_PATH);
        return $this;
    }
}
