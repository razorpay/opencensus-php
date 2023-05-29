<?php

namespace RZP\Mail\Payment;

use RZP\Mail\Base\Constants;

class B2bUploadInvoice extends Base
{
    protected function addHtmlView()
    {
        return $this;
    }

    protected function addTextView()
    {
        return $this;
    }

    protected function shouldSendEmailViaStork(): bool
    {
        return true;
    }

    protected function addSender()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::CROSS_BORDER];

        $header = "Cross Border";

        $this->from($email, $header);

        return $this;
    }

    protected function getMailTag()
    {
        return MailTags::B2B_UPLOAD_INVOICE;
    }

    protected function addSubject()
    {
        $subjectLine = "[Important] Invoice proof and payer billing address details required for international bank transfer";

        $this->subject($subjectLine);

        return $this;
    }

    protected function getParamsForStork(): array
    {
        $data = $this->data;

        $storkParams = [
            'template_namespace' => 'payments_crossborder',
            'template_name'      => 'b2b_invoice_upload',
            'org_id'             => $data['org']['id'],
            'params'             => [
                'merchant_name'             => $data['merchant']['name'],
                'payment_public_id'         => $data['payment']['public_id'],
                'amount'                    => $data['payment']['amount'],
                'amount_symbol'             => $data['payment']['amount_spread'][0],
                'amount_units'              => $data['payment']['amount_spread'][1],
                'amount_subunits'           => $data['payment']['amount_spread'][2],
            ],
        ];

        return $storkParams;
    }
}
