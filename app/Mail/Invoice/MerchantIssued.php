<?php

namespace RZP\Mail\Invoice;

use Razorpay\Trace\Logger as Trace;
use RZP\Http\Request\Requests;
use RZP\Mail\Base\Stork;
use RZP\Models\Merchant;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Constants\Entity as E;
use RZP\Trace\TraceCode;

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

    protected function shouldSendEmailViaStork(): bool
    {
        $app = \App::getFacadeRoot();

        $traceData = [
            'merchant_id' => $this->data['merchant']['id'],
            'view' => $this->view,
            'data' => $this->data,
            'isStorkEmailVIAEnabled' => true
        ];

        $app['trace']->info(TraceCode::PAYMENT_LINK_EMAIL_ATTEMPT_VIA_SPLITZ_MERCHANT_ISSUED , $traceData);

        if ($this->fileData !== null && $this->fileData['path'] !== null )
        {
            try
            {
                $params = $this->getParamsForFile();
                $path = $this->fileData['path'];
                $file_id = (new Stork($this->mode, $this->originProduct))->getFileId($params,$path);

                if (!empty($file_id))
                {
                    $this->fileData['file_id'] = $file_id;

                    return true;
                }
            }
            catch (\Throwable $e)
            {
                $app['trace']->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::GET_FILE_ID_EXCEPTION,
                    ['file_data' => $this->fileData]
                );
                return false;
            }
        }

        return false;
    }

    protected function getParamsForStork(): array
    {
        return [
            'template_name' => $this->view,
            'template_namespace' => 'payments_payment_links',
            'org_id' => $this->data['org']['id'],
            'params' => $this->data,
            'attachments' => [
                [
                    "file_id" => $this->fileData['file_id'],
                    "display_name" => $this->fileData['name'],
                    "extension" => "pdf"
                ]
            ]
        ];
    }
    protected function getParamsForFile(): array
    {
        return [
            'channel'            => 'email',
            'owner_id'           => $this->mid,
            'owner_type'         => 'merchant',
            'url_count'          => '1'
        ];
    }
}
