<?php

namespace Razorpay\Mailers;

use Models\Merchant\Entity as MerchantEntity;
use Razorpay\Exceptions\InvalidContactInformationException;

class MerchantMailer extends Mailer
{
    const INVALID_MERCHANT_ERROR = "Valid merchant object must be provided for delivering an email.";

    /**
     * Create a new abstract mailer instance.
     *
     * @param \Models\Merchant\Entity $merchant
     */
    public function __construct(MerchantEntity $merchant)
    {
        if(!is_object($merchant))
        {
            throw new InvalidContactInformationException(self::INVALID_MERCHANT_ERROR);
        }

        $this->to = $merchant->name;
        $this->email = $merchant->email;
        $this->data = $merchant->toArray();

        $this->data['merchant_details'] = $merchant->merchantDetails->toArray();
    }

    /**
     * Responsible for sending out an activation form submission confirmation email
     *
     * @return self
     */
    public function confirmActivationSubmission()
    {
        $this->subject = 'Razorpay | Account pending approval for '
            . $this->data['merchant_details']['business_name'];

        $this->to = $this->data['merchant_details']['contact_name'];
        $this->email = $this->data['merchant_details']['contact_email'];
        $this->view = 'emails.submission';

        return $this;
    }

    /**
     * Responsible for sending notification to sales team about activation form submission
     *
     * @return self
     */
    public function notifyActivationSubmission()
    {
        $this->subject = "New activation form submitted for {$this->data['merchant_details']['business_name']}";

        $this->to = 'Razorpay Sales Team';
        $this->email = $this->getEmailFor('sales');;
        $this->view = 'emails.admin_notify';

        return $this;
    }
}
