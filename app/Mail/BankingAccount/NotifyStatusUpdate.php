<?php


namespace RZP\Mail\BankingAccount;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Exception\LogicException;
use RZP\Models\BankingAccount\Status;

class NotifyStatusUpdate extends Mailable
{
    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addRecipients()
    {
        $toEmail = $this->data['contact_email'];

        $toName = $this->data['contact_name'];

        $this->to($toEmail, $toName);

        return $this;
    }

    protected function addSender()
    {
        $fromEmail = Constants::MAIL_ADDRESSES[Constants::SUPPORT];

        $fromName = Constants::HEADERS[Constants::RAZORPAY_X];

        $this->from($fromEmail, $fromName);

        return $this;
    }

    protected function addHtmlView()
    {
        $status = $this->data['status'];

        switch ($status)
        {
            case STATUS::CANCELLED:
                $this->view('emails.banking_account.notify_status_cancelled');

                break;

            case STATUS::PROCESSING:
                $this->view('emails.banking_account.notify_status_processing');

                break;

            case STATUS::PROCESSED:
                $this->view('emails.banking_account.notify_status_processed');

                break;

            default:
                throw new LogicException(
                    'Undefined Banking Account status: ' . $status,
                    null,
                    $status);

        }

        return $this;
    }

    protected function addSubject()
    {
        $subject = 'Your RazorpayX Current Account is ' . $this->data['status'];

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

    protected function getMailTag()
    {
        return MailTags::ACCOUNT_STATUS_UPDATED;
    }
}
