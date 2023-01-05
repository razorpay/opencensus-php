<?php

namespace RZP\Mail\Payment;

class Failed extends Base
{
    protected function addSubject()
    {
        $label = $this->data['merchant']['billing_label'] ?? $this->data['payment']['amount'];

        $orgName = $this->data['org']['display_name'] ?? 'Razorpay';

        $subject = $orgName . " | Payment failed for $label";

        $this->subject($subject);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.payment.merchant_failure');

        return $this;
    }

    protected function addTextView()
    {
        $this->text('emails.payment.merchant_text');

        return $this;
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
                    'error_description'         => $data['payment']['error_description'],
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

        $storkParams['template_name'] = 'payments_merchant_failure';

        return $storkParams;
    }
}

