<?php

namespace RZP\Mail\Payment;

use RZP\Mail\Base\Constants;
use RZP\Models\Admin\Org\Entity as Org;


class Authorized extends Base
{
    protected function addHtmlView()
    {
        $emailView = 'emails.mjml.customer.payment';

        $this->view($emailView);

        return $this;
    }

    protected function addTextView()
    {
        $this->text('emails.payment.customer_text');

        return $this;
    }

    protected function addMailData()
    {
        parent::addMailData();

        $this->data['data'] = $this->getCustomerSupportText();

        return $this;
    }

    protected function addReplyTo()
    {
        $email = $this->getSupportEmailInReplyTo();

        $this->replyTo($email);

        return $this;
    }

    public function isCustomerReceiptEmail()
    {
        return true;
    }

    protected function getSenderEmail(): string
    {
        return Constants::MAIL_ADDRESSES[Constants::NOREPLY];
    }

    protected function getSenderHeader(): string
    {
        return Constants::HEADERS[Constants::NOREPLY];
    }

    protected function shouldSendEmailViaStork(): bool
    {
        $data = $this->data;

        if ((isset($data['merchant']['eligible_for_covid_relief']) and
            $data['merchant']['eligible_for_covid_relief'] === true) or
            isset($data['org']['id']) and $data['org']['id'] !== Org::RAZORPAY_ORG_ID)
        {
            return false;
        }

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
                    'unsigned_id'               => $data['payment']['id'],
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
                    'name'                 => 'Razorpay Software Private Ltd',
                    'logo_url'             => 'https://cdn.razorpay.com/logo.png',
                ],
            ],
        ];

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

        return $storkParams;
    }
}
