<?php

namespace RZP\Mail\Payment;

use RZP\Trace\TraceCode;
use RZP\Mail\Base\Constants;
use RZP\Models\Admin\Org\Entity as Org;


class Authorized extends Base
{

    // @todo : This logic should be refactored and moved to the templating service by creating use case specific templates
    protected array $storkWhitelistedOrgs = [
        Org::RAZORPAY_ORG_ID,
        Org::CURLEC_ORG_ID
    ];

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
        $this->data['data'] = $this->getCustomerSupportText();

        $this->with($this->data);

        $app = \App::getFacadeRoot();
        $app->trace->info(TraceCode::AUTHORIZE_MAIL, [
            'data' => $this->data
        ]);

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
        $orgCode = $this->data['org']['custom_code'] ?? '';

        return Constants::getSenderEmailForOrg($orgCode, Constants::NOREPLY);
    }

    protected function getSenderHeader(): string
    {
        $orgCode = $this->data['org']['custom_code'] ?? '';

        return Constants::getSenderNameForOrg($orgCode, Constants::NOREPLY);
    }

    protected function shouldSendEmailViaStork(): bool
    {
        $app = \App::getFacadeRoot();
        $data = $this->data;

        // @todo : Remove this logic, to not be extended further
        if ((isset($data['merchant']['eligible_for_covid_relief']) and
            $data['merchant']['eligible_for_covid_relief'] === true) or
            isset($data['org']['id']) and in_array($data['org']['id'], $this->storkWhitelistedOrgs) === false)
        {
            $app->trace->info(TraceCode::AUTHORIZE_MAIL, [
                'data' => $this->data,
                'shouldSendEmailViaStork' => false,
            ]);
            return false;
        }

        $app->trace->info(TraceCode::AUTHORIZE_MAIL, [
            'data' => $this->data,
            'shouldSendEmailViaStork' => true,
        ]);

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
                    'unsigned_id'                   => $data['payment']['id'],
                    'dcc'                           => $data['payment']['dcc'],
                    'payment_currency'              => $data['payment']['currency'],
                    'gateway_currency'              => $data['payment']['gateway_currency'],
                    'exchange_rate'                 => $data['payment']['exchange_rate'],
                    'payment_amount_without_symbol' => $data['payment']['amount_without_symbol'],
                    'gateway_amount_without_symbol' => $data['payment']['gateway_amount_without_symbol'],
                    'currency_conversion_fee_without_symbol' => $data['payment']['currency_conversion_fee_without_symbol'],
                    'reduced_markup_percent'        => $data['payment']['reduced_markup_percent'],
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

        if (isset($data['rewards']) === true)
        {
            $storkParams['params']['rewards'] = $data['rewards'];
            $storkParams['template_name'] = 'customer.payment.authorized_with_rewards';
        } elseif (isset($data['payment']['dcc']) && $data['payment']['dcc']) {
            $storkParams['template_namespace'] = "payments_crossborder";
            $storkParams['template_name'] = 'customer.payment.authorized_with_compliant_dcc';
        } else {
            $storkParams['template_name'] = 'customer.payment.authorized_without_rewards';
        }

        if (isset($data['qr_customer']) === true)
        {
            $storkParams['params']['qr_customer'] = $data['qr_customer'];
        }

        return $storkParams;
    }
}
