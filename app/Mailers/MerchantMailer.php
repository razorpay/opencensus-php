<?php

namespace App\Mailers;

use App\Merchant\Entity as MerchantEntity;
use App\Exception\InvalidContactInformationException;

class MerchantMailer extends Mailer
{
    const INVALID_MERCHANT_ERROR = "Valid merchant object must be provided for delivering an email.";

    /**
     * Create a new abstract mailer instance.
     *
     * @param \App\Merchant\Entity $merchant
     */
    public function __construct(MerchantEntity $merchant)
    {
        if (is_object($merchant) === false)
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
        $details = $this->data['merchant_details'];

        $this->subject = 'Razorpay | Account pending approval for ' . $details['business_name'];

        $this->to = $details['contact_name'];
        $this->email = $details['contact_email'];
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

        $this->to = 'Razorpay Activations Team';
        $this->email = $this->getEmailFor('activations');
        $this->view = 'emails.admin_notify';

        return $this;
    }
}
