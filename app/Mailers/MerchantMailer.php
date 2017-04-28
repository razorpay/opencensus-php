<?php

namespace App\Mailers;

use App\Merchant;
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
    public function __construct(Merchant\GenericMerchant $merchant, array $merchantDetails)
    {
        if (is_object($merchant) === false)
        {
            throw new InvalidContactInformationException(self::INVALID_MERCHANT_ERROR);
        }

        $this->to = $merchant->name;
        $this->email = $merchant->email;
        $this->data = $merchant->toArray();
        $this->data['merchant_details'] = $merchantDetails;

        $this->merchantDetails = $merchantDetails;
    }

    /**
     * Responsible for sending out an activation form submission confirmation email
     *
     * @return self
     */
    public function confirmActivationSubmission()
    {
        $this->subject = 'Razorpay | Account pending approval for ' . $this->merchantDetails['business_name'];

        $this->to = $this->merchantDetails['contact_name'];
        $this->email = $this->merchantDetails['contact_email'];
        $this->view = 'emails.submission';
        $this->mailTag = MailTags::CONFIRM_ACTIVATION_SUBMISSION;

        return $this;
    }

    /**
     * Responsible for sending notification to sales team about activation form submission
     *
     * @return self
     */
    public function notifyActivationSubmission()
    {
        $this->subject = "New activation form submitted for {$this->merchantDetails['business_name']}";

        $this->to = 'Razorpay Activations Team';
        $this->email = $this->getEmailFor('activations');
        $this->view = 'emails.admin_notify';
        $this->mailTag = MailTags::NOTIFY_ACTIVATION_SUBMISSION;

        return $this;
    }
}
