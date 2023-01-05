<?php

namespace RZP\Mail\Payment;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;

class CustomerFailed extends Base
{

    const ZERODHA_MIDS = [
        'EQ8AzfZip2meDu',
        '4jrfbTLsua1pWJ',
        '9VIi8FakOk1SiV',
        '5zJACbxPORFLk8',
        'F02iHSCplfL5m7',
        'Eh54Q1B6HQKbS3',
        'E2Mw08X9tYN35Z',
        '5XBrWzODBkDPmi',
        '4sR6aB3rYxH3Sv',
    ];

    protected function addSubject()
    {
        $label = $this->data['merchant']['billing_label'] ?? $this->data['payment']['amount'];

        $subject = "Payment failed for $label";

        $this->subject($subject);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.mjml.customer.failure');

        return $this;
    }

    protected function addTextView()
    {
        $this->text('emails.payment.merchant_text');

        return $this;
    }

    protected function getMailTag()
    {
        return MailTags::PAYMENT_FAILED;
    }

    public function isCustomerReceiptEmail()
    {
        return true;
    }

    protected function addReplyTo()
    {
        $email = $this->getSupportEmailInReplyTo();

        $this->replyTo($email);

        return $this;
    }

    protected function addMailData()
    {
       $data = $this->data;

       if ((isset($data['merchant']['id']) === true) and
           (isset($data['merchant']['support_details']) === true) and
           (isset($data['merchant']['support_details']['email']) === true) and
           (in_array($data['merchant']['id'], self::ZERODHA_MIDS) === true))
       {
           $data['merchant']['support_details']['email'] = 'support.zerodha.com';
       }

        $this->with($data);

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
                'payment' => [
                    'public_id'                 => $data['payment']['public_id'],
                    'amount_symbol'             => $data['payment']['amount_spread'][0],
                    'amount_units'              => $data['payment']['amount_spread'][1],
                    'amount_subunits'           => $data['payment']['amount_spread'][2],
                    'created_at_formatted'      => $data['payment']['created_at_formatted'],
                    'method'                    => [
                        'first_value'               => $data['payment']['method'][0],
                        'second_value'              => $data['payment']['method'][1],
                    ],
                ],

                'customer'  => [
                    'email'                    => $data['customer']['email'],
                    'phone'                    => $data['customer']['phone'],
                ],

                'merchant'  => [
                    'billing_label'            => $data['merchant']['billing_label'],
                    'brand_color'              => $data['merchant']['brand_color'],
                    'brand_contrast_color'     => $data['merchant']['contrast_color'],
                    'report_url'               => $data['merchant']['report_url'],
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

        if (isset($data['merchant']['support_details']))
        {
            $storkParams['params']['merchant']['support_details'] = $data['merchant']['support_details'];
        }

        $storkParams['template_name'] = 'payments_customer_failure';

        return $storkParams;
    }
}
