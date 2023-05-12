<?php

namespace RZP\Mail\Invoice;

use RZP\Constants\Entity;
use RZP\Mail\Base\Constants;
use RZP\Models\Invoice\Type;
use RZP\Models\Merchant\Preferences;
use RZP\Models\Invoice\Entity as InvoiceEntity;
use RZP\Models\SubscriptionRegistration\Entity as SubRegEntity;
use RZP\Models\SubscriptionRegistration\Method as SubRegMethod;

class Issued extends Base
{
    const SUBJECT_TEMPLATES = [
        Type::LINK                              => ' Requesting payment of %s %s (via Razorpay)',
        Type::ECOD                              => ' Requesting payment of %s %s (via Razorpay)',
        Type::INVOICE                           => ' Invoice from %s',
        Preferences::MID_RBL_RETAIL_ASSETS      => ' Mandate registration link from RBL Bank',
        Preferences::MID_RBL_RETAIL_CUSTOMER    => ' Mandate registration link from RBL Bank',
        Preferences::MID_RBL_RETAIL_PRODUCT     => ' Mandate registration link from RBL Bank',
        Preferences::MID_RBL_INTERIM_PROCESS2   => ' Mandate registration link from RBL Bank',
        Preferences::MID_ADITYA_BIRLA_HEALTH    => ' Auto Debit Registration for Policy - %s',
        Preferences::MID_BOB_FIN                => ' Direct Debit registration (e-Mandate) for BoB Credit Card monthly bill payment',
        'pp_invoice'                            => ' Payment Page',
    ];

    protected $fileData;

    public function __construct(array $data, array $fileData = null)
    {
        parent::__construct($data);

        $this->fileData = $fileData;
    }

    protected function addHtmlView()
    {
        $merchantId = $this->data['merchant']['id'];

        switch ($merchantId)
        {
            case Preferences::MID_RBL_RETAIL_ASSETS:

                $this->view('emails.invoice.customer.custom.rbl_retail_assets');

                break;

            case Preferences::MID_RBL_RETAIL_CUSTOMER:

                $this->view('emails.invoice.customer.custom.rbl_retail_customer');

                break;

            case Preferences::MID_RBL_RETAIL_PRODUCT:

                $this->view('emails.invoice.customer.custom.rbl_retail_product');

                break;

            case Preferences::MID_BAGIC:

                $this->view('emails.invoice.customer.custom.bagic');

                break;

            case Preferences::MID_RBL_INTERIM_PROCESS2:

                if ($this->data[Entity::INVOICE][InvoiceEntity::ENTITY_TYPE] === Entity::SUBSCRIPTION_REGISTRATION and
                    $this->data[Entity::INVOICE][Entity::SUBSCRIPTION_REGISTRATION][SubRegEntity::METHOD] === Constants::EMANDATE)
                {
                    $this->view('emails.invoice.customer.custom.rbl_interim_process2');
                }
                else
                {
                    $this->view('emails.invoice.customer.notification');
                }

                break;

            default:

                $this->view('emails.invoice.customer.notification');

                break;

        }

        if(empty($this->data['pp_invoice']) === false)
        {
            $this->view('emails.mjml.customer.payment_page.payment');
        }

        return $this;
    }

    protected function addAttachments()
    {
        if ($this->fileData !== null)
        {
            $pdfDisplayName = $this->fileData['name'];

            if ($this->fileData['path'] !== null)
            {
                $this->attach(
                    $this->fileData['path'],
                    ['as' => $pdfDisplayName, 'mime' => 'application/pdf']
                );
            }

        }

        return $this;
    }

    protected function addSubject()
    {
        $subject = $this->getSubjectByInvoiceType();

        if (empty($this->data['reminder']) === false)
        {
            $subject = 'Reminder:' . $subject;
        }

        $this->subject($subject);

        return $this;
    }

    protected function addReplyTo()
    {
        $replyTo = $this->getSenderEmail();

        $merchantId = $this->data['merchant']['id'] ?? '';

        if ($merchantId === Preferences::MID_BOB_FIN)
        {
            $replyTo = Constants::MERCHANT_CUSTOM_MAIL_ADDRESSES[$merchantId] ?? $replyTo;
        }

        $header = $this->getSenderHeader();

        $this->replyTo($replyTo, $header);

        return $this;
    }

    protected function shouldSendEmailViaStork(): bool
    {
        $data = $this->data;

        if ($data['invoice']['type'] === 'link')
        {
            return parent::shouldSendEmailViaStork();
        }

        return false;
    }

    protected function getParamsForStork(): array
    {
        $data = $this->data;

        $invoiceData = $data['invoice'];
        $invoiceEntityType = $data['invoice']['entity_type'];

        $storkParams = [
            'template_namespace'            => 'payments_payment_links',
            'org_id'                        => $data['org']['id'],
            'template_name'                 => 'customer.payment_link.issued_full_payment',
            'params'                        => [
                'merchant'          => [
                    'billing_label'         => $data['merchant']['billing_label'],
                    'brand_color'           => $data['merchant']['brand_color'],
                    'brand_logo'            => $data['merchant']['brand_logo'],
                    'brand_contrast_color'  => $data['merchant']['contrast_color'],
                ],
                'invoice'           => [
                    'public_id'             => $invoiceData['id'],
                    'description'           => $invoiceData['description'],
                    'receipt'               => $invoiceData['receipt'],
                    'short_url'             => $invoiceData['short_url'],
                    'expire_by_formatted'   => $invoiceData['expire_by_formatted'],
                    'amount'                => [
                        'symbol'                => $invoiceData['amount_spread'][0],
                        'units'                 => $invoiceData['amount_spread'][1],
                        'subunits'              => $invoiceData['amount_spread'][2],
                    ],
                ],

                'customer'          => [
                    'email'                 => $data['invoice']['customer_details']['email'],
                    'phone'                 => $data['invoice']['customer_details']['contact'],
                ],

                'org'               => [
                    'name'                  => $data['org']['display_name'],
                    'logo_url'              => $data['org']['checkout_logo_url'] ?? $data['org']['main_logo_url'],
                ],
            ],
        ];

        if ($invoiceData['partial_payment'] === true)
        {
            $storkParams['template_name'] = 'customer.payment_link.issued_partial_payment';

            $storkParams['params']['invoice']['amount_due'] = [
                'symbol'    => $invoiceData['amount_due_spread'][0],
                'units'     => $invoiceData['amount_due_spread'][1],
                'subunits'  => $invoiceData['amount_due_spread'][2],
            ];

            $storkParams['params']['invoice']['amount_paid'] = [
                'symbol'    => $invoiceData['amount_paid_spread'][0],
                'units'     => $invoiceData['amount_paid_spread'][1],
                'subunits'  => $invoiceData['amount_paid_spread'][2],
            ];
        }
        else if (
            $invoiceEntityType === Entity::SUBSCRIPTION_REGISTRATION and
            isset($invoiceData[Entity::SUBSCRIPTION_REGISTRATION]) === true)
        {
            $subRegData = $invoiceData[Entity::SUBSCRIPTION_REGISTRATION];

            if ($subRegData['method'] === SubRegMethod::CARD)
            {
                $storkParams['template_name'] = 'customer.authorization_links.issued_with_card';
            }
            else if ($subRegData['method'] === SubRegMethod::UPI)
            {
                $storkParams['template_name'] = 'customer.authorization_links.issued_with_upi';
            }

        }

        return $storkParams;
    }
}
