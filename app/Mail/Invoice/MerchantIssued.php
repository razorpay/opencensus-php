<?php

namespace RZP\Mail\Invoice;

use RZP\Models\Merchant;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Constants\Entity as E;

class MerchantIssued extends Mailable
{
    protected $data;

    protected $fileData;

    const DEFAULT_MERCHANT_TEMPLATE = 'Payment successful';

    public function __construct(array $data, array $fileData = null)
    {
        parent::__construct();

        $this->data = $data;

        $this->fileData = $fileData;
    }

    protected function addSender()
    {
        $fromEmail = app()['config']->get('app.apps_default_sender_email_address') ?? Constants::MAIL_ADDRESSES[Constants::NOREPLY];

        $fromHeader = 'Razorpay Payment Pages';

        $this->from($fromEmail, $fromHeader);

        return $this;
    }

    protected function addRecipients()
    {
        $toEmail = $this->data['to'];

        $this->to($toEmail);

        return $this;
    }

    protected function addSubject()
    {
        $subject = $this->getSubjectByInvoiceType();

        $this->subject($subject);

        return $this;
    }

    protected function addReplyTo()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::NOREPLY];

        $header = Constants::HEADERS[Constants::NOREPLY];

        $this->replyTo($email, $header);

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.mjml.customer.payment_page.payment');

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

    protected function getSubjectByInvoiceType(): string
    {
        if(empty($this->data['pp_invoice']) === false)
        {
            return $this->getPpMailSubject();
        }

        return self::DEFAULT_MERCHANT_TEMPLATE;
    }

    protected function getPpMailSubject()
    {
        $viewType = $this->data['invoice'][E::PAYMENT_PAGE]['view_type'];

        $appendText = 'Successful payment on ';

        switch ($viewType){

            case 'button':

               $appendText .= 'Payment Button';

               break;

            case 'page':

                $appendText .= 'Payment Page';

                break;
        }

        $appendText .= ' - '.$this->data['invoice'][E::PAYMENT_PAGE]['title'];

        return $appendText;
    }
}
