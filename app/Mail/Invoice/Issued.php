<?php

namespace RZP\Mail\Invoice;

use RZP\Constants\Entity;
use RZP\Mail\Base\Constants;
use RZP\Models\Invoice\Type;
use RZP\Models\Merchant\Preferences;
use RZP\Models\Invoice\Entity as InvoiceEntity;
use RZP\Models\SubscriptionRegistration\Entity as SubRegEntity;

class Issued extends Base
{
    const SUBJECT_TEMPLATES = [
        Type::LINK                              => ' Requesting payment of %s %s (via Razorpay)',
        Type::ECOD                              => ' Requesting payment of %s %s (via Razorpay)',
        Type::INVOICE                           => ' Invoice from %s',
        Preferences::MID_RBL_RETAIL_ASSETS      => ' Mandate registration link from RBL Bank',
        Preferences::MID_RBL_INTERIM_PROCESS2   => ' Mandate registration link from RBL Bank',
        Preferences::MID_ADITYA_BIRLA_HEALTH    => ' Auto Debit Registration for Policy - %s',
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
}
