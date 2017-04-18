<?php

namespace RZP\Mail\Payment;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;

class Refunded extends Base
{
    protected function addHtmlView()
    {
        $this->view('emails.refund.common');

        return $this;
    }

    protected function getAction()
    {
        return 'Refund';
    }

    protected function getMailTag()
    {
        return MailTags::REFUND_SUCCESSFUL;
    }

    public function isCustomerReceiptEmail()
    {
        if ($this->isMerchantEmail === true)
        {
            return false;
        }

        return true;
    }
}
