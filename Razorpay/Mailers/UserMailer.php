<?php 

namespace Razorpay\Mailers;

use Razorpay\Exceptions\InvalidContactInformationException;
use Models\Merchant\Entity as MerchantEntity;

class UserMailer extends Mailer
{
    /**
     * Create a new abstract mailer instance.
     *
     * @param \Models\Merchant\Entity $user
     */
    public function __construct(MerchantEntity $user)
    {
        if(!is_object($user)){
            throw new InvalidContactInformationException("A valid user object must be provided for delivering an email.");
        }

        $this->to = $user->name;
        $this->email = $user->email;
        $this->data = $user->toArray();
        $this->data['merchant_details'] = $user->merchantDetails->toArray();
    }

    /**
     * Responsible for sending out an account confirmation email to the user
     *
     * @return \Razorpay\Mailer\UserMailer
     */
    public function accountVerification()
    {
        $this->subject = 'Razorpay | Confirm Your Email';
        $this->view = 'emails.confirmation';

        return $this;
    }

    /**
     * Responsible for sending out an activation form submission confirmation email to the user
     *
     * @return \Razorpay\Mailer\UserMailer
     */
    public function confirmActivationSubmission()
    {
        //sd($this->data);
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
     * @return \Razorpay\Mailer\UserMailer
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