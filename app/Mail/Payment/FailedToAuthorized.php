<?php

namespace RZP\Mail\Payment;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;

class FailedToAuthorized extends Base
{
    protected function addHtmlView()
    {
        if ($this->isMerchantEmail === true)
        {
            $this->view('emails.payment.failed_to_authorized');
        }
        else
        {
            $this->view('emails.mjml.customer.payment');
        }

        return $this;
    }

    protected function addTextView()
    {
        if ($this->isMerchantEmail === true)
        {
            $this->text('emails.payment.failed_to_authorized_text');
        }
        else
        {
            $this->text('emails.payment.customer_text');
        }

        return $this;
    }

    protected function getMailTag()
    {
        return MailTags::FAILED_TO_AUTHORIZED;
    }

    protected function addMailData()
    {
        parent::addMailData();

        $this->data['data'] = $this->getCustomerSupportText();

        return $this;
    }

    protected function addReplyTo()
    {
        $email = $this->getSupportEmailInReplyTo($this->isMerchantEmail);

        $this->replyTo($email);

        return $this;
    }

    public function isCustomerReceiptEmail()
    {
        if ($this->isMerchantEmail === true)
        {
            return false;
        }

        return true;
    }

    protected function shouldSendEmailViaStork(): bool
    {
        return parent::shouldSendEmailViaStork();
    }

    protected function getParamsForStork(): array
    {
        $data = $this->data;
        
        $storkParams = [
            'template_namespace'                => 'payments_core',
            'org_id'                            => $data['org']['id'],
            'params'        => [
                'email_logo'                    => $data['email_logo'],
                'custom_branding'               => $data['custom_branding'],
                'header_timestamp'              => \Carbon\Carbon::now("Asia/Kolkata")->format('jS F Y'),
                'payment' => [
                    'public_id'                 => $data['payment']['public_id'],
                    'amount'                    => $data['payment']['amount'],
                    'order_id'                  => $data['payment']['orderId'],
                ],

                'customer'  => [
                    'email'                    => $data['customer']['email'],
                    'phone'                    => $data['customer']['phone'],
                ],

                'merchant'  => [
                    'website'                  => $data['merchant']['website'],
                    'billing_label'            => $data['merchant']['billing_label'],
                ],

                // hardcoding this as of now, will remove this as soon as way
                // of getting orgs from basic auth is figured
                // out while sending the email
                'org'       => [
                    'name'                 => 'Razorpay Software Private Ltd',
                    'logo_url'             => 'https://cdn.razorpay.com/logo.png',
                ],
            ],
        ];

        if (isset($data['message']) === true)
        {
            $storkParams['message'] = $data['message'];
        }

        $storkParams['template_name'] = 'payments_failed_to_authorize';

        return $storkParams;
    }

}
