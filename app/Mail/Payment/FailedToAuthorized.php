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
        $this->data['data'] = $this->getCustomerSupportText();

        $this->with($this->data);

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
        return true;
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
                    'name'                 => $data['org']['display_name'],
                    'logo_url'             => $data['org']['logo_url'],
                    'custom_code'          => $data['org']['custom_code'],
                    'hostname'             => $data['org']['hostname'],
                ],
            ],
        ];

        if (isset($data['message']) === true)
        {
            $storkParams['message'] = $data['message'];
        }

        $storkParams['template_name'] = 'payments_failed_to_authorize';

        if ($this->isMerchantEmail === false)
        {
            $storkParams['params']['payment']['amount_symbol'] = $data['payment']['amount_spread'][0];

            $storkParams['params']['payment']['amount_units'] = $data['payment']['amount_spread'][1];

            $storkParams['params']['payment']['amount_subunits'] = $data['payment']['amount_spread'][2];

            $storkParams['params']['payment']['created_at_formatted'] = $data['payment']['created_at_formatted'];

            $storkParams['params']['payment']['method']['first_value'] = $data['payment']['method'][0];

            $storkParams['params']['payment']['method']['second_value'] = $data['payment']['method'][1];

            $storkParams['params']['payment']['unsigned_id'] = $data['payment']['id'];

            $storkParams['params']['merchant']['billing_label'] = $data['merchant']['billing_label'];

            $storkParams['params']['merchant']['brand_color'] = $data['merchant']['brand_color'];

            $storkParams['params']['merchant']['brand_contrast_color'] = $data['merchant']['contrast_color'];

            $storkParams['params']['merchant']['report_url'] = $data['merchant']['report_url'];

            if (isset($data['merchant']['support_details']))
            {
                $storkParams['params']['merchant']['support_details'] = $data['merchant']['support_details'];
            }

            if (isset($data['rewards']) === true)
            {
                $storkParams['params']['rewards'] = $data['rewards'];

                $storkParams['template_name'] = 'customer.payment.authorized_with_rewards';
            }
            else
            {
                $storkParams['template_name'] = 'customer.payment.authorized_without_rewards';
            }
        }

        return $storkParams;
    }

}
